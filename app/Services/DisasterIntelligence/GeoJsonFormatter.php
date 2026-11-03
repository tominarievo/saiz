<?php

namespace App\Services\DisasterIntelligence;

use App\RoadRegulation;
use App\WasteSite;
use Illuminate\Support\Collection;

/**
 * 道路規制・仮置場をGeoJSON FeatureCollectionに変換する。
 */
class GeoJsonFormatter
{
    /**
     * @param Collection<int, RoadRegulation> $roads
     * @return array<string, mixed>
     */
    public function roads(Collection $roads): array
    {
        $features = $roads->map(function (RoadRegulation $road) {
            $geometry = $this->geometry($road->geometry, $road->latitude, $road->longitude);

            if ($geometry === null) {
                return null;
            }

            return [
                'type' => 'Feature',
                'geometry' => $geometry,
                'properties' => [
                    'id' => $road->id,
                    'road_name' => $road->road_name,
                    'section' => $road->section,
                    'regulation_type' => $road->regulation_type,
                    'vehicle_classes' => $road->vehicle_classes ?? [],
                    'reason' => $road->reason,
                    'confidence_rank' => $road->confidence_rank,
                    'source' => $this->source($road),
                    'ai_extracted' => $road->confidence_rank === 'C',
                    'disclaimer' => config('disaster-intelligence.disclaimer'),
                ],
            ];
        })->filter()->values()->all();

        return $this->featureCollection($features, [
            'count' => count($features),
            'generated_at' => now()->toIso8601String(),
            'disclaimer' => config('disaster-intelligence.disclaimer'),
        ]);
    }

    /**
     * @param Collection<int, WasteSite> $sites
     * @return array<string, mixed>
     */
    public function wasteSites(Collection $sites): array
    {
        $features = $sites->map(function (WasteSite $site) {
            $geometry = $this->geometry($site->geometry, $site->latitude, $site->longitude);

            if ($geometry === null) {
                return null;
            }

            return [
                'type' => 'Feature',
                'geometry' => $geometry,
                'properties' => [
                    'id' => $site->id,
                    'name' => $site->name,
                    'status' => $site->status,
                    'acceptance_start_at' => $site->acceptance_start_at?->toIso8601String(),
                    'acceptance_end_at' => $site->acceptance_end_at?->toIso8601String(),
                    'vehicle_limit' => $site->vehicle_limit,
                    'max_payload_tons' => $site->max_payload_tons,
                    'entry_allowed' => $site->entry_allowed,
                    'accepted_items' => $site->accepted_items ?? [],
                    'rejected_items' => $site->rejected_items ?? [],
                    'support_conditions' => $site->support_conditions,
                    'source' => $this->source($site),
                    'ai_extracted' => $site->source_url !== '' && $site->metadata !== null,
                    'disclaimer' => config('disaster-intelligence.disclaimer'),
                ],
            ];
        })->filter()->values()->all();

        return $this->featureCollection($features, [
            'count' => count($features),
            'generated_at' => now()->toIso8601String(),
            'disclaimer' => config('disaster-intelligence.disclaimer'),
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $features
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    private function featureCollection(array $features, array $meta = []): array
    {
        return [
            'type' => 'FeatureCollection',
            'features' => $features,
            'meta' => $meta,
        ];
    }

    /**
     * @param array<string, mixed>|null $geometry
     * @return array<string, mixed>|null
     */
    private function geometry(?array $geometry, ?float $latitude, ?float $longitude): ?array
    {
        if (is_array($geometry) && isset($geometry['type'], $geometry['coordinates'])) {
            return $geometry;
        }

        if ($latitude !== null && $longitude !== null) {
            return [
                'type' => 'Point',
                'coordinates' => [$longitude, $latitude],
            ];
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function source(RoadRegulation|WasteSite $model): array
    {
        return [
            'org' => $model->source_org,
            'url' => $model->source_url,
            'published_at' => $model->source_published_at?->toIso8601String(),
            'scraped_at' => $model->scraped_at?->toIso8601String(),
            'raw_file_path' => $model->raw_file_path,
        ];
    }
}
