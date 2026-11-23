<?php

namespace App\Console\Commands;

use App\Disaster;
use App\EvacuationShelter;
use App\MeetingInsight;
use App\RoadRegulation;
use App\Services\DisasterDataSource\DisasterDataSourceRecorder;
use App\Support\DisasterDataSourceCategory;
use App\VolunteerCenter;
use App\WasteSite;
use Illuminate\Console\Command;

class RebuildDisasterDataSources extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'disaster-data-sources:rebuild
                            {disaster? : 災害ID（省略時は全災害）}';

    /**
     * コマンド説明
     *
     * @var string
     */
    protected $description = '既存の自動収集データから災害ごとの出典・収集日を再構築する';

    /**
     * Execute the console command.
     */
    public function handle(DisasterDataSourceRecorder $recorder): int
    {
        $disasterId = $this->argument('disaster');

        $disasters = $disasterId
            ? Disaster::where('id', (int) $disasterId)->get()
            : Disaster::all();

        if ($disasters->isEmpty()) {
            $this->error('対象の災害が見つかりません。');
            return self::FAILURE;
        }

        foreach ($disasters as $disaster) {
            $before = $disaster->dataSources()->count();

            $this->rebuildInformationLinks($disaster, $recorder);
            $this->rebuildIntelligenceData($disaster, $recorder);
            $this->rebuildShelterAndVolunteerSources($disaster, $recorder);

            $after = $disaster->dataSources()->count();
            $added = $after - $before;
            $this->info("災害ID {$disaster->id}「{$disaster->name}」: 出典 {$after}件（追加 {$added}件）");
        }

        return self::SUCCESS;
    }

    private function rebuildInformationLinks(Disaster $disaster, DisasterDataSourceRecorder $recorder): void
    {
        foreach ($disaster->informationLinks as $link) {
            $isAi = $link->source === 'ai';
            $host = parse_url((string) $link->url, PHP_URL_HOST);
            $sourceName = $isAi ? ($host ?: 'AI収集') : '手動登録';

            $recorder->record(
                $disaster->id,
                DisasterDataSourceCategory::INFORMATION,
                $sourceName,
                $link->url,
                $link->description,
                $link->fetched_at ?? $link->updated_at ?? $link->created_at,
                null,
                ['source' => $link->source]
            );
        }
    }

    private function rebuildIntelligenceData(Disaster $disaster, DisasterDataSourceRecorder $recorder): void
    {
        $targets = [
            DisasterDataSourceCategory::ROADS => RoadRegulation::class,
            DisasterDataSourceCategory::WASTE_SITES => WasteSite::class,
            DisasterDataSourceCategory::MEETING_INSIGHTS => MeetingInsight::class,
        ];

        foreach ($targets as $category => $model) {
            $groups = $model::where('disaster_id', $disaster->id)
                ->get()
                ->groupBy('source_url');

            foreach ($groups as $url => $group) {
                $first = $group->first();
                $collectedAt = $group->max('scraped_at') ?? $group->max('updated_at');
                $publishedAt = $group->max('source_published_at');

                $recorder->record(
                    $disaster->id,
                    $category,
                    $first->source_org,
                    $url,
                    null,
                    $collectedAt,
                    $publishedAt,
                    ['records' => $group->count(), 'rebuilt' => true]
                );
            }
        }
    }

    private function rebuildShelterAndVolunteerSources(Disaster $disaster, DisasterDataSourceRecorder $recorder): void
    {
        $localGovernmentIds = $disaster->localGovernments()->pluck('local_governments.id');

        if ($localGovernmentIds->isEmpty()) {
            return;
        }

        $shelterGroups = EvacuationShelter::whereIn('local_government_id', $localGovernmentIds)
            ->get()
            ->groupBy(fn ($shelter) => ($shelter->source_name ?? '').'|'.($shelter->source_url ?? ''));

        foreach ($shelterGroups as $group) {
            $first = $group->first();

            $recorder->record(
                $disaster->id,
                DisasterDataSourceCategory::EVACUATION_SHELTERS,
                $first->source_name,
                $first->source_url,
                null,
                $group->max('fetched_at') ?? $group->max('updated_at'),
                null,
                ['records' => $group->count(), 'rebuilt' => true]
            );
        }

        $statusGroups = EvacuationShelter::whereIn('local_government_id', $localGovernmentIds)
            ->whereNotNull('status_source_name')
            ->get()
            ->groupBy(fn ($shelter) => ($shelter->status_source_name ?? '').'|'.($shelter->status_source_url ?? ''));

        foreach ($statusGroups as $group) {
            $first = $group->first();

            $recorder->record(
                $disaster->id,
                DisasterDataSourceCategory::EVACUATION_SHELTERS,
                $first->status_source_name,
                $first->status_source_url,
                '避難所開設状況',
                $group->max('status_checked_at') ?? $group->max('updated_at'),
                null,
                ['records' => $group->count(), 'status' => true]
            );
        }

        $volunteerGroups = VolunteerCenter::whereIn('local_government_id', $localGovernmentIds)
            ->get()
            ->groupBy(fn ($center) => ($center->source_name ?? '').'|'.($center->source_url ?? ''));

        foreach ($volunteerGroups as $group) {
            $first = $group->first();

            $recorder->record(
                $disaster->id,
                DisasterDataSourceCategory::VOLUNTEER_CENTERS,
                $first->source_name,
                $first->source_url,
                null,
                $group->max('fetched_at') ?? $group->max('updated_at'),
                null,
                ['records' => $group->count(), 'rebuilt' => true]
            );
        }
    }
}
