<?php

namespace App\Jobs;

use App\Disaster;
use App\MeetingInsight;
use App\RoadRegulation;
use App\Services\DisasterIntelligence\DisasterSourceParser;
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
        public readonly ?int $sourcePage = null
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(
        PdfTextExtractor $extractor,
        DisasterSourceParser $parser,
        \App\Services\DisasterIntelligence\RoadRecoveryVerifier $recoveryVerifier
    ): void {
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
        $text = $extractor->extract($absolutePath, $extension);

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
        ];

        $targetMunicipalities = $disaster->localGovernments()
            ->pluck('name')
            ->filter()
            ->values()
            ->all();

        $records = array_values($parser->parse($this->type, $text, $attribution, $targetMunicipalities));
        $recoveredIndexes = [];

        if ($this->type === 'roads' && $records !== []) {
            $recoveredIndexes = $recoveryVerifier->recoveredIndexes($this->disasterId, $records);

            if ($recoveredIndexes !== []) {
                $records = array_values(array_filter(
                    $records,
                    fn ($index) => !in_array($index, $recoveredIndexes, true),
                    ARRAY_FILTER_USE_KEY
                ));
            }

            RoadRegulation::where('disaster_id', $this->disasterId)
                ->where('source_url', $this->sourceUrl)
                ->delete();
        }

        foreach ($records as $record) {
            match ($this->type) {
                'roads' => $this->saveRoad($record),
                'waste_sites' => $this->saveWasteSite($record),
                'meeting_insights' => $this->saveMeetingInsight($record),
                default => null,
            };
        }

        Log::info('災害資料をパースしました。', [
            'disaster_id' => $this->disasterId,
            'type' => $this->type,
            'records' => count($records),
            'recovered' => count($recoveredIndexes),
            'file' => $this->filePath,
        ]);
    }

    /**
     * @param array<string, mixed> $record
     */
    private function saveRoad(array $record): void
    {
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
    private function saveWasteSite(array $record): void
    {
        WasteSite::updateOrCreate(
            [
                'disaster_id' => $this->disasterId,
                'source_url' => $this->sourceUrl,
                'name' => $record['name'],
            ],
            $record + ['disaster_id' => $this->disasterId]
        );
    }

    /**
     * @param array<string, mixed> $record
     */
    private function saveMeetingInsight(array $record): void
    {
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
