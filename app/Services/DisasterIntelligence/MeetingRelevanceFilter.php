<?php

namespace App\Services\DisasterIntelligence;

use App\Disaster;

/**
 * 議事録が対象災害のものかを判定する。
 *
 * 全国団体のページのように複数災害の会議が並ぶ資料から、別の都道府県・災害の
 * 会議が抽出されることがあるため、対象災害の地域名と他災害の地域名を照合する。
 */
class MeetingRelevanceFilter
{
    /** @var array<int, string> */
    private const PREFECTURES = [
        '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
        '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
        '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
        '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
        '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
        '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
        '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県',
    ];

    /** @var array<string, array<int, string>> */
    private const ALIASES = [
        '熊本' => ['KVOAD', 'くまもと', '火の国', '肥後'],
        '富山' => ['北陸'],
    ];

    /** @var array<int, array{current: array<string, true>, other: array<string, true>}>|null */
    private ?array $keywordMap = null;

    /**
     * 会議名・決定事項などが対象災害に関係するか判定する。
     */
    public function isRelevant(int $disasterId, string ...$texts): bool
    {
        $map = $this->keywordMap();
        $current = $map[$disasterId]['current'] ?? [];
        $other = $map[$disasterId]['other'] ?? [];

        if ($other === []) {
            return true;
        }

        $text = $this->normalize(implode("\n", $texts));

        if ($current !== [] && $this->containsAny($text, $current)) {
            return true;
        }

        return !$this->containsAny($text, $other);
    }

    /**
     * @return array<int, array{current: array<string, true>, other: array<string, true>}>
     */
    private function keywordMap(): array
    {
        if ($this->keywordMap !== null) {
            return $this->keywordMap;
        }

        $regions = [];
        $current = [];
        $configured = (array) config('disaster-intelligence.meeting_region_keywords', []);

        $disasters = Disaster::with(['localGovernments.prefecture'])->get();

        foreach ($disasters as $disaster) {
            $id = (int) $disaster->id;
            $regionKeywords = [];
            $localKeywords = [];

            $this->addKeyword($regionKeywords, (string) $disaster->name);
            $this->addPrefectureKeywords($regionKeywords, (string) $disaster->name);

            foreach ($disaster->localGovernments as $localGovernment) {
                $this->addKeyword($localKeywords, (string) $localGovernment->name);
                $this->addKeyword($regionKeywords, (string) ($localGovernment->prefecture->name ?? ''));
            }

            foreach ((array) ($configured[$id] ?? []) as $keyword) {
                $this->addKeyword($regionKeywords, (string) $keyword);
            }

            foreach (self::ALIASES as $base => $aliases) {
                if ($this->hasBase($regionKeywords, $base)) {
                    foreach ($aliases as $alias) {
                        $this->addKeyword($regionKeywords, $alias);
                    }
                }
            }

            $regions[$id] = $regionKeywords;
            $current[$id] = $regionKeywords + $localKeywords;
        }

        $map = [];

        foreach ($regions as $id => $own) {
            $other = [];

            foreach ($regions as $otherId => $keywords) {
                if ($otherId === $id) {
                    continue;
                }

                $other += $keywords;
            }

            $map[$id] = [
                'current' => $current[$id],
                'other' => array_diff_key($other, $current[$id]),
            ];
        }

        return $this->keywordMap = $map;
    }

    /**
     * @param array<string, true> $keywords
     */
    private function addPrefectureKeywords(array &$keywords, string $value): void
    {
        $value = $this->normalize($value);

        foreach (self::PREFECTURES as $prefecture) {
            $short = $prefecture === '北海道'
                ? $prefecture
                : (string) preg_replace('/(都|府|県)$/u', '', $prefecture);

            if ($short !== '' && str_contains($value, $short)) {
                $this->addKeyword($keywords, $prefecture);
                $this->addKeyword($keywords, $short);
            }
        }
    }

    /**
     * @param array<string, true> $keywords
     */
    private function addKeyword(array &$keywords, string $value): void
    {
        $value = $this->normalize($value);

        if ($value === '') {
            return;
        }

        $keywords[$value] = true;

        $stripped = (string) preg_replace('/(都|道|府|県|市|町|村)$/u', '', $value);

        if ($stripped !== '' && $stripped !== $value && mb_strlen($stripped) >= 2) {
            $keywords[$stripped] = true;
        }
    }

    /**
     * @param array<string, true> $keywords
     */
    private function hasBase(array $keywords, string $base): bool
    {
        return isset($keywords[$base]) || isset($keywords[$base . '県'])
            || isset($keywords[$base . '府']) || isset($keywords[$base . '都']);
    }

    /**
     * @param array<string, true> $keywords
     */
    private function containsAny(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword => $_) {
            if ($keyword !== '' && str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $value): string
    {
        $value = mb_convert_kana($value, 'asKV');
        $value = mb_strtolower($value, 'UTF-8');

        return trim((string) preg_replace('/[\s\x{3000}]+/u', '', $value));
    }
}
