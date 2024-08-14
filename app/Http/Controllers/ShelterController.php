<?php

namespace App\Http\Controllers;

use App\Disaster;
use App\DisasterType;
use App\Http\Requests\ShelterRequest;
use App\LocalGovernment;
use App\Prefecture;
use App\Shelter;
use App\SupportCategory1;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use SplFileObject;

/**
 * 支援先に関する操作を処理するコントローラー
 */
class ShelterController extends Controller
{
    const DEFAULT_SUPPORT_DAY = 3;

    /**
     * 支援先一覧を表示する
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $start_date = $request->input('start_date');
        $end_date   = $request->input('end_date');

        if (filled($start_date) && filled($end_date)) {

            $start_date_carbon = new Carbon($start_date);
            $end_date_carbon   = new Carbon($end_date);

        } else {

            $start_date_carbon = Carbon::today();
            $start_date_carbon->subDays(14);

            $end_date_carbon   = Carbon::today();

            $start_date = $start_date_carbon->format('Y/m/d');
            $end_date   = $end_date_carbon->format('Y/m/d');
        }

        $condition = new \stdClass();
        $condition->id               = $request->input('id'); //検索条件にはなく、他画面からの遷移時のみ。
        $condition->include_disabled = $request->input('include_disabled');
        $condition->keyword          = $request->input('keyword');
        $condition->prefecture_id    = $request->input('prefecture_id');
        $condition->support_category1_id = $request->input('support_category1_id');
        $condition->signal_id        = $request->input('signal_id');
        $condition->start_date       = $start_date;
        $condition->end_date         = $end_date;


        $query = Shelter::query();

        if (filled($condition->id)) {
            $query->where('id', $condition->id);
        }

        $query->when(blank($condition->include_disabled), function($q) {
            $q->where('status', true);
        });

        $query->when(filled($condition->prefecture_id), function($q) use ($condition) {
            // TODO カラム名変更
            $q->where('npo_col_11', $condition->prefecture_id);
        });

        $query->when(filled($condition->keyword), function($q) use ($condition) {

            //連続スペースを1つの半角スペースに替えた上で検索
            $keyword = trim($condition->keyword);
            $keyword = preg_replace('/ +/', ' ', $keyword);
            $keyword = preg_replace('/　+/', ' ', $keyword);

            $words = explode(' ', $keyword);

            foreach ($words as $word) {
                // バラしたキーワードはAND検索
                $q->where(function ($sub) use ($word) {
                    // 複数のカラムが必要な場合はこの中でorWhereを使うことでスコープを閉じることができる。
                    $sub->where('name', 'like', "%{$word}%")
                        ->orWhere('npo_col_2', 'like', "%{$word}%") // TODO カラム名変更 名称かな
                        ->orWhere('npo_col_16', 'like', "%{$word}%") // TODO カラム名変更 対象となる自治体
                        ->orWhere('npo_col_18', 'like', "%{$word}%") // TODO カラム名変更 備考
                        ->orWhere('npo_col_12', 'like', "%{$word}%") // TODO カラム名変更 市区町村
                        ->orWhere('npo_col_3', 'like', "%{$word}%") // TODO カラム名変更 住所
                        ->orWhere('npo_col_4', 'like', "%{$word}%"); // TODO カラム名変更 方書
                });
            }
        });

        // reportsテーブルの条件。状態、開始日、終了日
        if (filled($condition->signal_id)
            || filled($condition->support_category1_id)) {

            $query->whereHas("reports", function($report_sub) use ($condition) {

                if (isset($condition->start_date) && filled($condition->start_date)) {
                    $report_sub->where('report_date', '>=', $condition->start_date);
                }

                if (isset($condition->end_date) && filled($condition->end_date)) {
                    $report_sub->where('report_date', '<=', $condition->end_date);
                }

                if ((isset($condition->signal_id) && filled($condition->signal_id))
                    || (isset($condition->support_category1_id) && filled($condition->support_category1_id)) ) {

                    $report_sub->whereHas("supportCategory2s", function($category_sub) use ($condition) {

                        if (isset($condition->signal_id) && filled($condition->signal_id)) {
                            $category_sub->where('signal', $condition->signal_id);
                        }

                        if (isset($condition->support_category1_id) && filled($condition->support_category1_id)) {
                            $category_sub->where('support_category1_id', $condition->support_category1_id);
                        }
                    });
                }
            });

        }

        // ソート
        $query->orderBy("npo_col_2", "ASC");


        // CSV出力の場合処理終了
        if ($request->input('submit') === 'csv') {
            return $this->downloadCsv($query);
        }


        $shelters = $query->paginate(20)->appends((array)$condition);


        /*
         * APIからのアクセスの場合はここで戻す
         */

        if (Route::is('api.shelters.index')) {
            return $shelters;
        }


        $support_category1_list = SupportCategory1::get();


        if (filled($shelters)) {
            foreach ($shelters as $shelter) {

                $category1_list = [];

                foreach ($support_category1_list as $support_category1) {

                    $reports = $shelter->getSearchedReports($condition, $support_category1);

                    // 支援種別単位で一番強いシグナルを取得する。
                    $tmp_signal = $shelter->getMaxSignalForMap($condition, $support_category1);

                    $category1_list[] = [
                        "support_category1" => $support_category1,
                        "signal_id"         => $tmp_signal->id,
                        "signal_css"        => $tmp_signal->css_class,
                        "report_count"      => filled($reports) ? $reports->count() : null
                    ];
                }

                $shelter->category1_list = $category1_list;
            }
        }

        $signals = [
            1 => "OK",
            2 => "一部課題あり",
            3 => "非常に課題あり",
        ];

        View::share("signals", $signals);


        $disasters = Disaster::where('status', true)
            ->get()
            ->pluck('name', 'id')
            ->all();

        View::share("disasters", $disasters);


        // 支援種別
        $support_category1_pulldown_list = SupportCategory1::get()->pluck('name', 'id')->all();

        View::share("support_category1_pulldown_list", $support_category1_pulldown_list);


        $this->shareCommonVars();

        return view('shelters.index', compact('shelters', 'condition'));
    }

    /**
     * 支援先を新規作成する
     * @param Request $request
     * @return void
     */
    public function create(Request $request)
    {
        // 日報入力から来た場合にのみ値がある。
        $from = $request->input('from');
        View::share("from", $from);

        $shelter = new Shelter();
        $shelter->status = true;
        $shelter->lat = config('const.LATLNG.LAT');
        $shelter->lng = config('const.LATLNG.LNG');

        $this->shareCommonVars();

        return view('shelters.create', compact('shelter'));
    }

    /**
     * 共通の変数をビューと共有する
     *
     * @return void
     */
    private function shareCommonVars()
    {
        /*
         * 県
         */

        $prefectures = Prefecture::get()->pluck('name', 'name')->all();

        \View::share("prefectures", $prefectures);


        $statuses = [
            '0' => '無効',
            '1' => '有効',
        ];

        View::share("statuses", $statuses);


        /*
         * 自治体コード
         */

        $local_government_pulldowns = [];

        $local_governments = LocalGovernment::select('local_governments.id', 'local_governments.name', 'local_governments.code', 'prefectures.name AS prefecture_name')
            ->join('prefectures', 'prefecture_id', 'prefectures.id')
            ->where('government_type', LocalGovernment::GOVERNMENT_TYPE_TOWN)
            ->orderBy('prefecture_id', "ASC")
            ->get();

        foreach ($local_governments as $local_government) {
            $local_government_pulldowns[$local_government->id] = $local_government->prefecture_name .' '. $local_government->name.' ('. $local_government->code.')';
        }

        View::share("local_government_pulldowns", $local_government_pulldowns);


    }

    /**
     * 支援先を新規登録する
     *
     * @param ShelterRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ShelterRequest $request)
    {
        $shelter = new Shelter();
        $shelter->name            = $request->input('name');
        $shelter->status          = filled($request->input('status')) ?: false;
        $shelter->is_designated   = filled($request->input('is_designated')) ?: false;
        $shelter->representative  = $request->input('representative');
        $shelter->lat             = $request->input('lat');
        $shelter->lng             = $request->input('lng');

        $shelter->local_government_id = $request->input('local_government_id');

        if (filled($shelter->local_government_id)) {
            $shelter->prefecture_id = $shelter->localGovernment->prefecture_id;
            $shelter->npo_col_11    = $shelter->localGovernment->prefecture->name;
            $shelter->npo_col_12    = $shelter->localGovernment->name;
        }

        $shelter->npo_col_1          = $request->input('npo_col_1');
        $shelter->npo_col_2          = $request->input('npo_col_2');
        $shelter->npo_col_3          = $request->input('npo_col_3');
        $shelter->npo_col_4          = $request->input('npo_col_4');
        $shelter->npo_col_5          = $request->input('npo_col_5');
        $shelter->npo_col_6          = $request->input('npo_col_6');
        $shelter->npo_col_7          = $request->input('npo_col_7');
        $shelter->npo_col_8          = $request->input('npo_col_8');
        $shelter->npo_col_9          = $request->input('npo_col_9');

        $shelter->npo_col_13          = $request->input('npo_col_13');
        $shelter->npo_col_14          = $request->input('npo_col_14');
        $shelter->npo_col_15          = $request->input('npo_col_15');
        $shelter->npo_col_16          = $request->input('npo_col_16');
        $shelter->npo_col_17          = $request->input('npo_col_17');
        $shelter->npo_col_18          = $request->input('npo_col_18');

        $shelter->save();

        $request->session()->flash('status', '支援先の登録に成功しました。');

        return filled($request->input('from'))
            ? redirect(route('reports.create', ["registered_shelter_id" => $shelter->id]))
            : redirect(route('shelters.index'));
    }

    /**
     * 支援先の詳細を表示する
     *
     * @param Request $request
     * @param Shelter $shelter
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Shelter $shelter)
    {
        $this->shareCommonVars();

        return view('shelters.show', compact('shelter'));
    }

    /**
     * 支援先の編集画面を表示する
     *
     * @param Request $request
     * @param Shelter $shelter
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, Shelter $shelter)
    {
        // 日報入力から来た場合にのみ値がある。
        View::share("from", null);

        $this->shareCommonVars();

		return view('shelters.edit', compact('shelter'));
    }

    /**
     * 支援先を更新する
     *
     * @param ShelterRequest $request
     * @param Shelter $shelter
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ShelterRequest $request, Shelter $shelter)
    {
        $shelter->name            = $request->input('name');
        $shelter->status          = filled($request->input('status')) ?: false;
        $shelter->is_designated   = filled($request->input('is_designated')) ?: false;
        $shelter->representative  = $request->input('representative');
        $shelter->lat             = $request->input('lat');
        $shelter->lng             = $request->input('lng');

        $shelter->local_government_id = $request->input('local_government_id');

        if (filled($shelter->local_government_id)) {
            $shelter->prefecture_id = $shelter->localGovernment->prefecture_id;
            $shelter->npo_col_11    = $shelter->localGovernment->prefecture->name;
            $shelter->npo_col_12    = $shelter->localGovernment->name;
        }

        $shelter->npo_col_2          = $request->input('npo_col_2');
        $shelter->npo_col_3          = $request->input('npo_col_3');
        $shelter->npo_col_4          = $request->input('npo_col_4');
        $shelter->npo_col_5          = $request->input('npo_col_5');
        $shelter->npo_col_6          = $request->input('npo_col_6');
        $shelter->npo_col_7          = $request->input('npo_col_7');
        $shelter->npo_col_8          = $request->input('npo_col_8');
        $shelter->npo_col_9          = $request->input('npo_col_9');
        $shelter->npo_col_13          = $request->input('npo_col_13');
        $shelter->npo_col_14          = $request->input('npo_col_14');
        $shelter->npo_col_15          = $request->input('npo_col_15');
        $shelter->npo_col_16          = $request->input('npo_col_16');
        $shelter->npo_col_17          = $request->input('npo_col_17');
        $shelter->npo_col_18          = $request->input('npo_col_18');
		$shelter->save();

        $request->session()->flash('status', '更新に成功しました。');

		return redirect(route('shelters.show', ["shelter" => $shelter->id]));
    }

    /**
     * 支援先を削除する
     *
     * @param ShelterRequest $request
     * @param Shelter $shelter
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(ShelterRequest $request, Shelter $shelter)
    {
//        TODO ユーザーや情報があれば削除不可にする。

        $shelter->delete();

        $request->session()->flash('status', '削除に成功しました。');

        return redirect(route('shelters.index'));
    }


    private function downloadCsv($query)
    {
        $file_name = "支援先.csv";

        Storage::makeDirectory("temp_csv");

        // フォルダ権限解除のためまず仮の空のファイルを「S3ではなく実サーバー上に」作成する。
        $path = Storage::path("temp_csv/".Str::uuid().".csv", '');

//        // 改行コードをcrlfに変更
//        stream_filter_register('CrlfFilter', CrlfFilter::class);

        $file = new SplFileObject($path, 'w');

        $header = [
            "支援先id",
            "指定避難所",
            "名称",
            "名称かな",
            "市区町村コード",
            "都道府県名",
            "市区町村名",
            "住所",
            "ビル名等",
            "代表者",
            "電話番号",
            "内線番号",
            "指定支援先との重複",
            "想定収容人数",
            "対象となる町内会・自治会",
            "URL",
        ];

        // ヘッダ先頭にBOM追加(fputで出力するとストリームフィルタが行と認識してしまうのでここでセット)
        $header[0] = "\xEF\xBB\xBF".$header[0];

        // 1行のみ
        $file->fputcsv($header);


        $query->chunk(100, function($shelters) use ($file) {

            foreach ($shelters as $shelter) {

                $line = [];

                $line[] = $shelter->id;
                $line[] = $shelter->is_designated ? '1' : '';
                $line[] = $shelter->name;
                $line[] = $shelter->npo_col_2;
                $line[] = $shelter->npo_col_10;
                $line[] = $shelter->npo_col_11;
                $line[] = $shelter->npo_col_12;
                $line[] = $shelter->npo_col_3;
                $line[] = $shelter->npo_col_4;

                $line[] = $shelter->representative;
                $line[] = $shelter->npo_col_8;
                $line[] = $shelter->npo_col_9;
                $line[] = $shelter->npo_col_14;
                $line[] = $shelter->npo_col_15;
                $line[] = $shelter->npo_col_16;
                $line[] = $shelter->npo_col_17;

                $file->fputcsv($line);
                $file->fflush();
            }

        });

        $headers = array(
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$file_name.'"',
        );

        return response()->download($path, $file_name, $headers);
    }
}
