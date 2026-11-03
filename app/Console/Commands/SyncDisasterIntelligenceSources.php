<?php

namespace App\Console\Commands;

use App\Disaster;
use App\Services\DisasterIntelligence\SourceSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncDisasterIntelligenceSources extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'disaster-intelligence:sync
                            {disaster : 災害ID}
                            {--source= : roads / waste_sites / meeting_insights で絞り込み}
                            {--url= : 一時的に取得するURL}
                            {--type=roads : --url指定時の資料種別}
                            {--org=不明 : --url指定時の発信組織}
                            {--published-at= : 公表日時（ISO8601）}
                            {--page= : 該当ページ番号}
                            {--force : 更新検知を無視して取得}';

    /**
     * コマンド説明
     *
     * @var string
     */
    protected $description = '災害資料の更新をHEADで検知し、PDFを保存して非同期パースジョブを投入する';

    /**
     * Execute the console command.
     */
    public function handle(SourceSyncService $syncService): int
    {
        $disaster = Disaster::find($this->argument('disaster'));

        if (!$disaster) {
            $this->error('災害が見つかりません。');
            return self::FAILURE;
        }

        $sources = config('disaster-intelligence.sources.' . $disaster->id, []);
        $url = $this->option('url');

        if ($url) {
            $type = (string) $this->option('type');

            if (!in_array($type, ['roads', 'waste_sites', 'meeting_insights'], true)) {
                $this->error('typeは roads / waste_sites / meeting_insights のいずれかです。');
                return self::FAILURE;
            }

            $sources = [[
                'type' => $type,
                'url' => $url,
                'source_org' => (string) $this->option('org'),
                'published_at' => $this->option('published-at'),
                'source_page' => $this->option('page') !== null ? (int) $this->option('page') : null,
            ]];
        }

        $sourceFilter = $this->option('source');

        if ($sourceFilter) {
            $sources = array_values(array_filter($sources, function ($source) use ($sourceFilter) {
                return ($source['type'] ?? '') === $sourceFilter;
            }));
        }

        if ($sources === []) {
            $this->warn('同期対象がありません。config/disaster-intelligence.php の sources か --url を指定してください。');
            return self::SUCCESS;
        }

        $failed = 0;

        foreach ($sources as $source) {
            $url = (string) ($source['url'] ?? '');
            $type = (string) ($source['type'] ?? 'roads');

            try {
                $path = $syncService->sync($disaster->id, $source, (bool) $this->option('force'));

                if ($path === null) {
                    $this->line('更新なし: ' . $url);
                } else {
                    $this->info('取得・ジョブ投入: ' . $type . ' / ' . $path);
                }
            } catch (Throwable $error) {
                $failed++;
                $this->error('同期失敗: ' . $url . ' / ' . $error->getMessage());
            }
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
