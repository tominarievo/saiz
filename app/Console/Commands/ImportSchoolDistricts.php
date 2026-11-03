<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class ImportSchoolDistricts extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'school-districts:import
                            {prefecture : 2桁の都道府県コード（例: 13）}
                            {--force : 既存データを上書きする}
                            {--source-dir= : ダウンロード済みZIPを配置したディレクトリ（オフライン取込用）}';

    /**
     * コマンド説明
     *
     * @var string
     */
    protected $description = '国土数値情報 A27/A32 の学区データを都道府県単位で取り込む';

    /**
     * 取り込むデータセット
     *
     * @var array
     */
    private $datasets = [
        'A27' => 'A27-23',
        'A32' => 'A32-23',
    ];

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

        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $workRoot = storage_path('app/import-school-districts');
        File::ensureDirectoryExists($workRoot);

        foreach ($this->datasets as $code => $version) {
            $targetDirectory = "school-districts/{$code}/{$prefecture}";
            $metadataPath = "{$targetDirectory}/metadata.json";

            if (!$this->option('force') && Storage::disk('local')->exists($metadataPath)) {
                $this->info("{$code} {$prefecture}: 取込済みのためスキップしました（--force で再取込）。");
                continue;
            }

            $this->info("{$code} {$prefecture}: 取込中...");

            $zipPath = "{$workRoot}/{$version}_{$prefecture}.zip";
            $extractDirectory = "{$workRoot}/{$version}_{$prefecture}";
            $url = "https://nlftp.mlit.go.jp/ksj/gml/data/{$code}/{$version}/{$version}_{$prefecture}_GML.zip";

            $sourceDirectory = $this->option('source-dir');
            $sourceZip = $sourceDirectory
                ? rtrim($sourceDirectory, '/\\') . "/{$version}_{$prefecture}.zip"
                : null;

            try {
                File::delete($zipPath);

                if ($sourceZip && File::exists($sourceZip)) {
                    $this->info("{$code} {$prefecture}: ローカルZIPを使用します。");
                    File::copy($sourceZip, $zipPath);
                } else {
                    try {
                        $response = Http::timeout(600)
                            ->withOptions(['sink' => $zipPath])
                            ->get($url);

                        $response->throw();
                    } catch (\Throwable $e) {
                        File::delete($zipPath);

                        $curl = new Process([
                            'curl',
                            '-L',
                            '--fail',
                            '--silent',
                            '--show-error',
                            '--max-time',
                            '600',
                            '-o',
                            $zipPath,
                            $url,
                        ]);
                        $curl->setTimeout(620)->mustRun();
                    }
                }

                if (!File::exists($zipPath)) {
                    throw new \RuntimeException("ZIPを取得できませんでした: {$url}");
                }

                File::deleteDirectory($extractDirectory);
                File::ensureDirectoryExists($extractDirectory);

                $process = new Process([
                    'unzip',
                    '-o',
                    '-q',
                    $zipPath,
                    '-d',
                    $extractDirectory,
                ]);
                $process->setTimeout(600)->mustRun();

                $geojsonFile = collect(File::allFiles($extractDirectory))
                    ->first(function ($file) {
                        return strtolower($file->getExtension()) === 'geojson';
                    });

                if (!$geojsonFile) {
                    throw new \RuntimeException('ZIP内にGeoJSONが見つかりませんでした。');
                }

                $data = json_decode(File::get($geojsonFile->getPathname()), true);

                if (!is_array($data) || !isset($data['features']) || !is_array($data['features'])) {
                    throw new \RuntimeException('GeoJSONの形式が不正です。');
                }

                $groups = [];

                foreach ($data['features'] as $feature) {
                    $adminCode = $feature['properties']["{$code}_001"] ?? null;

                    if (!is_string($adminCode) || !preg_match('/^\d{5}$/', $adminCode)) {
                        continue;
                    }

                    $groups[$adminCode][] = $feature;
                }

                if (empty($groups)) {
                    throw new \RuntimeException('市区町村単位の地物がありませんでした。');
                }

                Storage::disk('local')->deleteDirectory($targetDirectory);
                File::ensureDirectoryExists(storage_path('app/' . $targetDirectory));

                foreach ($groups as $adminCode => $features) {
                    $payload = [
                        'type' => 'FeatureCollection',
                        'features' => $features,
                    ];

                    Storage::disk('local')->put(
                        "{$targetDirectory}/{$adminCode}.json",
                        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    );
                }

                Storage::disk('local')->put(
                    $metadataPath,
                    json_encode([
                        'code' => $code,
                        'prefecture' => $prefecture,
                        'year' => '2023',
                        'source' => "国土数値情報（{$code}）",
                        'source_url' => $url,
                        'license' => 'CC BY 4.0（一部自治体は利用条件あり）',
                        'imported_at' => now()->toIso8601String(),
                        'municipality_count' => count($groups),
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                );

                $this->info("{$code} {$prefecture}: " . count($groups) . ' 市区町村を取込みました。');
            } finally {
                File::delete($zipPath);
                File::deleteDirectory($extractDirectory);
            }
        }

        return self::SUCCESS;
    }
}
