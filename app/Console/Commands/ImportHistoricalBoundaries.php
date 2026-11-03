<?php

namespace App\Console\Commands;

use App\HistoricalBoundary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class ImportHistoricalBoundaries extends Command
{
    /**
     * @var string
     */
    protected $signature = 'historical-boundaries:import
                            {prefecture : 2桁の都道府県コード（例: 16）}
                            {--resolution=h : TopoJSONの解像度（h/i/l/f）}
                            {--force : 既存データを上書きする}
                            {--source-dir= : ダウンロード済みTopoJSONを配置したディレクトリ（日付/ファイル名）}';

    /**
     * @var string
     */
    protected $description = '歴史的行政区域データセットβ版の合併前行政界をDBに取り込む';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $prefecture = str_pad((string) $this->argument('prefecture'), 2, '0', STR_PAD_LEFT);

        if (!preg_match('/^\d{2}$/', $prefecture)) {
            $this->error('都道府県コードは2桁の数字で指定してください。');
            return self::FAILURE;
        }

        $resolution = (string) $this->option('resolution');

        if (!in_array($resolution, ['h', 'i', 'l', 'f'], true)) {
            $this->error('解像度は h / i / l / f のいずれかを指定してください。');
            return self::FAILURE;
        }

        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $workDirectory = storage_path('app/historical-boundaries-tmp');
        File::ensureDirectoryExists($workDirectory);

        $pageUrl = "https://geoshape.ex.nii.ac.jp/city/choropleth/{$prefecture}_city.html";
        $pagePath = "{$workDirectory}/{$prefecture}.html";

        $this->info("{$prefecture}: 配布一覧を取得中...");

        try {
            $this->download($pageUrl, $pagePath);
            $html = File::get($pagePath);

            if (!preg_match('/var list = (\[.*?\]);/s', $html, $matches)) {
                throw new \RuntimeException('TopoJSON一覧を解析できませんでした。');
            }

            $list = json_decode($matches[1], true);

            if (!is_array($list)) {
                throw new \RuntimeException('TopoJSON一覧のJSONが不正です。');
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $items = [];

        foreach ($list as $item) {
            $date = $item['key'] ?? null;
            $path = $item['data'][$resolution] ?? null;

            if (!is_string($date) || !preg_match('/^\d{8}$/', $date) || !is_string($path)) {
                continue;
            }

            $items[] = [
                'date' => $date,
                'boundary_date' => substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2),
                'url' => 'https://geoshape.ex.nii.ac.jp' . $path,
            ];
        }

        $items = collect($items)->sortBy('date')->values();

        if ($items->isEmpty()) {
            $this->error('対象データがありません。');
            return self::FAILURE;
        }

        $this->output->progressStart($items->count());
        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $sourceDirectory = $this->option('source-dir');

        foreach ($items as $item) {
            $boundaryDate = $item['boundary_date'];

            if (!$this->option('force') && HistoricalBoundary::where([
                'prefecture_code' => $prefecture,
                'boundary_date' => $boundaryDate,
                'resolution' => $resolution,
            ])->exists()) {
                $skipped++;
                $this->output->progressAdvance();
                continue;
            }

            $fileName = "{$prefecture}_city.{$resolution}.topojson";
            $sourcePath = $sourceDirectory
                ? rtrim($sourceDirectory, '/\\') . "/{$item['date']}/{$fileName}"
                : null;
            $localPath = "{$workDirectory}/{$item['date']}_{$prefecture}_{$resolution}.topojson";

            try {
                File::delete($localPath);

                if ($sourcePath && File::exists($sourcePath)) {
                    File::copy($sourcePath, $localPath);
                } else {
                    $this->download($item['url'], $localPath);
                }

                $topology = json_decode(File::get($localPath), true);

                if (!is_array($topology) || ($topology['type'] ?? null) !== 'Topology') {
                    throw new \RuntimeException("TopoJSONが不正です: {$item['date']}");
                }

                $geometries = $topology['objects']['city']['geometries'] ?? [];
                $firstProperties = $geometries[0]['properties'] ?? [];
                $prefectureName = $firstProperties['N03_001'] ?? "都道府県{$prefecture}";
                $license = $topology['metadata']['cc:license'] ?? 'CC BY-SA 4.0';
                $bbox = $topology['bbox'] ?? null;

                HistoricalBoundary::updateOrCreate(
                    [
                        'prefecture_code' => $prefecture,
                        'boundary_date' => $boundaryDate,
                        'resolution' => $resolution,
                    ],
                    [
                        'prefecture_name' => $prefectureName,
                        'source' => '歴史的行政区域データセットβ版（CODH）/ 国土数値情報',
                        'license' => $license,
                        'bbox' => $bbox,
                        'topojson' => $topology,
                        'feature_count' => count($geometries),
                    ]
                );

                $imported++;
            } catch (\Throwable $e) {
                $failed++;
                $this->newLine();
                $this->error("{$boundaryDate}: {$e->getMessage()}");
            } finally {
                File::delete($localPath);
                $this->output->progressAdvance();
            }
        }

        $this->output->progressFinish();
        File::delete($pagePath);

        $this->info("{$prefecture}: 取込 {$imported} / スキップ {$skipped} / 失敗 {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * URLをファイルへダウンロードする。
     *
     * @param string $url
     * @param string $path
     * @return void
     */
    private function download(string $url, string $path): void
    {
        File::ensureDirectoryExists(dirname($path));

        $process = new Process([
            'curl',
            '-L',
            '--fail',
            '--silent',
            '--show-error',
            '--max-time',
            '180',
            '-o',
            $path,
            $url,
        ]);
        $process->setTimeout(200)->mustRun();
    }
}
