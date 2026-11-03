<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        $sites = $query->orderBy('id')->get()->filter(function (WasteSite $site) use ($vehicle) {
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
        })->values();

        return response()->json($this->geoJson->wasteSites($sites));
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
