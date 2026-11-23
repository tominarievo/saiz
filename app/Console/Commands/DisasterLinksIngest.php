<?php

namespace App\Console\Commands;

use App\Disaster;
use App\Services\CollectedInformationBlocklist;
use App\Services\DisasterDataSource\DisasterDataSourceRecorder;
use App\Services\DisasterIntelligence\SourceUrlGuard;
use App\Support\DisasterDataSourceCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DisasterLinksIngest extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'disaster-links:ingest
                            {disaster : 災害ID}
                            {--file= : リンクJSONを読み込むファイル}
                            {--json= : リンクJSONを直接指定する}';

    /**
     * コマンド説明
     *
     * @var string
     */
    protected $description = 'AIが収集した最新情報リンクを災害に登録する';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(SourceUrlGuard $guard, CollectedInformationBlocklist $blocklist)
    {
        $disaster = Disaster::find($this->argument('disaster'));

        if (! $disaster) {
            $this->error('災害が見つかりません。');
            return self::FAILURE;
        }

        $json = $this->option('json');

        if ($json === null && $this->option('file')) {
            $path = $this->option('file');

            if (! File::exists($path)) {
                $this->error('指定されたファイルが見つかりません。');
                return self::FAILURE;
            }

            $json = File::get($path);
        }

        if ($json === null) {
            $json = stream_get_contents(STDIN);
        }

        $decoded = json_decode((string) $json, true);

        if (! is_array($decoded)) {
            $this->error('JSONの形式が正しくありません。');
            return self::FAILURE;
        }

        $items = isset($decoded['links']) && is_array($decoded['links'])
            ? $decoded['links']
            : $decoded;

        $manualUrls = $disaster->informationLinks()
            ->where('source', 'manual')
            ->pluck('url')
            ->all();

        $links = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $description = trim((string) ($item['description'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));

            if ($description === '' || $url === '') {
                continue;
            }

            if (mb_strlen($description) > 1000) {
                $description = mb_substr($description, 0, 1000);
            }

            if (mb_strlen($url) > 2048) {
                continue;
            }

            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

            if (! in_array($scheme, ['http', 'https'], true)) {
                continue;
            }

            if ($blocklist->isUrlBlocked($disaster->id, $url)) {
                continue;
            }

            try {
                $guard->assertAllowed($url, false);
            } catch (\Throwable $error) {
                continue;
            }

            $sourcePageUrl = $this->validSourcePageUrl(
                trim((string) ($item['source_page_url'] ?? '')),
                $guard
            );

            if ($sourcePageUrl !== '' && $blocklist->isUrlBlocked($disaster->id, $sourcePageUrl)) {
                $sourcePageUrl = '';
            }

            if ($sourcePageUrl !== '' && preg_match('/\.pdf($|\?)/i', $url)) {
                $url = $sourcePageUrl;
            }

            if (in_array($url, $manualUrls, true)) {
                continue;
            }

            $links[$url] = [
                'description' => $description,
                'url' => $url,
            ];

            if (count($links) >= 10) {
                break;
            }
        }

        if (empty($links)) {
            $this->error('登録できる有効なリンクがありません。');
            return self::FAILURE;
        }

        DB::transaction(function () use ($disaster, $links) {
            $disaster->informationLinks()->where('source', 'ai')->delete();

            $recorder = app(DisasterDataSourceRecorder::class);
            $collectedAt = now();

            foreach ($links as $link) {
                $disaster->informationLinks()->create([
                    'description' => $link['description'],
                    'url' => $link['url'],
                    'source' => 'ai',
                    'fetched_at' => $collectedAt,
                ]);

                $host = parse_url($link['url'], PHP_URL_HOST);

                $recorder->record(
                    $disaster->id,
                    DisasterDataSourceCategory::INFORMATION,
                    $host ?: 'AI収集',
                    $link['url'],
                    $link['description'],
                    $collectedAt
                );
            }
        });

        $this->info(count($links) . '件の最新情報リンクを更新しました。');

        return self::SUCCESS;
    }

    private function validSourcePageUrl(string $url, SourceUrlGuard $guard): string
    {
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        if (!in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return '';
        }

        try {
            $guard->assertAllowed($url, false);
        } catch (\Throwable $error) {
            return '';
        }

        return $url;
    }
}
