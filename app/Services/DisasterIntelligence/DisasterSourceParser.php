<?php

namespace App\Services\DisasterIntelligence;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 災害資料の本文をLLMで構造化する。
 */
class DisasterSourceParser
{
    private const PROMPTS = [
        'roads' => 'roads',
        'waste_sites' => 'waste_sites',
        'meeting_insights' => 'meeting_insights',
    ];

    /**
     * @param PromptRepository $prompts
     * @param LlmJsonClient $llm
     */
    public function __construct(
        private readonly PromptRepository $prompts,
        private readonly LlmJsonClient $llm
    ) {
    }

    /**
     * 資料をパースし、正規化したレコード配列を返す。
     *
     * @param string $type
     * @param string $text
     * @param array<string, mixed> $attribution
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $type, string $text, array $attribution, array $targetMunicipalities = []): array
    {
        if (!isset(self::PROMPTS[$type])) {
            throw new \InvalidArgumentException('未対応の資料種別です: ' . $type);
        }

        $prompt = $this->prompts->render(self::PROMPTS[$type], [
            'SOURCE_ORG' => $attribution['source_org'] ?? '',
            'SOURCE_URL' => $attribution['source_url'] ?? '',
            'PUBLISHED_AT' => $attribution['source_published_at'] ?? '',
            'TARGET_MUNICIPALITIES' => implode('、', $targetMunicipalities),
            'VEHICLE_CLASSES' => implode(', ', array_keys(config('disaster-intelligence.vehicles', []))),
            'DISCLAIMER' => config('disaster-intelligence.disclaimer', ''),
            'DOCUMENT_TEXT' => mb_substr($text, 0, 120000),
        ]);

        try {
            $payload = $this->llm->completeJson($prompt);
        } catch (Throwable $error) {
            Log::warning('災害資料のAIパースをスキップしました。', [
                'type' => $type,
                'source_url' => $attribution['source_url'] ?? '',
                'message' => $error->getMessage(),
            ]);

            return [];
        }

        return match ($type) {
            'roads' => $this->normalizeRoads($payload['roads'] ?? [], $attribution, $targetMunicipalities),
            'waste_sites' => $this->normalizeWasteSites($payload['waste_sites'] ?? [], $attribution),
            'meeting_insights' => $this->normalizeMeetingInsights($payload['meeting_insights'] ?? [], $attribution),
            default => [],
        };
    }

    /**
     * @param mixed $items
     * @param array<string, mixed> $attribution
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRoads(mixed $items, array $attribution, array $targetMunicipalities): array
    {
        if (!is_array($items)) {
            return [];
        }

        $roads = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $roadName = trim((string) ($item['road_name'] ?? $item['name'] ?? ''));

            if ($roadName === '') {
                continue;
            }

            $section = trim((string) ($item['section'] ?? ''));
            $searchText = $roadName . ' ' . $section . ' ' . trim((string) ($item['reason'] ?? ''));

            if (!$this->matchesTargetMunicipality(
                $searchText,
                (string) ($attribution['source_org'] ?? ''),
                $targetMunicipalities
            )) {
                continue;
            }

            $latitude = $this->nullableFloat($item['latitude'] ?? null);
            $longitude = $this->nullableFloat($item['longitude'] ?? null);
            $geometry = $this->normalizeGeometry($item['geometry'] ?? null, $latitude, $longitude, 'Point');

            if ($geometry === null) {
                continue;
            }

            $roads[] = array_merge([
                'road_name' => mb_substr($roadName, 0, 255),
                'section' => $this->nullableString($item['section'] ?? null, 255),
                'regulation_type' => $this->normalizeRegulationType($item['regulation_type'] ?? 'other'),
                'vehicle_classes' => $this->normalizeVehicleClasses($item['vehicle_classes'] ?? []),
                'reason' => $this->nullableString($item['reason'] ?? null, 255),
                'geometry' => $geometry,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'confidence_rank' => $this->normalizeRank($item['confidence_rank'] ?? 'C'),
                'metadata' => is_array($item['metadata'] ?? null) ? $item['metadata'] : null,
            ], $this->attribution($attribution));
        }

        return $roads;
    }

    /**
     * @param mixed $items
     * @param array<string, mixed> $attribution
     * @return array<int, array<string, mixed>>
     */
    private function normalizeWasteSites(mixed $items, array $attribution): array
    {
        if (!is_array($items)) {
            return [];
        }

        $sites = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $name = trim((string) ($item['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $latitude = $this->nullableFloat($item['latitude'] ?? null);
            $longitude = $this->nullableFloat($item['longitude'] ?? null);
            $geometry = $this->normalizeGeometry($item['geometry'] ?? null, $latitude, $longitude, 'Point');

            if ($geometry === null) {
                continue;
            }

            $sites[] = array_merge([
                'name' => mb_substr($name, 0, 255),
                'status' => $this->normalizeStatus($item['status'] ?? 'unknown'),
                'geometry' => $geometry,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'acceptance_start_at' => $this->nullableDate($item['acceptance_start_at'] ?? null),
                'acceptance_end_at' => $this->nullableDate($item['acceptance_end_at'] ?? null),
                'vehicle_limit' => $this->nullableString($item['vehicle_limit'] ?? null, 255),
                'max_payload_tons' => $this->nullableFloat($item['max_payload_tons'] ?? null),
                'entry_allowed' => (bool) ($item['entry_allowed'] ?? true),
                'accepted_items' => $this->normalizeStringArray($item['accepted_items'] ?? []),
                'rejected_items' => $this->normalizeStringArray($item['rejected_items'] ?? []),
                'support_conditions' => is_array($item['support_conditions'] ?? null) ? $item['support_conditions'] : null,
                'metadata' => is_array($item['metadata'] ?? null) ? $item['metadata'] : null,
            ], $this->attribution($attribution));
        }

        return $sites;
    }

    /**
     * @param mixed $items
     * @param array<string, mixed> $attribution
     * @return array<int, array<string, mixed>>
     */
    private function normalizeMeetingInsights(mixed $items, array $attribution): array
    {
        if (!is_array($items)) {
            return [];
        }

        $insights = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $meetingName = trim((string) ($item['meeting_name'] ?? ''));

            if ($meetingName === '') {
                continue;
            }

            $insights[] = array_merge([
                'meeting_name' => mb_substr($meetingName, 0, 255),
                'held_at' => $this->nullableDate($item['held_at'] ?? null),
                'decisions' => $this->normalizeStringArray($item['decisions'] ?? []),
                'policy_changes' => $this->normalizeStringArray($item['policy_changes'] ?? []),
                'unmet_needs' => $this->normalizeStringArray($item['unmet_needs'] ?? []),
                'source_page' => $this->nullableInt($item['source_page'] ?? null),
                'metadata' => is_array($item['metadata'] ?? null) ? $item['metadata'] : null,
            ], $this->attribution($attribution));
        }

        return $insights;
    }

    /**
     * @param array<string, mixed> $attribution
     * @return array<string, mixed>
     */
    private function attribution(array $attribution): array
    {
        return [
            'source_org' => (string) ($attribution['source_org'] ?? '不明'),
            'source_url' => (string) ($attribution['source_url'] ?? ''),
            'source_published_at' => $this->nullableDate($attribution['source_published_at'] ?? null),
            'scraped_at' => $this->nullableDate($attribution['scraped_at'] ?? null),
            'raw_file_path' => $attribution['raw_file_path'] ?? null,
            'source_credibility' => $attribution['source_credibility'] ?? null,
            'source_credibility_reason' => $attribution['source_credibility_reason'] ?? null,
        ];
    }

    /**
     * 対象市町村に該当する道路か判定する。
     *
     * @param string $searchText
     * @param string $sourceOrg
     * @param array<int, string> $targetMunicipalities
     * @return bool
     */
    private function matchesTargetMunicipality(string $searchText, string $sourceOrg, array $targetMunicipalities): bool
    {
        if ($targetMunicipalities === []) {
            return true;
        }

        foreach ($targetMunicipalities as $municipality) {
            if ($municipality !== '' && (mb_strpos($searchText, $municipality) !== false || mb_strpos($sourceOrg, $municipality) !== false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $geometry
     * @param float|null $latitude
     * @param float|null $longitude
     * @param string $fallbackType
     * @return array<string, mixed>|null
     */
    private function normalizeGeometry(mixed $geometry, ?float $latitude, ?float $longitude, string $fallbackType): ?array
    {
        if (is_string($geometry)) {
            $geometry = json_decode($geometry, true);
        }

        if (is_array($geometry) && isset($geometry['type'], $geometry['coordinates'])) {
            return $geometry;
        }

        if ($latitude !== null && $longitude !== null) {
            return [
                'type' => $fallbackType,
                'coordinates' => [$longitude, $latitude],
            ];
        }

        return null;
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private function normalizeVehicleClasses(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $allowed = array_keys(config('disaster-intelligence.vehicles', []));

        return array_values(array_intersect($allowed, array_map('strval', $value)));
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private function normalizeStringArray(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($item) {
            return is_scalar($item) ? trim((string) $item) : '';
        }, $value), fn ($item) => $item !== ''));
    }

    private function normalizeRegulationType(mixed $value): string
    {
        $value = strtolower(trim((string) $value));
        $allowed = ['full_closure', 'alternating', 'large_vehicle', 'weight_limit', 'other'];

        return in_array($value, $allowed, true) ? $value : 'other';
    }

    private function normalizeStatus(mixed $value): string
    {
        $value = strtolower(trim((string) $value));
        $allowed = ['open', 'closed', 'unknown'];

        return in_array($value, $allowed, true) ? $value : 'unknown';
    }

    private function normalizeRank(mixed $value): string
    {
        $value = strtoupper(trim((string) $value));

        return in_array($value, ['A', 'B', 'C'], true) ? $value : 'C';
    }

    private function nullableString(mixed $value, int $limit): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : mb_substr($value, 0, $limit);
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function nullableDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateTimeString();
        } catch (Throwable) {
            return null;
        }
    }
}
