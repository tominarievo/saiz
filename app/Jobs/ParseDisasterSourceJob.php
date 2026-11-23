<?php

namespace App\Jobs;

use App\Disaster;
use App\MeetingInsight;
use App\RoadRegulation;
use App\Services\CollectedInformationBlocklist;
use App\Services\DisasterDataSource\DisasterDataSourceRecorder;
use App\Services\DisasterIntelligence\DisasterSourceParser;
use App\Services\DisasterIntelligence\MeetingRelevanceFilter;
use App\Services\DisasterIntelligence\PdfTextExtractor;
use App\WasteSite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * 保存済みの災害資料を非同期でパースする。
 */
class ParseDisasterSourceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var int
     */
    public $tries = 3;

    /**
     * @var int
     */
    public $timeout = 300;

    /**
     * @var int
     */
    public $backoff = 30;

    /**
     * @param int $disasterId
     * @param string $type
     * @param string $filePath
     * @param string $sourceUrl
     * @param string $sourceOrg
     * @param string|null $sourcePublishedAt
     * @param int|null $sourcePage
     */
    public function __construct(
        public readonly int $disasterId,
        public readonly string $type,
        public readonly string $filePath,
        public readonly string $sourceUrl,
        public readonly string $sourceOrg,
        public readonly ?string $sourcePublishedAt = null,
        public readonly ?int $sourcePage = null,
        public readonly ?string $sourceCredibility = null,
        public readonly ?string $sourceCredibilityReason = null,
        public readonly ?string $sourcePageUrl = null
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(
        PdfTextExtractor $extractor,
        DisasterSourceParser $parser,
        CollectedInformationBlocklist $blocklist,
        MeetingRelevanceFilter $meetingRelevance
    ): void {
        ini_set('memory_limit', '512M');

        $disaster = Disaster::find($this->disasterId);

        if (!$disaster) {
            Log::warning('災害資料のパース対象災害が見つかりません。', ['disaster_id' => $this->disasterId]);
            return;
        }

        $disk = Storage::disk(config('disaster-intelligence.storage_disk', 'local'));

        if (!$disk->exists($this->filePath)) {
            throw new \RuntimeException('保存済み資料が見つかりません: ' . $this->filePath);
        }

        $absolutePath = $disk->path($this->filePath);
        $extension = strtolower(pathinfo($this->filePath, PATHINFO_EXTENSION));

        try {
            $text = $extractor->extract($absolutePath, $extension);
        } catch (\Throwable $error) {
            Log::warning('災害資料の本文抽出をスキップしました。', [
                'disaster_id' => $this->disasterId,
                'type' => $this->type,
                'file' => $this->filePath,
                'message' => $error->getMessage(),
            ]);

            return;
        }

        if (trim($text) === '') {
            Log::warning('災害資料の本文が空です。', ['file' => $this->filePath]);
            return;
        }

        $attribution = [
            'source_org' => $this->sourceOrg,
            'source_url' => $this->sourceUrl,
            'source_published_at' => $this->sourcePublishedAt,
            'scraped_at' => now()->toDateTimeString(),
            'raw_file_path' => $this->filePath,
            'source_credibility' => $this->sourceCredibility,
            'source_credibility_reason' => $this->sourceCredibilityReason,
        ];

        $targetMunicipalities = $disaster->localGovernments()
            ->pluck('name')
            ->filter()
            ->values()
            ->all();

        $records = array_values($parser->parse($this->type, $text, $attribution, $targetMunicipalities));
        $citationUrl = $this->sourcePageUrl ?: null;

        if ($citationUrl !== null) {
            $records = array_map(function (array $record) use ($citationUrl) {
                $record['metadata'] = array_merge(
                    is_array($record['metadata'] ?? null) ? $record['metadata'] : [],
                    [
                        'citation_url' => $citationUrl,
                        'retrieval_url' => $this->sourceUrl,
                    ]
                );

                return $record;
            }, $records);
        }

        $roadStates = [];

        if ($this->type === 'roads') {
            $roadStates = RoadRegulation::where('disaster_id', $this->disasterId)
                ->where('source_url', $this->sourceUrl)
                ->get()
                ->mapWithKeys(function (RoadRegulation $road) {
                    return [$this->roadStateKey($road->road_name, $road->section) => [
                        'status' => $road->status,
                        'recovered_at' => $road->recovered_at,
                        'last_verified_at' => $road->last_verified_at,
                        'recovery_source_url' => $road->recovery_source_url,
                        'recovery_evidence' => $road->recovery_evidence,
                    ]];
                })
                ->all();

            RoadRegulation::where('disaster_id', $this->disasterId)
                ->where('source_url', $this->sourceUrl)
                ->delete();
        }

        foreach ($records as $record) {
            match ($this->type) {
                'roads' => $this->saveRoad($record, $blocklist),
                'waste_sites' => $this->saveWasteSite($record, $blocklist),
                'meeting_insights' => $this->saveMeetingInsight($record, $blocklist, $meetingRelevance),
                default => null,
            };
        }

        if ($roadStates !== []) {
            RoadRegulation::where('disaster_id', $this->disasterId)
                ->where('source_url', $this->sourceUrl)
                ->get()
                ->each(function (RoadRegulation $road) use ($roadStates) {
                    $state = $roadStates[$this->roadStateKey($road->road_name, $road->section)] ?? null;

                    if ($state !== null) {
                        $road->forceFill($state)->save();
                    }
                });
        }

        app(DisasterDataSourceRecorder::class)->record(
            $this->disasterId,
            $this->type,
            $this->sourceOrg,
            $citationUrl ?: $this->sourceUrl,
            null,
            now(),
            $this->sourcePublishedAt,
            [
                'records' => count($records),
                'raw_file_path' => $this->filePath,
                'retrieval_url' => $this->sourceUrl,
                'citation_url' => $citationUrl,
                'credibility' => $this->sourceCredibility,
            ]
        );

        Log::info('災害資料をパースしました。', [
            'disaster_id' => $this->disasterId,
            'type' => $this->type,
            'records' => count($records),
            'file' => $this->filePath,
        ]);
    }

    private function roadStateKey(?string $roadName, ?string $section): string
    {
        $normalize = function (?string $value): string {
            $value = mb_convert_kana((string) $value, 'asKV');

            return (string) preg_replace('/[\s　]+/u', '', $value);
        };

        return $normalize($roadName) . '|' . $normalize($section);
    }

    /**
     * @param array<string, mixed> $record
     */
    private function saveRoad(array $record, CollectedInformationBlocklist $blocklist): void
    {
        $blockKey = CollectedInformationBlocklist::roadKey(
            $this->sourceUrl,
            (string) ($record['road_name'] ?? ''),
            $record['section'] ?? null
        );

        if ($blocklist->isBlocked($this->disasterId, CollectedInformationBlocklist::TYPE_ROAD, $blockKey)) {
            return;
        }

        RoadRegulation::updateOrCreate(
            [
                'disaster_id' => $this->disasterId,
                'source_url' => $this->sourceUrl,
                'road_name' => $record['road_name'],
                'section' => $record['section'] ?? null,
            ],
            $record + ['disaster_id' => $this->disasterId]
        );
    }

    /**
     * @param array<string, mixed> $record
     */
    private function saveWasteSite(array $record, CollectedInformationBlocklist $blocklist): void
    {
        $blockKey = CollectedInformationBlocklist::wasteSiteKey(
            $this->sourceUrl,
            (string) ($record['name'] ?? '')
        );

        if ($blocklist->isBlocked($this->disasterId, CollectedInformationBlocklist::TYPE_WASTE_SITE, $blockKey)) {
            return;
        }

        $site = WasteSite::firstOrNew([
            'disaster_id' => $this->disasterId,
            'source_url' => $this->sourceUrl,
            'name' => $record['name'],
        ]);

        if ($site->exists && $site->last_verified_at !== null) {
            unset($record['status']);
        }

        $site->fill($record + ['disaster_id' => $this->disasterId])->save();
    }

    /**
     * @param array<string, mixed> $record
     */
    private function saveMeetingInsight(
        array $record,
        CollectedInformationBlocklist $blocklist,
        MeetingRelevanceFilter $meetingRelevance
    ): void {
        $meetingText = implode("\n", [
            (string) ($record['meeting_name'] ?? ''),
            $this->sourceOrg,
            implode(' ', (array) ($record['decisions'] ?? [])),
            implode(' ', (array) ($record['policy_changes'] ?? [])),
            implode(' ', (array) ($record['unmet_needs'] ?? [])),
        ]);

        if (!$meetingRelevance->isRelevant($this->disasterId, $meetingText)) {
            return;
        }

        $blockKey = CollectedInformationBlocklist::meetingInsightKey(
            $this->sourceUrl,
            (string) ($record['meeting_name'] ?? ''),
            $record['held_at'] ?? null
        );

        if ($blocklist->isBlocked($this->disasterId, CollectedInformationBlocklist::TYPE_MEETING_INSIGHT, $blockKey)) {
            return;
        }

        MeetingInsight::updateOrCreate(
            [
                'disaster_id' => $this->disasterId,
                'source_url' => $this->sourceUrl,
                'meeting_name' => $record['meeting_name'],
                'held_at' => $record['held_at'] ?? null,
            ],
            $record + [
                'disaster_id' => $this->disasterId,
                'source_page' => $this->sourcePage,
            ]
        );
    }

    /**
     * 失敗時の記録
     */
    public function failed(Throwable $error): void
    {
        Log::error('災害資料のパースに失敗しました。', [
            'disaster_id' => $this->disasterId,
            'type' => $this->type,
            'file' => $this->filePath,
            'message' => $error->getMessage(),
        ]);
    }
}
