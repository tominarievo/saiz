<?php

namespace App\Services;

use App\Disaster;
use App\EvacuationShelter;
use App\HistoricalBoundary;
use App\Services\DisasterDataSource\DisasterDataSourceService;
use App\VolunteerCenter;
use App\WasteSite;
use Illuminate\Support\Collection;

/**
 * 災害詳細マップに必要なデータを組み立てる。
 */
class DisasterMapContext
{
    /**
     * @param DisasterDataSourceService $dataSourceService
     */
    public function __construct(private readonly DisasterDataSourceService $dataSourceService)
    {
    }

    /**
     * @param Disaster $disaster
     * @return array<string, mixed>
     */
    public function build(Disaster $disaster): array
    {
        $disaster->loadMissing(['disasterTypes', 'localGovernments.prefecture', 'informationLinks']);

        $mapMunicipalities = $disaster->localGovernments
            ->map(function ($localGovernment) {
                $adminCode = substr($localGovernment->code, 0, 5);
                $codes = $localGovernment->is_designated_city
                    ? config("designated-city-wards.{$adminCode}", [])
                    : [$adminCode];

                return [
                    'code' => $adminCode,
                    'name' => $localGovernment->prefecture->name . ' ' . $localGovernment->name,
                    'prefecture' => $localGovernment->prefecture->name,
                    'municipality' => $localGovernment->name,
                    'prefecture_code' => $localGovernment->prefecture_code,
                    'codes' => array_values($codes),
                ];
            })
            ->filter(fn ($municipality) => filled($municipality['codes']))
            ->values();

        $schoolDistrictUrlTemplate = route('disasters.school_districts', [
            'disaster' => $disaster->id,
            'type' => 'TYPE',
        ]);

        $historicalUrlTemplate = route('disasters.historical_boundaries', [
            'disaster' => $disaster->id,
            'date' => 'DATE',
        ]);

        $prefectureCodes = $disaster->localGovernments
            ->pluck('prefecture_code')
            ->filter()
            ->unique()
            ->values();

        $historicalDates = HistoricalBoundary::whereIn('prefecture_code', $prefectureCodes)
            ->orderBy('boundary_date')
            ->pluck('boundary_date')
            ->unique()
            ->values();

        $dataCollectionDates = $this->dataSourceService->latestCollectedAt($disaster->id);

        $localGovernmentIds = $disaster->localGovernments->pluck('id')->all();
        $activeShelterCount = $localGovernmentIds === []
            ? 0
            : EvacuationShelter::whereIn('local_government_id', $localGovernmentIds)
                ->where('is_active', true)
                ->count();
        $openVolunteerCenterCount = $localGovernmentIds === []
            ? 0
            : VolunteerCenter::whereIn('local_government_id', $localGovernmentIds)
                ->where('activity_status', 'open')
                ->count();

        $latestUpdatedAt = $disaster->updated_at;

        foreach ($dataCollectionDates as $collectedAt) {
            if ($collectedAt && (!$latestUpdatedAt || $collectedAt->gt($latestUpdatedAt))) {
                $latestUpdatedAt = $collectedAt;
            }
        }

        foreach ($disaster->informationLinks as $informationLink) {
            $linkUpdatedAt = $informationLink->fetched_at ?? $informationLink->updated_at;

            if ($linkUpdatedAt && (!$latestUpdatedAt || $linkUpdatedAt->gt($latestUpdatedAt))) {
                $latestUpdatedAt = $linkUpdatedAt;
            }
        }

        $wasteNotices = WasteSite::where('disaster_id', $disaster->id)
            ->where('status', 'closed')
            ->orderByDesc('closed_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $digestItems = $disaster->informationLinks
            ->sortByDesc(fn ($link) => $link->fetched_at ?? $link->updated_at)
            ->take(2)
            ->map(fn ($link) => [
                'title' => $link->description ?: $link->url,
                'date' => $link->fetched_at ?? $link->updated_at,
                'target' => 'news-link-' . $link->id,
                'type' => '最新情報',
            ])
            ->values();

        if ($digestItems->count() < 2) {
            $digestItems = $digestItems->concat(
                $wasteNotices
                    ->take(2 - $digestItems->count())
                    ->map(fn ($notice) => [
                        'title' => $notice->name . 'の受入終了',
                        'date' => $notice->closed_at ?? $notice->last_verified_at ?? $notice->updated_at,
                        'target' => 'news-waste-' . $notice->id,
                        'type' => '仮置場',
                    ])
            )->values();
        }

        if ($activeShelterCount > 0) {
            $disasterPhase = ['label' => '避難所開設中', 'class' => 'danger'];
        } elseif ($openVolunteerCenterCount > 0) {
            $disasterPhase = ['label' => 'ボランティア募集中', 'class' => 'success'];
        } elseif ($latestUpdatedAt && $latestUpdatedAt->gt(now()->subDays(30))) {
            $disasterPhase = ['label' => '復旧対応中', 'class' => 'warning'];
        } else {
            $disasterPhase = ['label' => '復旧・アーカイブ', 'class' => 'secondary'];
        }

        return compact(
            'disaster',
            'mapMunicipalities',
            'schoolDistrictUrlTemplate',
            'historicalUrlTemplate',
            'historicalDates',
            'dataCollectionDates',
            'activeShelterCount',
            'openVolunteerCenterCount',
            'latestUpdatedAt',
            'disasterPhase',
            'wasteNotices',
            'digestItems'
        );
    }

    /**
     * 切り替え対象の公開災害を返す。市町村未登録の災害は除外する。
     *
     * @return Collection<int, array{id: int, name: string, event_date: string|null}>
     */
    public function options(): Collection
    {
        return Disaster::query()
            ->where('status', true)
            ->whereHas('localGovernments', function ($query) {
                $query->whereNotNull('code')->where('code', '!=', '');
            })
            ->orderBy('event_date', 'DESC')
            ->get(['id', 'name', 'event_date'])
            ->map(fn (Disaster $disaster) => [
                'id' => $disaster->id,
                'name' => $disaster->name,
                'event_date' => $disaster->event_date,
            ])
            ->values();
    }
}
