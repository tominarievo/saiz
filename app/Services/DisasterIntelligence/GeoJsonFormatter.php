<?php

namespace App\Services\DisasterIntelligence;

use App\RoadRegulation;
use App\Support\SourceLicense;
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
                    'status' => $road->status,
                    'vehicle_classes' => $road->vehicle_classes ?? [],
                    'reason' => $road->reason,
                    'confidence_rank' => $road->confidence_rank,
                    'recovered_at' => $road->recovered_at?->toIso8601String(),
                    'last_verified_at' => $road->last_verified_at?->toIso8601String(),
                    'updated_at' => $road->updated_at?->toIso8601String(),
                    'recovery_source_url' => $road->recovery_source_url,
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
                    'last_verified_at' => $site->last_verified_at?->toIso8601String(),
                    'updated_at' => $site->updated_at?->toIso8601String(),
                    'closed_at' => $site->closed_at?->toIso8601String(),
                    'status_source_url' => $site->status_source_url,
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
            $normalized = $this->normalizeGeometry($geometry);

            if ($normalized !== null) {
                return $normalized;
            }
        }

        $point = $this->coordinatePair($longitude, $latitude);

        if ($point !== null) {
            return [
                'type' => 'Point',
                'coordinates' => $point,
            ];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $geometry
     * @return array<string, mixed>|null
     */
    private function normalizeGeometry(array $geometry): ?array
    {
        $coordinates = $geometry['coordinates'] ?? null;

        return match ($geometry['type'] ?? null) {
            'Point' => $this->normalizePoint($coordinates),
            'LineString' => $this->normalizeLineString($coordinates),
            'MultiLineString' => $this->normalizeMultiLineString($coordinates),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizePoint(mixed $coordinates): ?array
    {
        if (!is_array($coordinates)) {
            return null;
        }

        $point = $this->coordinatePair($coordinates[0] ?? null, $coordinates[1] ?? null);

        return $point === null ? null : ['type' => 'Point', 'coordinates' => $point];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeLineString(mixed $coordinates): ?array
    {
        if (!is_array($coordinates) || count($coordinates) < 2) {
            return null;
        }

        $points = [];

        foreach ($coordinates as $coordinate) {
            if (!is_array($coordinate)) {
                return null;
            }

            $point = $this->coordinatePair($coordinate[0] ?? null, $coordinate[1] ?? null);

            if ($point === null) {
                return null;
            }

            $points[] = $point;
        }

        return ['type' => 'LineString', 'coordinates' => $points];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeMultiLineString(mixed $coordinates): ?array
    {
        if (!is_array($coordinates) || $coordinates === []) {
            return null;
        }

        $lines = [];

        foreach ($coordinates as $line) {
            $normalized = $this->normalizeLineString($line);

            if ($normalized === null) {
                return null;
            }

            $lines[] = $normalized['coordinates'];
        }

        return ['type' => 'MultiLineString', 'coordinates' => $lines];
    }

    /**
     * @return array{0: float, 1: float}|null
     */
    private function coordinatePair(mixed $longitude, mixed $latitude): ?array
    {
        if (!is_numeric($longitude) || !is_numeric($latitude)) {
            return null;
        }

        $lng = (float) $longitude;
        $lat = (float) $latitude;

        if (!is_finite($lng) || !is_finite($lat)) {
            return null;
        }

        if ($lng < -180.0 || $lng > 180.0 || $lat < -90.0 || $lat > 90.0) {
            return null;
        }

        if (abs($lng) < 0.000001 && abs($lat) < 0.000001) {
            return null;
        }

        return [$lng, $lat];
    }

    /**
     * @return array<string, mixed>
     */
    private function source(RoadRegulation|WasteSite $model): array
    {
        $metadata = is_array($model->metadata) ? $model->metadata : [];
        $citationUrl = is_string($metadata['citation_url'] ?? null) ? $metadata['citation_url'] : null;

        return [
            'org' => $model->source_org,
            'url' => $citationUrl ?: $model->source_url,
            'citation_url' => $citationUrl,
            'retrieval_url' => $model->source_url,
            'published_at' => $model->source_published_at?->toIso8601String(),
            'scraped_at' => $model->scraped_at?->toIso8601String(),
            'credibility' => $model->source_credibility,
            'credibility_reason' => $model->source_credibility_reason,
            'license' => SourceLicense::forUrl($citationUrl ?: $model->source_url),
        ];
    }
}
