<?php

namespace App\Console\Commands;

use App\Disaster;
use App\RoadRegulation;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportKumamotoTraffic extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'disaster-intelligence:import-kumamoto-traffic
                            {disaster=6 : 災害ID}';

    /**
     * コマンド説明
     *
     * @var string
     */
    protected $description = '防災情報くまもとの交通規制JSONを道路規制として取り込む';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $disaster = Disaster::find($this->argument('disaster'));

        if (!$disaster) {
            $this->error('災害が見つかりません。');
            return self::FAILURE;
        }

        $feed = config('disaster-intelligence.traffic_feeds.' . $disaster->id);

        if (!is_array($feed) || empty($feed['url'])) {
            $this->error('交通規制フィードが設定されていません。');
            return self::FAILURE;
        }

        try {
            $response = Http::timeout(60)->get((string) $feed['url'])->throw();
        } catch (\Throwable $error) {
            $this->error('交通規制JSONの取得に失敗しました: ' . $error->getMessage());
            return self::FAILURE;
        }

        $items = $response->json('items');

        if (!is_array($items)) {
            $this->error('交通規制JSONの形式が不正です。');
            return self::FAILURE;
        }

        $eventDate = Carbon::parse($disaster->getRawOriginal('event_date'))->toDateString();
        $sourcePage = (string) ($feed['source_page'] ?? $feed['url']);
        $sourceOrg = (string) ($feed['source_org'] ?? '熊本県');
        $vehicleKeys = array_keys(config('disaster-intelligence.vehicles', []));
        $now = now();
        $roads = [];

        foreach ($items as $item) {
            if (!is_array($item) || ($item['regContent0'] ?? '') === '解除') {
                continue;
            }

            $startedAt = (string) ($item['regStartTimestamp'] ?? '');

            if ($startedAt === '' || substr($startedAt, 0, 10) < $eventDate) {
                continue;
            }

            $start = $this->point($item, 'regStartPointLat', 'regStartPointLng');
            $end = $this->point($item, 'regEndPointLat', 'regEndPointLng');
            $geometry = $this->geometry($start, $end);

            if ($geometry === null) {
                continue;
            }

            $content = (string) ($item['regContent0'] ?? '');
            $reason = trim((string) ($item['regReason'] ?? ''));
            $type = 'other';

            if (str_contains($content, '全面')) {
                $type = 'full_closure';
            } elseif (str_contains($content, '片側')) {
                $type = 'alternating';
            } elseif (str_contains($content, '車両')) {
                $type = 'large_vehicle';
            }

            if (preg_match('/(2t|2ｔ|4t|4ｔ)/u', $reason)) {
                $type = 'large_vehicle';
            }

            $vehicles = $type === 'large_vehicle'
                ? array_values(array_intersect(['4t', 'large'], $vehicleKeys))
                : ($type === 'other' ? [] : $vehicleKeys);

            $roads[] = [
                'disaster_id' => $disaster->id,
                'road_name' => (string) ($item['routeName'] ?? '道路名不明'),
                'section' => trim(
                    (string) ($item['regStartPointName'] ?? '') . ' 〜 ' . (string) ($item['regEndPointName'] ?? ''),
                    ' 〜'
                ),
                'regulation_type' => $type,
                'vehicle_classes' => $vehicles,
                'reason' => $reason !== '' ? $reason : ((string) ($item['regPlanedEndComment'] ?? '') ?: null),
                'geometry' => $geometry,
                'confidence_rank' => 'A',
                'source_org' => $sourceOrg,
                'source_url' => $sourcePage,
                'source_published_at' => Carbon::parse($startedAt)->toDateTimeString(),
                'source_credibility' => 'A',
                'source_credibility_reason' => '熊本県公式の交通規制JSON',
                'scraped_at' => $now,
                'metadata' => $item,
            ];
        }

        DB::transaction(function () use ($disaster, $sourcePage, $roads) {
            RoadRegulation::where('disaster_id', $disaster->id)
                ->where('source_url', $sourcePage)
                ->delete();

            foreach ($roads as $road) {
                RoadRegulation::create($road);
            }
        });

        $this->info(count($roads) . '件の道路規制を取り込みました。');

        return self::SUCCESS;
    }

    /**
     * @return array{0: float, 1: float}|null
     */
    private function point(array $item, string $latitudeKey, string $longitudeKey): ?array
    {
        $latitude = $item[$latitudeKey] ?? null;
        $longitude = $item[$longitudeKey] ?? null;

        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return null;
        }

        return [(float) $longitude, (float) $latitude];
    }

    /**
     * @param array{0: float, 1: float}|null $start
     * @param array{0: float, 1: float}|null $end
     * @return array<string, mixed>|null
     */
    private function geometry(?array $start, ?array $end): ?array
    {
        if ($start === null && $end === null) {
            return null;
        }

        if ($start !== null && $end !== null && $start !== $end) {
            return [
                'type' => 'LineString',
                'coordinates' => [$start, $end],
            ];
        }

        return [
            'type' => 'Point',
            'coordinates' => $start ?? $end,
        ];
    }
}
