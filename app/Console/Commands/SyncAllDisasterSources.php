<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncAllDisasterSources extends Command
{
    /**
     * @var string
     */
    protected $signature = 'disaster-intelligence:sync-all
                            {--force : 更新検知を無視して取得}
                            {--source= : roads / waste_sites / meeting_insights で絞り込み}';

    /**
     * @var string
     */
    protected $description = '設定済み災害の収集元をまとめて更新確認し、変更があれば解析する';

    public function handle(): int
    {
        $sourceFilter = $this->option('source');

        if ($sourceFilter !== null && !in_array($sourceFilter, ['roads', 'waste_sites', 'meeting_insights'], true)) {
            $this->error('sourceは roads / waste_sites / meeting_insights のいずれかです。');

            return self::FAILURE;
        }

        $ids = array_unique(array_merge(
            array_keys((array) config('disaster-intelligence.sources', [])),
            array_keys((array) config('disaster-intelligence.recovery_sources', []))
        ));
        sort($ids);

        if ($ids === []) {
            $this->warn('同期対象の災害が設定されていません。');

            return self::SUCCESS;
        }

        $failed = 0;

        foreach ($ids as $id) {
            $arguments = ['disaster' => $id];

            if ((bool) $this->option('force')) {
                $arguments['--force'] = true;
            }

            if ($sourceFilter !== null && $sourceFilter !== '') {
                $arguments['--source'] = $sourceFilter;
            }

            $this->line("災害ID {$id}: 同期を開始します。");

            if ($this->call('disaster-intelligence:sync', $arguments) !== self::SUCCESS) {
                $failed++;
            }
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
