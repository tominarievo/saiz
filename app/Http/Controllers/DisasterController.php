<?php

namespace App\Http\Controllers;

use App\DisasterType;
use App\EvacuationShelter;
use App\VolunteerCenter;
use App\Http\Requests\DisasterRequest;
use App\Disaster;
use App\HistoricalBoundary;
use App\LocalGovernment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DisasterController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $publicMethods = ['show', 'intelligence', 'schoolDistricts', 'historicalBoundaries', 'population', 'smallAreas', 'evacuationShelters', 'volunteerCenters'];

            if (!in_array($request->route()->getActionMethod(), $publicMethods, true)) {
                $this->authorizeAdmin();
            }

            return $next($request);
        });
    }

    /**
     * 公開中の災害、または管理者だけに詳細を許可する。
     *
     * @param Disaster $disaster
     * @return void
     */
    private function ensureDisasterVisible(Disaster $disaster): void
    {
        if (!$disaster->status && (!auth()->check() || !auth()->user()->isAdmin())) {
            abort(404);
        }
    }

    /**
     * 災害一覧ページを表示するためのメソッド
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {

        $condition = new \stdClass();

        $disasters = Disaster::with('localGovernments.prefecture')
            ->orderBy('event_date', 'DESC')
            ->paginate(10)->appends((array)$condition);

        return view('disasters.index', compact('disasters'));
    }

    /**
     * 災害情報の詳細ページを表示する。
     *
     * @param Disaster $disaster
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Disaster $disaster)
    {
        $this->ensureDisasterVisible($disaster);

        $disaster->load(['disasterTypes', 'localGovernments.prefecture', 'informationLinks']);

        $mapMunicipalities = $disaster->localGovernments
            ->map(function ($localGovernment) {
                $adminCode = substr($localGovernment->code, 0, 5);
                $codes = $localGovernment->is_designated_city
                    ? config("designated-city-wards.{$adminCode}", [])
                    : [$adminCode];

                return [
                    'name' => $localGovernment->prefecture->name . ' ' . $localGovernment->name,
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

        return view('disasters.show', compact(
            'disaster',
            'mapMunicipalities',
            'schoolDistrictUrlTemplate',
            'historicalUrlTemplate',
            'historicalDates'
        ));
    }

    /**
     * 専門災害支援団体向けの道路・廃棄物マップを表示する。
     *
     * @param Disaster $disaster
     * @return \Illuminate\Contracts\View\View
     */
    public function intelligence(Disaster $disaster)
    {
        $this->ensureDisasterVisible($disaster);

        return view('disasters.intelligence', compact('disaster'));
    }

    /**
     * 選択市町村の都道府県に対応する合併前行政界を返す。
     *
     * @param Disaster $disaster
     * @param string $date
     * @return \Illuminate\Http\JsonResponse
     */
    public function historicalBoundaries(Disaster $disaster, string $date)
    {
        $this->ensureDisasterVisible($disaster);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            abort(404);
        }

        $disaster->load('localGovernments');

        $prefectureCodes = $disaster->localGovernments
            ->pluck('prefecture_code')
            ->filter()
            ->unique()
            ->values();

        $boundaries = HistoricalBoundary::whereIn('prefecture_code', $prefectureCodes)
            ->where('boundary_date', $date)
            ->orderBy('prefecture_code')
            ->get();

        return response()->json([
            'date' => $date,
            'layers' => $boundaries->map(function ($boundary) {
                return [
                    'prefecture_code' => $boundary->prefecture_code,
                    'prefecture_name' => $boundary->prefecture_name,
                    'resolution' => $boundary->resolution,
                    'source' => $boundary->source,
                    'license' => $boundary->license,
                    'topojson' => $boundary->topojson,
                ];
            })->values(),
        ]);
    }

    /**
     * 選択市町村の学区境界を返す。
     *
     * @param Disaster $disaster
     * @param string $type
     * @return \Illuminate\Http\JsonResponse
     */
    public function schoolDistricts(Disaster $disaster, string $type)
    {
        $this->ensureDisasterVisible($disaster);

        $layers = [
            'elementary' => [
                'code' => 'A27',
                'label' => '小学校区',
            ],
            'junior_high' => [
                'code' => 'A32',
                'label' => '中学校区',
            ],
        ];

        abort_unless(isset($layers[$type]), 404);

        $layer = $layers[$type];

        $disaster->load('localGovernments');

        $features = [];
        $missing = [];

        foreach ($disaster->localGovernments as $localGovernment) {
            $adminCode = substr($localGovernment->code, 0, 5);
            $path = "school-districts/{$layer['code']}/{$localGovernment->prefecture_code}/{$adminCode}.json";

            if (!Storage::disk('local')->exists($path)) {
                $missing[] = $localGovernment->prefecture->name . ' ' . $localGovernment->name;
                continue;
            }

            $data = json_decode(Storage::disk('local')->get($path), true);

            if (is_array($data) && isset($data['features']) && is_array($data['features'])) {
                $features = array_merge($features, $data['features']);
            }
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
            'meta' => [
                'layer' => $type,
                'label' => $layer['label'],
                'year' => '2023',
                'source' => '国土数値情報（' . $layer['label'] . '）',
                'license' => 'CC BY 4.0（一部自治体は利用条件あり）',
                'available' => count($features) > 0,
                'missing' => array_values(array_unique($missing)),
            ],
        ]);
    }

    /**
     * 選択市町村の人口データを返す。
     *
     * @param Disaster $disaster
     * @return \Illuminate\Http\JsonResponse
     */
    public function population(Disaster $disaster)
    {
        $this->ensureDisasterVisible($disaster);

        $disaster->loadMissing('localGovernments.prefecture');

        $municipalities = [];
        $missing = [];
        $loadedCodes = [];

        foreach ($disaster->localGovernments as $localGovernment) {
            $code = substr($localGovernment->code, 0, 5);

            if (isset($loadedCodes[$code])) {
                continue;
            }

            $loadedCodes[$code] = true;
            $path = "population/{$localGovernment->prefecture_code}/{$code}.json";

            if (!Storage::disk('local')->exists($path)) {
                $missing[] = $localGovernment->prefecture->name . ' ' . $localGovernment->name;
                continue;
            }

            $data = json_decode(Storage::disk('local')->get($path), true);

            if (is_array($data)) {
                $municipalities[] = $data;
            }
        }

        return response()->json([
            'municipalities' => $municipalities,
            'meta' => [
                'available' => count($municipalities) > 0,
                'missing' => array_values(array_unique($missing)),
            ],
        ]);
    }

    /**
     * 選択市町村の小区域界を返す。
     *
     * @param Disaster $disaster
     * @return \Illuminate\Http\JsonResponse
     */
    public function smallAreas(Disaster $disaster)
    {
        $this->ensureDisasterVisible($disaster);

        ini_set('memory_limit', '512M');

        $disaster->loadMissing('localGovernments.prefecture');

        $features = [];
        $missing = [];
        $loadedCodes = [];

        foreach ($disaster->localGovernments as $localGovernment) {
            $code = substr($localGovernment->code, 0, 5);

            if (isset($loadedCodes[$code])) {
                continue;
            }

            $loadedCodes[$code] = true;
            $path = "population/{$localGovernment->prefecture_code}/small-areas/{$code}.geojson";

            if (!Storage::disk('local')->exists($path)) {
                $missing[] = $localGovernment->prefecture->name . ' ' . $localGovernment->name;
                continue;
            }

            $data = json_decode(Storage::disk('local')->get($path), true);

            if (is_array($data) && isset($data['features']) && is_array($data['features'])) {
                $features = array_merge($features, $data['features']);
            }
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
            'metadata' => [
                'as_of' => '2020-10-01',
                'available' => count($features) > 0,
                'missing' => array_values(array_unique($missing)),
                'source' => [
                    'name' => '令和2年国勢調査 町丁・字等境界データ',
                    'url' => 'https://www.e-stat.go.jp/gis/statmap-search?page=1&type=2&aggregateUnitForBoundary=A&toukeiCode=00200521',
                ],
            ],
        ]);
    }

    /**
     * 選択市町村の避難所・指定緊急避難場所を返す。
     *
     * @param Disaster $disaster
     * @return \Illuminate\Http\JsonResponse
     */
    public function evacuationShelters(Disaster $disaster)
    {
        $this->ensureDisasterVisible($disaster);

        $disaster->loadMissing('localGovernments');

        $localGovernmentIds = $disaster->localGovernments
            ->pluck('id')
            ->unique()
            ->values();

        $shelters = EvacuationShelter::with('localGovernment')
            ->whereIn('local_government_id', $localGovernmentIds)
            ->orderBy('local_government_id')
            ->orderBy('shelter_type')
            ->orderBy('name')
            ->get()
            ->map(function (EvacuationShelter $shelter) {
                return [
                    'id' => $shelter->id,
                    'name' => $shelter->name,
                    'address' => $shelter->address,
                    'latitude' => $shelter->latitude,
                    'longitude' => $shelter->longitude,
                    'shelter_type' => $shelter->shelter_type,
                    'disaster_types' => $shelter->disaster_types,
                    'note' => $shelter->note,
                    'is_active' => (bool) $shelter->is_active,
                    'activity_status' => $shelter->activity_status,
                    'evacuees' => $shelter->evacuees,
                    'status_note' => $shelter->status_note,
                    'status_checked_at' => $shelter->status_checked_at?->toIso8601String(),
                    'status_source' => [
                        'name' => $shelter->status_source_name,
                        'url' => $shelter->status_source_url,
                    ],
                    'municipality' => $shelter->localGovernment->name ?? '',
                    'source' => [
                        'name' => $shelter->source_name,
                        'url' => $shelter->source_url,
                    ],
                    'fetched_at' => $shelter->fetched_at?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json([
            'shelters' => $shelters,
            'meta' => [
                'available' => $shelters->isNotEmpty(),
                'count' => $shelters->count(),
                'active_count' => $shelters->where('is_active', true)->count(),
            ],
        ]);
    }

    /**
     * 選択市町村のボランティアセンターと最新募集情報を返す。
     *
     * @param Disaster $disaster
     * @return \Illuminate\Http\JsonResponse
     */
    public function volunteerCenters(Disaster $disaster)
    {
        $this->ensureDisasterVisible($disaster);

        $disaster->loadMissing('localGovernments');

        $localGovernmentIds = $disaster->localGovernments
            ->pluck('id')
            ->unique()
            ->values();

        $centers = VolunteerCenter::with('localGovernment')
            ->whereIn('local_government_id', $localGovernmentIds)
            ->orderBy('local_government_id')
            ->orderBy('name')
            ->get()
            ->map(function (VolunteerCenter $center) {
                return [
                    'id' => $center->id,
                    'name' => $center->name,
                    'address' => $center->address,
                    'latitude' => $center->latitude,
                    'longitude' => $center->longitude,
                    'phone' => $center->phone,
                    'url' => $center->url,
                    'municipality' => $center->localGovernment->name ?? '',
                    'latest_recruitment' => [
                        'title' => $center->latest_recruitment_title,
                        'summary' => $center->latest_recruitment_summary,
                        'url' => $center->latest_recruitment_url,
                        'updated_at' => $center->recruitment_updated_at?->toIso8601String(),
                    ],
                    'source' => [
                        'name' => $center->source_name,
                        'url' => $center->source_url,
                    ],
                    'fetched_at' => $center->fetched_at?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json([
            'centers' => $centers,
            'meta' => [
                'available' => $centers->isNotEmpty(),
                'count' => $centers->count(),
            ],
        ]);
    }

    /**
     * 新しい災害を作成するためのフォームを表示するメソッド
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create(Request $request)
    {
        $disaster = new Disaster();

        $disaster_types = DisasterType::get();
        $localGovernments = $this->localGovernmentOptions();
        $selectedLocalGovernmentIds = old('local_government_ids', []);
        $informationLinks = old('information_links', [['description' => '', 'url' => '']]);

        return view('disasters.create', compact(
            'disaster',
            'disaster_types',
            'localGovernments',
            'selectedLocalGovernmentIds',
            'informationLinks'
        ));
    }

    /**
     * 新しい災害を保存するメソッド
     *
     * @param DisasterRequest $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(DisasterRequest $request)
    {

        $disaster = new Disaster();
        $disaster->status = true;
        $disaster->name                     = $request->input('name');
        $disaster->npo_col_1                = $request->input('npo_col_1');
        $disaster->npo_col_2                = $request->input('npo_col_2');
        $disaster->npo_col_3                = $request->input('npo_col_3');
        $disaster->is_catastrophic_disaster = filled($request->input('is_catastrophic_disaster')) ?: false;
        $disaster->event_date               = $request->input('event_date');
        $disaster->save();

        // 市町村
        $disaster->localGovernments()->sync((array) $request->input('local_government_ids', []));

        $this->syncInformationLinks($disaster, (array) $request->input('information_links', []));

        // 災害種別
        $disaster->disasterTypes()->detach();
        $disaster->disasterTypes()->syncWithoutDetaching($request->input('disaster_type_ids'));


        $request->session()->flash('status', '登録に成功しました。');

        return redirect(route('disasters.index'));
    }

    /**
     * 災害情報を編集するためのフォームを表示するメソッド
     *
     * @param Request $request
     * @param Disaster $disaster
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Request $request, Disaster $disaster)
    {

        $disaster_types = DisasterType::get();

        /*
         * 災害種別
         */

        $disaster_type_ids = [];

        foreach ($disaster->disasterTypes as $disaster_type) {
            $disaster_type_ids[] = $disaster_type->id;
        }

        $disaster->disaster_type_ids = $disaster_type_ids;

        $localGovernments = $this->localGovernmentOptions();
        $selectedLocalGovernmentIds = old(
            'local_government_ids',
            $disaster->localGovernments()->pluck('local_governments.id')->all()
        );
        $informationLinks = old(
            'information_links',
            $disaster->informationLinks->map(function ($link) {
                return [
                    'description' => $link->description,
                    'url' => $link->url,
                ];
            })->all()
        );

        if (empty($informationLinks)) {
            $informationLinks = [['description' => '', 'url' => '']];
        }

		return view('disasters.edit', compact(
            'disaster',
            'disaster_types',
            'localGovernments',
            'selectedLocalGovernmentIds',
            'informationLinks'
        ));
    }

    /**
     * 災害情報を更新するためのメソッド
     *
     * @param DisasterRequest $request
     * @param Disaster $disaster
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(DisasterRequest $request, Disaster $disaster)
    {

        $disaster->status = true;
        $disaster->name                     = $request->input('name');
        $disaster->npo_col_1                = $request->input('npo_col_1');
        $disaster->npo_col_2                = $request->input('npo_col_2');
        $disaster->npo_col_3                = $request->input('npo_col_3');
        $disaster->is_catastrophic_disaster = filled($request->input('is_catastrophic_disaster')) ?: false;
        $disaster->event_date               = $request->input('event_date');
		$disaster->save();

        // 市町村
        $disaster->localGovernments()->sync((array) $request->input('local_government_ids', []));

        $this->syncInformationLinks($disaster, (array) $request->input('information_links', []));

        // 災害種別
        $disaster->disasterTypes()->detach();
        $disaster->disasterTypes()->syncWithoutDetaching($request->input('disaster_type_ids'));

        $request->session()->flash('status', '更新に成功しました。');

		return redirect(route('disasters.index'));
    }

    /**
     * 最新情報リンクを保存する。
     *
     * @param Disaster $disaster
     * @param array $links
     * @return void
     */
    private function syncInformationLinks(Disaster $disaster, array $links): void
    {
        $disaster->informationLinks()->delete();

        foreach ($links as $link) {
            $description = trim((string) ($link['description'] ?? ''));
            $url = trim((string) ($link['url'] ?? ''));

            if ($description === '' && $url === '') {
                continue;
            }

            $disaster->informationLinks()->create([
                'description' => $description,
                'url' => $url,
            ]);
        }
    }

    /**
     * 市町村の選択肢を取得する。
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function localGovernmentOptions()
    {
        return LocalGovernment::with('prefecture')
            ->conditionForPrefecture()
            ->orderBy('prefecture_code')
            ->orderBy('code')
            ->get();
    }

    /**
     * 災害情報を削除するためのメソッド
     *
     * @param DisasterRequest $request
     * @param Disaster $disaster
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(DisasterRequest $request, Disaster $disaster)
    {
//        TODO ユーザーや情報があれば削除不可にする。

        $disaster->delete();

        $request->session()->flash('status', '削除に成功しました。');

        return redirect(route('disasters.index'));
    }
}
