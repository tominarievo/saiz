<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\MeetingInsight;
use App\RoadRegulation;
use App\Services\DisasterIntelligence\GeoJsonFormatter;
use App\WasteSite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisasterIntelligenceController extends Controller
{
    /**
     * @param GeoJsonFormatter $geoJson
     */
    public function __construct(private readonly GeoJsonFormatter $geoJson)
    {
    }

    /**
     * 道路規制をGeoJSONで返す。
     */
    public function roads(Request $request): JsonResponse
    {
        $vehicle = $this->vehicle($request);

        if ($vehicle === false) {
            return response()->json(['message' => '車両区分が不正です。'], 422);
        }

        $query = RoadRegulation::query();

        if ($request->filled('disaster_id')) {
            $query->where('disaster_id', (int) $request->input('disaster_id'));
        }

        $roads = $query->orderBy('id')->get()->filter(function (RoadRegulation $road) use ($vehicle) {
            if (!$vehicle) {
                return true;
            }

            $classes = $road->vehicle_classes ?? [];

            return $classes === [] || in_array($vehicle, $classes, true);
        })->values();

        return response()->json($this->geoJson->roads($roads));
    }

    /**
     * 災害廃棄物・仮置場をGeoJSONで返す。
     */
    public function wasteSites(Request $request): JsonResponse
    {
        $vehicle = $this->vehicle($request);

        if ($vehicle === false) {
            return response()->json(['message' => '車両区分が不正です。'], 422);
        }

        $query = WasteSite::query();

        if ($request->filled('disaster_id')) {
            $query->where('disaster_id', (int) $request->input('disaster_id'));
        }

        $sites = $query->orderByDesc('source_published_at')
            ->orderByDesc('scraped_at')
            ->orderByDesc('id')
            ->get()
            ->filter(function (WasteSite $site) use ($vehicle) {
                if (!$site->entry_allowed) {
                    return false;
                }

                if (!$vehicle) {
                    return true;
                }

                $limit = $site->max_payload_tons;

                if ($limit === null) {
                    return true;
                }

                return match ($vehicle) {
                    'light_truck', '2t' => $limit <= 2,
                    '4t' => $limit <= 4,
                    'large' => true,
                    default => false,
                };
            })
            ->unique(fn (WasteSite $site) => preg_replace('/\s+/u', '', (string) $site->name))
            ->values();

        return response()->json($this->geoJson->wasteSites($sites));
    }

    /**
     * 議事録サマリを返す。
     */
    public function meetings(Request $request): JsonResponse
    {
        $query = MeetingInsight::query();

        if ($request->filled('disaster_id')) {
            $query->where('disaster_id', (int) $request->input('disaster_id'));
        }

        $meetings = $query->orderByDesc('held_at')
            ->orderByDesc('source_published_at')
            ->orderByDesc('scraped_at')
            ->orderByDesc('id')
            ->get()
            ->filter(function (MeetingInsight $meeting) {
                return !empty($meeting->decisions)
                    || !empty($meeting->policy_changes)
                    || !empty($meeting->unmet_needs);
            })
            ->unique(fn (MeetingInsight $meeting) => $meeting->held_at?->format('Y-m-d H:i:s') ?? 'undated-' . $meeting->id)
            ->values()
            ->map(function (MeetingInsight $meeting) {
                return [
                    'id' => $meeting->id,
                    'meeting_name' => $meeting->meeting_name,
                    'held_at' => $meeting->held_at?->toIso8601String(),
                    'decisions' => $meeting->decisions ?? [],
                    'policy_changes' => $meeting->policy_changes ?? [],
                    'unmet_needs' => $meeting->unmet_needs ?? [],
                    'source' => [
                        'org' => $meeting->source_org,
                        'url' => $meeting->source_url,
                        'published_at' => $meeting->source_published_at?->toIso8601String(),
                        'scraped_at' => $meeting->scraped_at?->toIso8601String(),
                        'credibility' => $meeting->source_credibility,
                        'credibility_reason' => $meeting->source_credibility_reason,
                    ],
                ];
            });

        return response()->json([
            'meetings' => $meetings,
            'meta' => [
                'count' => $meetings->count(),
                'generated_at' => now()->toIso8601String(),
                'disclaimer' => config('disaster-intelligence.disclaimer'),
            ],
        ]);
    }

    /**
     * @return string|null|false
     */
    private function vehicle(Request $request): string|null|false
    {
        $vehicle = $request->input('vehicle');

        if ($vehicle === null || $vehicle === '') {
            return null;
        }

        $allowed = array_keys(config('disaster-intelligence.vehicles', []));

        return in_array($vehicle, $allowed, true) ? (string) $vehicle : false;
    }
}
