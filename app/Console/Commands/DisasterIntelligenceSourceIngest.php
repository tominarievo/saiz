<?php

namespace App\Console\Commands;

use App\Disaster;
use App\DisasterIntelligenceSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

class DisasterIntelligenceSourceIngest extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'disaster-intelligence:source-ingest
                            {disaster : 災害ID}
                            {--file= : AIが返した収集元JSONファイル}
                            {--json= : 収集元JSONを直接指定する}';

    /**
     * コマンド説明
     *
     * @var string
     */
    protected $description = 'AIが探索した災害インテリジェンスの収集元を登録する';

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

        $json = $this->option('json');

        if ($json === null && $this->option('file')) {
            $path = $this->option('file');

            if (!File::exists($path)) {
                $this->error('指定されたファイルが見つかりません。');
                return self::FAILURE;
            }

            $json = File::get($path);
        }

        if ($json === null) {
            $json = stream_get_contents(STDIN);
        }

        $data = json_decode((string) $json, true);

        if (!is_array($data)) {
            $this->error('JSONの形式が正しくありません。');
            return self::FAILURE;
        }

        $sources = $data['sources'] ?? $data;

        if (!is_array($sources)) {
            $this->error('sources配列が見つかりません。');
            return self::FAILURE;
        }

        $saved = 0;
        $skipped = 0;

        foreach ($sources as $source) {
            if (!is_array($source)) {
                $skipped++;
                continue;
            }

            $type = (string) ($source['type'] ?? '');

            if (!in_array($type, ['roads', 'waste_sites', 'meeting_insights'], true)) {
                $skipped++;
                continue;
            }

            $url = trim((string) ($source['url'] ?? ''));

            if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
                $skipped++;
                continue;
            }

            $sourceOrg = trim((string) ($source['source_org'] ?? ''));

            if ($sourceOrg === '') {
                $skipped++;
                continue;
            }

            $aiRank = $this->normalizeRank($source['credibility_rank'] ?? 'C');
            $domainRank = $this->domainRank($url);
            $rank = $this->worseRank($aiRank, $domainRank);
            $publishedAt = $this->nullableDate($source['published_at'] ?? null);

            DisasterIntelligenceSource::updateOrCreate(
                [
                    'disaster_id' => $disaster->id,
                    'type' => $type,
                    'url_hash' => sha1($url),
                ],
                [
                    'url' => $url,
                    'source_org' => $sourceOrg,
                    'published_at' => $publishedAt,
                    'credibility_rank' => $rank,
                    'credibility_reason' => trim((string) ($source['credibility_reason'] ?? '')) ?: null,
                    'status' => 'active',
                    'discovered_at' => now(),
                    'metadata' => [
                        'title' => $source['title'] ?? null,
                        'domain' => parse_url($url, PHP_URL_HOST),
                        'ai_rank' => $aiRank,
                        'domain_rank' => $domainRank,
                    ],
                ]
            );

            $saved++;
        }

        $this->info("収集元を保存しました（保存: {$saved}件 / スキップ: {$skipped}件）。");

        return self::SUCCESS;
    }

    private function normalizeRank(mixed $value): string
    {
        $value = strtoupper(trim((string) $value));

        return in_array($value, ['A', 'B', 'C'], true) ? $value : 'C';
    }

    private function domainRank(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host) ?? $host;

        if (str_ends_with($host, '.go.jp') || str_ends_with($host, '.lg.jp')) {
            return 'A';
        }

        if (preg_match('/^(city|town|vill|pref)\./', $host) && str_ends_with($host, '.jp')) {
            return 'A';
        }

        if (str_ends_with($host, '.or.jp') || in_array($host, ['jvoad.jp', 'kvoad.com'], true)) {
            return 'B';
        }

        return 'C';
    }

    private function worseRank(string $left, string $right): string
    {
        $order = ['A' => 1, 'B' => 2, 'C' => 3];

        return $order[$left] >= $order[$right] ? $left : $right;
    }

    private function nullableDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse((string) $value)->toDateTimeString();
        } catch (Throwable) {
            return null;
        }
    }
}
