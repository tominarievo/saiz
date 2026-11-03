<?php

namespace App\Console\Commands;

use App\EvacuationShelter;
use App\LocalGovernment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ShelterStatusIngest extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'shelter-status:ingest
                            {admin_code : 5桁の行政コード}
                            {--file= : 開設状況JSONを読み込むファイル}
                            {--json= : 開設状況JSONを直接指定する}';

    /**
     * コマンド説明
     *
     * @var string
     */
    protected $description = 'AIが取得した避難所の開設状況を登録する';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $adminCode = (string) $this->argument('admin_code');
        $config = config("shelter-status-sources.{$adminCode}");

        if (!is_array($config)) {
            $this->error('対象市町村の設定が見つかりません。');
            return self::FAILURE;
        }

        $localGovernmentIds = LocalGovernment::where('code', 'like', $adminCode . '%')
            ->pluck('id');

        if ($localGovernmentIds->isEmpty()) {
            $this->error('対象市町村が見つかりません。');
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

        $status = strtolower(trim((string) ($data['status'] ?? 'unknown')));

        if (!in_array($status, ['open', 'closed', 'unknown'], true)) {
            $this->error('statusはopen、closed、unknownのいずれかで指定してください。');
            return self::FAILURE;
        }

        $firstSource = $config['sources'][0] ?? [];
        $sourceName = trim((string) ($data['source_name'] ?? ($firstSource['name'] ?? '')));
        $sourceUrl = trim((string) ($data['source_url'] ?? ($firstSource['url'] ?? '')));

        if ($sourceName === '') {
            $sourceName = '自治体防災情報';
        }

        if ($sourceUrl !== '' && (!filter_var($sourceUrl, FILTER_VALIDATE_URL) || mb_strlen($sourceUrl) > 2048)) {
            $sourceUrl = '';
        }

        $shelters = EvacuationShelter::whereIn('local_government_id', $localGovernmentIds)
            ->get()
            ->keyBy('id');

        if ($shelters->isEmpty()) {
            $this->error('対象市町村の避難所が登録されていません。');
            return self::FAILURE;
        }

        $now = now();

        if ($status === 'unknown') {
            foreach ($shelters as $shelter) {
                $shelter->status_source_name = $sourceName;
                $shelter->status_source_url = $sourceUrl !== '' ? $sourceUrl : null;
                $shelter->status_checked_at = $now;
                $shelter->save();
            }

            $this->info('避難所の開設状況は不明のため、前回の状態を保持しました。');
            return self::SUCCESS;
        }

        if ($status === 'closed') {
            $activeCount = 0;

            foreach ($shelters as $shelter) {
                if ($shelter->is_active) {
                    $activeCount++;
                    $shelter->status_changed_at = $now;
                }

                $shelter->is_active = false;
                $shelter->activity_status = 'closed';
                $shelter->evacuees = null;
                $shelter->status_note = null;
                $shelter->status_source_name = $sourceName;
                $shelter->status_source_url = $sourceUrl !== '' ? $sourceUrl : null;
                $shelter->status_checked_at = $now;
                $shelter->save();
            }

            $this->info("避難所を全て閉鎖として更新しました（開設中から変更: {$activeCount}件）。");
            return self::SUCCESS;
        }

        $activeItems = $data['active'] ?? null;

        if (!is_array($activeItems) || count($activeItems) === 0) {
            $this->error('statusがopenの場合、activeに開設中の避難所を1件以上指定してください。');
            return self::FAILURE;
        }

        $aliases = is_array($config['aliases'] ?? null) ? $config['aliases'] : [];
        $activeIds = [];
        $unmatched = [];

        foreach ($activeItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $shelter = $this->findShelter($shelters, $item, $aliases);

            if (!$shelter) {
                $label = trim((string) ($item['name'] ?? ''));
                $unmatched[] = $label !== '' ? $label : 'ID不明';
                continue;
            }

            $activeIds[$shelter->id] = true;
            $wasActive = $shelter->is_active;

            $shelter->is_active = true;
            $shelter->activity_status = 'open';
            $shelter->evacuees = $this->normalizeEvacuees($item['evacuees'] ?? null);
            $shelter->status_note = $this->normalizeNote($item['note'] ?? null);
            $shelter->status_source_name = $sourceName;
            $shelter->status_source_url = $sourceUrl !== '' ? $sourceUrl : null;
            $shelter->status_checked_at = $now;

            if (!$wasActive) {
                $shelter->status_changed_at = $now;
            }

            $shelter->save();
        }

        foreach ($shelters as $shelter) {
            if (isset($activeIds[$shelter->id])) {
                continue;
            }

            if ($shelter->is_active) {
                $shelter->status_changed_at = $now;
            }

            $shelter->is_active = false;
            $shelter->activity_status = 'closed';
            $shelter->evacuees = null;
            $shelter->status_note = null;
            $shelter->status_source_name = $sourceName;
            $shelter->status_source_url = $sourceUrl !== '' ? $sourceUrl : null;
            $shelter->status_checked_at = $now;
            $shelter->save();
        }

        $message = '避難所の開設状況を更新しました（開設中: ' . count($activeIds) . '件）。';
        $this->info($message);

        if ($unmatched !== []) {
            $this->warn('候補避難所と一致しなかった名称: ' . implode('、', array_slice($unmatched, 0, 20)));
        }

        return self::SUCCESS;
    }

    /**
     * 開設中の避難所をIDまたは名称で探す。
     */
    private function findShelter($shelters, array $item, array $aliases): ?EvacuationShelter
    {
        $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);

        if ($id !== false && $shelters->has($id)) {
            return $shelters->get($id);
        }

        $name = trim((string) ($item['name'] ?? ''));

        if ($name === '') {
            return null;
        }

        $normalized = $this->normalizeName($name);

        foreach ($shelters as $shelter) {
            if ($this->normalizeName($shelter->name) === $normalized) {
                return $shelter;
            }
        }

        foreach ($aliases as $alias => $canonical) {
            if ($this->normalizeName($alias) === $normalized) {
                $target = $this->normalizeName((string) $canonical);

                foreach ($shelters as $shelter) {
                    $candidate = $this->normalizeName($shelter->name);

                    if ($candidate === $target || mb_strpos($candidate, $target) !== false) {
                        return $shelter;
                    }
                }
            }
        }

        $matches = $shelters->filter(function (EvacuationShelter $shelter) use ($normalized) {
            $candidate = $this->normalizeName($shelter->name);

            return mb_strlen($normalized) >= 3
                && (mb_strpos($candidate, $normalized) !== false || mb_strpos($normalized, $candidate) !== false);
        });

        return $matches->count() === 1 ? $matches->first() : null;
    }

    /**
     * 名称比較用の正規化。
     */
    private function normalizeName(string $value): string
    {
        $value = mb_convert_kana($value, 'asKV');
        $value = preg_replace('/[\s　]+/u', '', $value) ?? $value;
        $value = str_replace(['（', '）', '(', ')', '【', '】', '［', '］'], '', $value);
        $value = str_replace(['高岡市立', '氷見市立', '富山市立', '富山県立'], '', $value);
        $value = str_replace(['義務教育学校', '小中学校', '小学校'], '小', $value);
        $value = str_replace('中学校', '中', $value);
        $value = str_replace('避難所', '', $value);

        return $value;
    }

    /**
     * 避難者数を整数に正規化する。
     */
    private function normalizeEvacuees($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return max(0, (int) $value);
    }

    /**
     * 特記事項を正規化する。
     */
    private function normalizeNote($value): ?string
    {
        $note = trim((string) $value);

        if ($note === '') {
            return null;
        }

        return mb_substr($note, 0, 1000);
    }
}
