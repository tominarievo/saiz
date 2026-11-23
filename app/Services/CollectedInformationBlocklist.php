<?php

namespace App\Services;

use App\CollectedInformationBlock;

/**
 * 削除された収集情報の再収集を防ぐ。
 */
class CollectedInformationBlocklist
{
    public const TYPE_SOURCE = 'source';
    public const TYPE_LINK = 'link';
    public const TYPE_ROAD = 'road';
    public const TYPE_WASTE_SITE = 'waste_site';
    public const TYPE_MEETING_INSIGHT = 'meeting_insight';

    public function isBlocked(int $disasterId, string $type, string $key): bool
    {
        if ($key === '') {
            return false;
        }

        return CollectedInformationBlock::where('disaster_id', $disasterId)
            ->where('type', $type)
            ->where('block_hash', $this->hash($type, $key))
            ->exists();
    }

    public function isUrlBlocked(int $disasterId, string $url): bool
    {
        return $this->isBlocked($disasterId, self::TYPE_SOURCE, $this->normalizeUrl($url))
            || $this->isBlocked($disasterId, self::TYPE_LINK, $this->normalizeUrl($url));
    }

    public function block(int $disasterId, string $type, string $key, ?string $sourceUrl = null, ?int $userId = null): void
    {
        $key = trim($key);

        if ($key === '') {
            return;
        }

        CollectedInformationBlock::updateOrCreate(
            [
                'disaster_id' => $disasterId,
                'type' => $type,
                'block_hash' => $this->hash($type, $key),
            ],
            [
                'block_key' => $key,
                'source_url' => $sourceUrl,
                'created_by' => $userId,
            ]
        );
    }

    public static function roadKey(?string $sourceUrl, string $roadName, ?string $section): string
    {
        return implode('|', [
            self::normalizeUrl((string) $sourceUrl),
            self::normalizeText($roadName),
            self::normalizeText((string) $section),
        ]);
    }

    public static function wasteSiteKey(?string $sourceUrl, string $name): string
    {
        return implode('|', [
            self::normalizeUrl((string) $sourceUrl),
            self::normalizeText($name),
        ]);
    }

    public static function meetingInsightKey(?string $sourceUrl, string $meetingName, ?string $heldAt): string
    {
        return implode('|', [
            self::normalizeUrl((string) $sourceUrl),
            self::normalizeText($meetingName),
            self::normalizeText((string) $heldAt),
        ]);
    }

    public static function normalizeUrl(string $url): string
    {
        return mb_strtolower(trim($url));
    }

    public static function normalizeText(string $value): string
    {
        $value = mb_convert_kana($value, 'asKV');

        return trim((string) preg_replace('/[\s　]+/u', '', $value));
    }

    private function hash(string $type, string $key): string
    {
        return hash('sha256', $type . '|' . $key);
    }
}
