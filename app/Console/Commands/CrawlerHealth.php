<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use Throwable;

class CrawlerHealth extends Command
{
    /**
     * @var string
     */
    protected $signature = 'crawler:health
                            {--json : JSONで出力する}
                            {--skip-ner : GiNZAの起動確認を省略する}
                            {--strict : warningでも失敗扱いにする}';

    /**
     * @var string
     */
    protected $description = 'データ収集・キュー・GiNZAの稼働状況を確認する';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $checks = [];
        $checks[] = $this->checkScheduler();
        $checks[] = $this->checkQueue();
        $checks[] = $this->checkLastCollection();

        if (!(bool) $this->option('skip-ner')) {
            $checks[] = $this->checkNer();
        }

        $hasError = collect($checks)->contains(fn (array $check) => $check['status'] === 'error');
        $hasWarning = collect($checks)->contains(fn (array $check) => $check['status'] === 'warning');
        $failed = $hasError || ((bool) $this->option('strict') && $hasWarning);

        if ((bool) $this->option('json')) {
            $this->line(json_encode([
                'ok' => !$failed,
                'checked_at' => now()->toIso8601String(),
                'checks' => $checks,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(
                ['確認項目', '状態', '詳細'],
                array_map(fn (array $check) => [$check['name'], $check['status'], $check['detail']], $checks)
            );
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{name: string, status: string, detail: string}
     */
    private function checkScheduler(): array
    {
        $path = storage_path('logs/saiz-scheduler.log');

        if (!is_file($path)) {
            return [
                'name' => 'scheduler',
                'status' => 'warning',
                'detail' => 'ログファイルがありません: ' . $path,
            ];
        }

        $modifiedAt = filemtime($path);
        $ageMinutes = $modifiedAt === false ? null : (int) floor((time() - $modifiedAt) / 60);

        if ($ageMinutes === null) {
            return ['name' => 'scheduler', 'status' => 'warning', 'detail' => '更新日時を取得できません。'];
        }

        if ($ageMinutes > 10) {
            return [
                'name' => 'scheduler',
                'status' => 'error',
                'detail' => '最終更新から ' . $ageMinutes . ' 分経過しています。',
            ];
        }

        return [
            'name' => 'scheduler',
            'status' => 'ok',
            'detail' => '最終更新: ' . $ageMinutes . ' 分前',
        ];
    }

    /**
     * @return array{name: string, status: string, detail: string}
     */
    private function checkQueue(): array
    {
        $pending = Schema::hasTable('jobs') ? (int) DB::table('jobs')->count() : 0;
        $failed = Schema::hasTable('failed_jobs')
            ? (int) DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count()
            : 0;

        if ($failed > 0) {
            return [
                'name' => 'queue',
                'status' => 'error',
                'detail' => '直近24時間の失敗ジョブ: ' . $failed . '件 / 待機: ' . $pending . '件',
            ];
        }

        if ($pending > 100) {
            return [
                'name' => 'queue',
                'status' => 'warning',
                'detail' => '待機ジョブが多すぎます: ' . $pending . '件',
            ];
        }

        return [
            'name' => 'queue',
            'status' => 'ok',
            'detail' => '待機: ' . $pending . '件 / 直近24時間の失敗: 0件',
        ];
    }

    /**
     * @return array{name: string, status: string, detail: string}
     */
    private function checkLastCollection(): array
    {
        if (!Schema::hasTable('disaster_data_sources')) {
            return ['name' => 'collection', 'status' => 'warning', 'detail' => '出典テーブルがありません。'];
        }

        $last = DB::table('disaster_data_sources')->max('collected_at');

        if (!$last) {
            return [
                'name' => 'collection',
                'status' => 'warning',
                'detail' => '収集データがありません。',
            ];
        }

        try {
            $collectedAt = Carbon::parse((string) $last);
        } catch (Throwable) {
            return ['name' => 'collection', 'status' => 'warning', 'detail' => '収集日時を解釈できません。'];
        }

        $ageHours = (int) floor(abs(now()->diffInHours($collectedAt)));

        if ($ageHours > 24) {
            return [
                'name' => 'collection',
                'status' => 'warning',
                'detail' => '最終収集から ' . $ageHours . ' 時間経過しています。',
            ];
        }

        return [
            'name' => 'collection',
            'status' => 'ok',
            'detail' => '最終収集: ' . $collectedAt->format('Y/m/d H:i'),
        ];
    }

    /**
     * @return array{name: string, status: string, detail: string}
     */
    private function checkNer(): array
    {
        $config = (array) config('disaster-intelligence.redaction.ner', []);

        if (!(bool) ($config['enabled'] ?? false)) {
            return ['name' => 'ginza', 'status' => 'ok', 'detail' => 'NERは無効です。'];
        }

        $python = (string) ($config['python'] ?? '');
        $script = (string) ($config['script'] ?? '');

        if ($python === '' || !is_file($python)) {
            return ['name' => 'ginza', 'status' => 'error', 'detail' => 'Pythonが見つかりません: ' . $python];
        }

        if ($script === '' || !is_file($script)) {
            return ['name' => 'ginza', 'status' => 'error', 'detail' => 'NERスクリプトが見つかりません: ' . $script];
        }

        try {
            $process = new Process([$python, '-c', 'import spacy, ginza']);
            $process->setWorkingDirectory(base_path());
            $process->setTimeout(20);
            $process->run();

            if (!$process->isSuccessful()) {
                return [
                    'name' => 'ginza',
                    'status' => 'error',
                    'detail' => 'GiNZAの起動に失敗しました: ' . mb_substr(trim($process->getErrorOutput()), 0, 300),
                ];
            }
        } catch (Throwable $error) {
            return ['name' => 'ginza', 'status' => 'error', 'detail' => $error->getMessage()];
        }

        return ['name' => 'ginza', 'status' => 'ok', 'detail' => 'GiNZAを読み込めます。'];
    }
}
