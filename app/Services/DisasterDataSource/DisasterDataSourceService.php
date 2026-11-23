<?php

namespace App\Services\DisasterDataSource;

use App\DisasterDataSource;
use App\Support\DisasterDataSourceCategory;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 災害ごとのデータ出典・収集日を取得する。
 */
class DisasterDataSourceService
{
    /**
     * カテゴリごとの最終収集日を返す。
     *
     * @param int $disasterId
     * @return array<string, Carbon|null>
     */
    public function latestCollectedAt(int $disasterId): array
    {
        $latest = DisasterDataSource::query()
            ->where('disaster_id', $disasterId)
            ->select('category', DB::raw('MAX(collected_at) as latest'))
            ->groupBy('category')
            ->pluck('latest', 'category')
            ->all();

        $result = [];

        foreach (array_keys(DisasterDataSourceCategory::labels()) as $category) {
            $result[$category] = isset($latest[$category]) && $latest[$category] !== null
                ? Carbon::parse($latest[$category])
                : null;
        }

        return $result;
    }

    /**
     * カテゴリごとに出典をまとめて返す。
     *
     * @param int $disasterId
     * @return Collection<string, Collection<int, DisasterDataSource>>
     */
    public function groupedSources(int $disasterId): Collection
    {
        return DisasterDataSource::query()
            ->where('disaster_id', $disasterId)
            ->orderByDesc('collected_at')
            ->orderBy('source_name')
            ->get()
            ->groupBy('category');
    }
}
