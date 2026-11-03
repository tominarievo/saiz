<?php

namespace App\Console\Commands;

use App\LocalGovernment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportEvacuationShelters extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'evacuation-shelters:import
                            {code : 5桁の市町村コード（例: 16205）}';

    /**
     * コマンド説明
     *
     * @var string
     */
    protected $description = '国土地理院の指定避難所・指定緊急避難場所データを取り込む';

    /**
     * 災害種別の定義
     *
     * @var array<string, string>
     */
    private $disasterColumns = [
        '洪水' => '洪水',
        '崖崩れ、土石流及び地滑り' => '崖崩れ・土石流・地滑り',
        '高潮' => '高潮',
        '地震' => '地震',
        '津波' => '津波',
        '大規模な火事' => '大規模な火事',
        '内水氾濫' => '内水氾濫',
        '火山現象' => '火山現象',
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $inputCode = str_pad((string) $this->argument('code'), 5, '0', STR_PAD_LEFT);

        if (!preg_match('/^\d{5,6}$/', $inputCode)) {
            $this->error('市町村コードは5桁または6桁の数字で指定してください。');
            return self::FAILURE;
        }

        $code = substr($inputCode, 0, 5);
        $localGovernment = LocalGovernment::where('code', 'like', $code . '%')->first();

        if (!$localGovernment) {
            $this->error('市町村が見つかりません。');
            return self::FAILURE;
        }

        $designatedUrl = "https://hinanmap.gsi.go.jp/hinanjocp/defaultFtpData/csv/{$code}_1.csv";
        $emergencyUrl = "https://hinanmap.gsi.go.jp/hinanjocp/defaultFtpData/csv/{$code}_2.csv";

        try {
            $designatedCsv = Http::timeout(60)->get($designatedUrl)->throw()->body();
            $emergencyCsv = Http::timeout(60)->get($emergencyUrl)->throw()->body();
        } catch (\Throwable $exception) {
            $this->error('避難所データの取得に失敗しました: ' . $exception->getMessage());
            return self::FAILURE;
        }

        $shelters = array_merge(
            $this->parseDesignatedShelters($designatedCsv, $designatedUrl),
            $this->parseEmergencySites($emergencyCsv, $emergencyUrl)
        );

        DB::transaction(function () use ($localGovernment, $shelters) {
            $localGovernment->evacuationShelters()
                ->whereIn('shelter_type', ['designated_shelter', 'emergency_site'])
                ->delete();

            foreach ($shelters as $shelter) {
                $localGovernment->evacuationShelters()->create($shelter);
            }
        });

        $this->info(count($shelters) . '件の避難所・避難場所を登録しました。');

        return self::SUCCESS;
    }

    /**
     * CSVを配列に変換する
     *
     * @param string $csv
     * @return array<int, array<int, string>>
     */
    private function parseCsv(string $csv): array
    {
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv);
        $lines = preg_split('/\r\n|\n|\r/', trim($csv));
        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $rows[] = str_getcsv($line);
        }

        return $rows;
    }

    /**
     * 指定避難所を解析する
     *
     * @param string $csv
     * @param string $sourceUrl
     * @return array<int, array<string, mixed>>
     */
    private function parseDesignatedShelters(string $csv, string $sourceUrl): array
    {
        $rows = $this->parseCsv($csv);

        if (empty($rows)) {
            return [];
        }

        $header = array_shift($rows);
        $columns = array_flip($header);
        $shelters = [];
        $seen = [];

        foreach ($rows as $row) {
            $name = $this->value($row, $columns, '施設・場所名');

            if ($name === '') {
                continue;
            }

            $commonId = $this->value($row, $columns, '共通ID');
            $address = $this->value($row, $columns, '住所');
            $latitude = $this->value($row, $columns, '緯度');
            $longitude = $this->value($row, $columns, '経度');

            if (!is_numeric($latitude) || !is_numeric($longitude)) {
                continue;
            }

            $key = $commonId !== '' ? $commonId : $name . '|' . $address;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $noteParts = array_filter([
                $this->value($row, $columns, 'その他市町村長が必要と認める事項'),
                $this->value($row, $columns, '受入対象者'),
                $this->value($row, $columns, '備考'),
            ]);

            $shelters[] = [
                'common_id' => $commonId !== '' ? $commonId : null,
                'name' => $name,
                'address' => $address !== '' ? $address : null,
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
                'shelter_type' => 'designated_shelter',
                'disaster_types' => null,
                'note' => empty($noteParts) ? null : implode(' / ', $noteParts),
                'source_name' => '国土地理院 指定緊急避難場所・指定避難所データ',
                'source_url' => $sourceUrl,
                'fetched_at' => now(),
            ];
        }

        return $shelters;
    }

    /**
     * 指定緊急避難場所を解析する
     *
     * @param string $csv
     * @param string $sourceUrl
     * @return array<int, array<string, mixed>>
     */
    private function parseEmergencySites(string $csv, string $sourceUrl): array
    {
        $rows = $this->parseCsv($csv);

        if (empty($rows)) {
            return [];
        }

        $header = array_shift($rows);
        $columns = array_flip($header);
        $shelters = [];
        $seen = [];

        foreach ($rows as $row) {
            $name = $this->value($row, $columns, '施設・場所名');

            if ($name === '') {
                continue;
            }

            $commonId = $this->value($row, $columns, '共通ID');
            $address = $this->value($row, $columns, '住所');
            $latitude = $this->value($row, $columns, '緯度');
            $longitude = $this->value($row, $columns, '経度');

            if (!is_numeric($latitude) || !is_numeric($longitude)) {
                continue;
            }

            $key = $commonId !== '' ? $commonId : $name . '|' . $address;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $disasterTypes = [];

            foreach ($this->disasterColumns as $column => $label) {
                if ($this->value($row, $columns, $column) === '1') {
                    $disasterTypes[] = $label;
                }
            }

            $note = $this->value($row, $columns, '備考');

            $shelters[] = [
                'common_id' => $commonId !== '' ? $commonId : null,
                'name' => $name,
                'address' => $address !== '' ? $address : null,
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
                'shelter_type' => 'emergency_site',
                'disaster_types' => empty($disasterTypes) ? null : $disasterTypes,
                'note' => $note !== '' ? $note : null,
                'source_name' => '国土地理院 指定緊急避難場所・指定避難所データ',
                'source_url' => $sourceUrl,
                'fetched_at' => now(),
            ];
        }

        return $shelters;
    }

    /**
     * 行から指定列の値を取得する
     *
     * @param array<int, string> $row
     * @param array<string, int> $columns
     * @param string $name
     * @return string
     */
    private function value(array $row, array $columns, string $name): string
    {
        if (!isset($columns[$name])) {
            return '';
        }

        return trim((string) ($row[$columns[$name]] ?? ''));
    }
}
