<?php

namespace App\Services\DisasterDataSource;

use App\DisasterDataSource;
use Carbon\Carbon;

/**
 * 自動収集データの出典と収集日を記録する。
 */
class DisasterDataSourceRecorder
{
    /**
     * 出典を登録または更新する。
     *
     * @param int $disasterId
     * @param string $category
     * @param string|null $sourceName
     * @param string|null $sourceUrl
     * @param string|null $title
     * @param mixed $collectedAt
     * @param mixed $publishedAt
     * @param array<string, mixed> $metadata
     * @return DisasterDataSource|null
     */
    public function record(
        int $disasterId,
        string $category,
        ?string $sourceName,
        ?string $sourceUrl,
        ?string $title = null,
        mixed $collectedAt = null,
        mixed $publishedAt = null,
        array $metadata = []
    ): ?DisasterDataSource {
        $sourceName = trim((string) $sourceName);
        $sourceUrl = trim((string) $sourceUrl);

        if ($sourceName === '' && $sourceUrl === '') {
            return null;
        }

        $title = trim((string) $title);

        if (mb_strlen($title) > 255) {
            $title = mb_substr($title, 0, 255);
        }

        $sourceKeyUrl = $sourceUrl;

        if (!empty($metadata['retrieval_url']) && $metadata['retrieval_url'] !== $sourceUrl) {
            $sourceKeyUrl .= '|'.$metadata['retrieval_url'];
        }

        $sourceKey = sha1($category.'|'.mb_strtolower($sourceName).'|'.$sourceKeyUrl);
        $collected = $collectedAt ? Carbon::parse($collectedAt) : now();
        $published = $publishedAt ? Carbon::parse($publishedAt) : null;

        $source = DisasterDataSource::firstOrNew([
            'disaster_id' => $disasterId,
            'category' => $category,
            'source_key' => $sourceKey,
        ]);

        if (!$source->collected_at || $collected->greaterThan($source->collected_at)) {
            $source->collected_at = $collected;
        }

        if ($sourceName !== '') {
            $source->source_name = $sourceName;
        }

        if ($sourceUrl !== '') {
            $source->source_url = $sourceUrl;
        }

        if ($title !== '') {
            $source->title = $title;
        }

        if ($published !== null) {
            $source->published_at = $published;
        }

        if ($metadata !== []) {
            $source->metadata = array_merge($source->metadata ?? [], $metadata);
        }

        $source->save();

        return $source;
    }
}
