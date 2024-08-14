<?php

namespace App\Http\Controllers;

use App\Disaster;
use App\DisasterType;
use App\Http\Requests\ReportRequest;
use App\Logics\CrlfFilter;
use App\Organization;
use App\Prefecture;
use App\Report;
use App\Shelter;
use App\SupportCategory1;
use App\Tag;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use SplFileObject;

/**
 * 日報に関する操作を処理するコントローラー
 */
class ReportController extends Controller
{
    /**
     * 一覧画面を表示する
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $from = $request->input("from");

        $condition = new \stdClass();
        $condition->shelter_id      = $request->input('shelter_id');
        $condition->writer          = $request->input('writer');
        $condition->keyword         = $request->input('keyword');
        $condition->signal_ids      = $request->input('signal_ids');
        $condition->start_date      = $request->input('start_date');
        $condition->end_date        = $request->input('end_date');
        $condition->tag_list        = $request->input('tag_list');
        $condition->support_category1_id      = $request->input('support_category1_id');
        $condition->include_disabled_shelters = $request->input('include_disabled_shelters');


        if (filled($from) && ( ! Auth::user()->isAdmin())) {
            // 団体ユーザーでログインし、メニューからの遷移時は支援団体の初期値は自身の団体
            $condition->organization_id = Auth::user()->organization_id;
        } else {
            $condition->organization_id = $request->input('organization_id');
        }

        $query = Report::orderBy('report_date', "DESC");

        $query->whereHas("organization", function($q) {
            $q->where('status', true);
        });

        $query->whereHas("shelter", function($q) use ($condition) {
            if (blank($condition->include_disabled_shelters) || ($condition->include_disabled_shelters === 'false')) {
                $q->where('status', true);
            }
        });

        // 記入者
        $query->when(filled($condition->shelter_id), function($q) use ($condition) {
            $q->where('shelter_id', $condition->shelter_id);
        });

        $query->when(filled($condition->writer), function($q) use ($condition) {
            $q->where('writer', $condition->writer);
        });

        $query->when(filled($condition->organization_id), function($q) use ($condition) {
            $q->where('organization_id', $condition->organization_id);
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
                    $sub->where('comment', 'like', "%{$word}%");
                });
            }
        });

        $query->when(filled($condition->start_date), function($q) use ($condition) {
            $q->where('report_date', '>=', $condition->start_date);
        });

        $query->when(filled($condition->end_date), function($q) use ($condition) {
            $q->where('report_date', '<=', $condition->end_date);
        });

        if ((filled($condition->signal_ids) && is_array($condition->signal_ids))
            || filled($condition->support_category1_id)) {

            $query->whereHas("supportCategory2s", function($sub) use ($condition) {

                if (filled($condition->signal_ids)) {
                    $sub->whereIn('signal', $condition->signal_ids);
                }

                if (filled($condition->support_category1_id)) {
                    $sub->where('support_category1_id', $condition->support_category1_id);
                }
            });
        }

        $query->when(filled($condition->tag_list), function($q) use ($condition) {

            // TODO tag_listの中身のjsonのチェック

            $tags = json_decode($condition->tag_list);

            foreach ($tags as $tag) {
                // バラしたキーワードはAND検索
                $q->whereHas('tags', function($q) use ($tag) {
                    $q->where('name', $tag->value);
                });
            }
        });


        if ($request->input('submit') === 'csv') {
            return $this->downloadCsv($query);
        }


        $reports = $query->paginate(15)->appends((array)$condition);

        $support_category1s = SupportCategory1::get();

        $organizations = Organization::where('status', true)
            ->get()
            ->pluck('name', 'id')
            ->all();

        View::share("organizations", $organizations);


        $shelters = Shelter::
            when((blank($condition->include_disabled_shelters) || ($condition->include_disabled_shelters === 'false')), function($q) {
                $q->where('status', true);
            })->get()
            ->pluck('name', 'id')
            ->all();

        View::share("shelters", $shelters);



        $signals = [
            1 => "OK",
            2 => "一部課題あり",
            3 => "非常に課題あり",
        ];

        View::share("signals", $signals);


        // タグ
        $tags = Tag::get()->pluck('name')->all();
        View::share("tags", $tags);


        // 支援種別
        $support_category1_pulldown_list = SupportCategory1::get()->pluck('name', 'id')->all();
        View::share("support_category1_pulldown_list", $support_category1_pulldown_list);

        $writers = filled(\Auth::user()->organization_id) ? Report::select("writer")
            ->where('organization_id', \Auth::user()->organization_id)->distinct()
            ->where('writer', '!=', "")
            ->get()
            ->pluck('writer')
            ->all()
            : [];


        $prev_writers = [];

        if (filled($writers)) {

            foreach ($writers as $writer) {
                $prev_writers[$writer] = $writer;
            }
        }


        View::share("prev_writers", $prev_writers);


        return view('reports.index', compact("condition", 'reports', 'support_category1s'));
    }

    /**
     * 日報作成画面を表示する
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $report_id = $request->input("report_id");

        // 登録画面で支援先が切り替えられたときの画面遷移で送信されるパラメータ
        $use_prev_shelter    = $request->input("usePrevShelter");
        $selected_shelter_id = $request->input("selected_shelter_id");

        $report = new Report();

        $prev_report = null;

        if (filled($report_id)) {

            /*
             * report_idが渡されている場合はコピーとして画面を表示
             */

            $report = Report::find($report_id);

            // 権限チェック
            $this->authorize('update', $report);


            // 画面上でのみ使用する値
            $report->use_prev_writer  = true;
            $report->use_prev_shelter = true;
            $report->prev_writer      = $report->writer;

            /*
             * 支援種別(大)をモデルにセットする。これをセットしておかないとview上のjsがカテゴリー(中)のチェックを外してしまうので注意。
             * 中間テーブルのデータもここで取得する。
             */

            $support_category1_ids = [];

            $support_category_values = [];

            foreach ($report->supportCategory2s as $supportCategory2) {

                if ( ! in_array($supportCategory2->support_category1_id, $support_category1_ids)) {
                    $support_category1_ids[] = $supportCategory2->support_category1_id;
                }

                // pivotカラムを画面用に保持
                $support_category_values[$supportCategory2->id]["signal"] = $supportCategory2->pivot->signal;
                $support_category_values[$supportCategory2->id]["memo"]   = $supportCategory2->pivot->memo;

            }

            $report->support_category1_ids = $support_category1_ids;
            $report->support_category_values = $support_category_values;

            $report->tag_list = $report->getTagListStr();

            // コピー時はnull
            $report->report_date = null;

        } else {

            $today = \Illuminate\Support\Carbon::today();

            if (filled(\Auth::user()->organization_id)) {

                /**
                 * 登録画面で支援先が切り替えられた場合に支援先idが渡されるので、
                 * 支援先を限定した直近の日報を前回の日報として取得する。
                 */

                if (filled($selected_shelter_id)) {

                    $prev_report = Report::where('organization_id', \Auth::user()->organization_id)
                        ->where("shelter_id", $selected_shelter_id)
                        ->orderBy('report_date', 'DESC')
                        ->first();
                } else {

                    $prev_report = Report::where('organization_id', \Auth::user()->organization_id)
                        ->orderBy('report_date', 'DESC')
                        ->first();
                }

            }

            if (filled(\Auth::user()->organization_id)) {
                $report->organization_id = \Auth::user()->organization_id;
            }

            $report->report_date      = $today->format('Y/m/d');
            $report->use_prev_writer  = true;


            // 日報から支援先登録を実行する画面遷移から戻ってきた場合
            $registered_shelter_id = $request->input('registered_shelter_id');

            if (filled($registered_shelter_id)) {
                $report->use_prev_shelter = false;
                $report->shelter_id = $registered_shelter_id;
            } else {

                if (filled($selected_shelter_id)) {

                    if (isset($use_prev_shelter) && $use_prev_shelter === "false") {
                        $report->use_prev_shelter = false;
                    } else {
                        $report->use_prev_shelter = true;
                    }

                    $report->shelter_id       = $selected_shelter_id;

                } else {
                    $report->use_prev_shelter = true;
                    $report->shelter_id       = $prev_report ? $prev_report->shelter_id : null;
                }


            }


            $report->prev_writer      = $prev_report ? $prev_report->writer : null;

            //　新規作成の場合は災害の初期値は1つ前の日報(ただし、支援先のstatusは考慮しない)
            if ($prev_report) {
                $report->disaster_id = $prev_report->disaster_id;
            }
        }


        $this->shareCommonVars($report);

        return view('reports.create', compact('report', 'prev_report'));
    }

    /**
     * 共通の変数をビューと共有する
     *
     * @param Report $report
     */
    private function shareCommonVars(Report $report)
    {
        /*
         * 県
         */

        $prefectures = Prefecture::get()->pluck('name', 'name')->all();

        \View::share("prefectures", $prefectures);


        $support_category1s = SupportCategory1::get();
        View::share("support_category1s", $support_category1s);

        $disasters = Disaster::where('status', true)
            ->orderBy('event_date', 'DESC')
            ->get()
            ->pluck('name', 'id')
            ->all();
        View::share("disasters", $disasters);

        $shelters = Shelter::where('status', true)
            ->get()
            ->pluck('name', 'id')
            ->all();
        View::share("shelters", $shelters);

        // モーダルでの検索に使用する支援先リスト
        $all_shelters_for_modal = Shelter::get();

        View::share("all_shelters_for_modal", $all_shelters_for_modal);


        $organizations = Organization::when(filled(\Auth::user()->organization_id), function($q){
            $q->where('id', \Auth::user()->organization_id);
        })->where('status', true)
        ->get()
        ->pluck('name', 'id')
        ->all();
        View::share("organizations", $organizations);


        /*
         * 前回記入の記入者を取得する。
         */

        $writers = Report::select("writer")->when(filled(\Auth::user()->organization_id), function($q){
            $q->where('organization_id', \Auth::user()->organization_id);
        })->distinct()
            ->get()
            ->pluck('writer')
            ->all();

        $prev_writers = [];

        if (filled($writers)) {

            foreach ($writers as $writer) {
                $prev_writers[$writer] = $writer;
            }
        }

        View::share("prev_writers", $prev_writers);


        $prev_shelters = [];

        if (filled(\Auth::user()->organization_id) || filled($report->organization_id)) {

            $organization_id = filled(\Auth::user()->organization_id)
                ? \Auth::user()->organization_id
                : $report->organization_id;

            $organization = Organization::find($organization_id);

            if ($organization) {
                foreach ($organization->shelters as $shelter) {
                    $prev_shelters[$shelter->id] = $shelter->name;
                }
            }

        }
        View::share("prev_shelters", $prev_shelters);

        // タグ
        $tags = Tag::get()->pluck('name')->all();
        View::share("tags", $tags);

        /*
         * 支援先モーダル用
         */

        // 災害種別
        $disaster_types = DisasterType::get();
        View::share("disaster_types", $disaster_types);
    }

    /*
     * 日報を登録する
     * @param ReportRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(ReportRequest $request)
    {
        $report = new Report();
        $report->fill($request->validated());

        $report->writer = filled($request->input('prev_writer'))
            ? $request->input('prev_writer')
            : $request->input('writer');

        // 更新者情報
        $report->updated_by_admin = $report->isUpdateByAdminUser(Auth::id());
        $report->update_user_id   = Auth::id();

        $report->save();


        /*
         * tag
         */

        $tag_list = $request->input('tag_list');

        $report->tags()->detach();

        // リクエストにはjson形式の文字列が入っているのでvalueからタグ名を取得する。
        if (filled($tag_list)) {

            $tag_json = json_decode($tag_list);

            $tag_ids = [];

            foreach ($tag_json as $tag_name) {

                $tag = Tag::firstOrCreate(["name" => $tag_name->value]);
                $tag_ids[] = $tag->id;
            }

            $report->tags()->attach($tag_ids);
        }


        /*
         * 支援種別(中)
         */

        $report->supportCategory2s()->detach();

        // 中間テーブルのカラムのネストした値をセットする
        if (filled($request->input('supportCategory2s'))) {

            $relation = [];

            $values = $request->input("support_category_values");

            foreach ($request->input('supportCategory2s') as $support_category_id) {

                if (isset($values[$support_category_id]) && isset($values[$support_category_id]["signal"])) {

                    $relation[$support_category_id] = [
                        "signal" => $values[$support_category_id]["signal"],
                        "memo"   => $values[$support_category_id]["memo"],
                    ];;
                }
            }

            $report->supportCategory2s()->attach($relation);
        }


        /*
         * 団体と支援先のリレーションの追加
         * (過去に入力した支援先を取得するために使用)
         */

        $report->organization->shelters()->syncWithoutDetaching($request->input('shelter_id'));


        // 日報の記録用ログ
        $for_json = Report::with(["tags", "supportCategory2s"])->find($report->id);
        \Log::info("日報 INSERT:".json_encode($for_json, JSON_UNESCAPED_UNICODE));


        $request->session()->flash('status', '登録に成功しました。');

        return redirect(route('reports.index'));
    }

   /*
    * 日報詳細画面を表示する
    * @param Request $request
    * @param Report $report
    * @return \Illuminate\Http\Response
    */
    public function show(Request $request, Report $report)
    {
        /*
         * 支援種別の情報を画面表示用に整理する。
         */

        $support_category1s = [];

        foreach ($report->supportCategory2s as $supportCategory2) {

            if ( ! isset($support_category1s[$supportCategory2->support_category1_id])) {

                $temp = [
                    "support_category1" => $supportCategory2->supportCategory1,
                    "support2_list"     => []
                ];

                $support_category1s[$supportCategory2->support_category1_id] = $temp;
            }

            $support_category1s[$supportCategory2->support_category1_id]["support2_list"][] = $supportCategory2;
        }

        View::share("support_category1s", $support_category1s);

        $report->tag_list = $report->getTagListStr();

        return view('reports.show', compact('report'));
    }

    /**
     * 日報編集画面を表示する
     *
     * @param ReportRequest $request
     * @param Report $report
     * @return \Illuminate\Http\Response
     */
    public function edit(ReportRequest $request, Report $report)
    {
        $this->authorize('update', $report);

        // 画面上でのみ使用する値
        $report->use_prev_writer  = true;
        $report->use_prev_shelter = true;
        $report->prev_writer      = $report->writer;

        /*
         * 支援種別(大)をモデルにセットする。これをセットしておかないとview上のjsがカテゴリー(中)のチェックを外してしまうので注意。
         * 中間テーブルのデータもここで取得する。
         */

        $support_category1_ids = [];

        $support_category_values = [];

        foreach ($report->supportCategory2s as $supportCategory2) {

            if ( ! in_array($supportCategory2->support_category1_id, $support_category1_ids)) {
                $support_category1_ids[] = $supportCategory2->support_category1_id;
            }

            // pivotカラムを画面用に保持
            $support_category_values[$supportCategory2->id]["signal"] = $supportCategory2->pivot->signal;
            $support_category_values[$supportCategory2->id]["memo"]   = $supportCategory2->pivot->memo;

        }

        $report->support_category1_ids = $support_category1_ids;
        $report->support_category_values = $support_category_values;


        $this->shareCommonVars($report);

        $report->tag_list = $report->getTagListStr();

        return view('reports.edit', compact('report'));
    }

    /*
     * 日報を更新する
     * @param ReportRequest $request
     * @param Report $report
     * @return \Illuminate\Http\Response
     */
    public function update(ReportRequest $request, Report $report)
    {
        $this->authorize('update', $report);

        $report->fill($request->validated());

        $report->writer = filled($request->input('prev_writer'))
            ? $request->input('prev_writer')
            : $request->input('writer');

		// 更新者情報
        $report->updated_by_admin = $report->isUpdateByAdminUser(Auth::id());
        $report->update_user_id   = Auth::id();

        $report->save();

        /*
         * tag
         */

        $tag_list = $request->input('tag_list');

        $report->tags()->detach();

        // リクエストにはjson形式の文字列が入っているのでvalueからタグ名を取得する。
        if (filled($tag_list)) {

            $tag_json = json_decode($tag_list);

            $tag_ids = [];

            foreach ($tag_json as $tag_name) {

                $tag = Tag::firstOrCreate(["name" => $tag_name->value]);
                $tag_ids[] = $tag->id;
            }

            $report->tags()->attach($tag_ids);
        }


        // 支援種別(中)
        $report->supportCategory2s()->detach();

        // 中間テーブルのカラムのネストした値をセットする
        if (filled($request->input('supportCategory2s'))) {

            $relation = [];

            $values = $request->input("support_category_values");

            foreach ($request->input('supportCategory2s') as $support_category_id) {

                if (isset($values[$support_category_id]) && isset($values[$support_category_id]["signal"])) {

                    $relation[$support_category_id] = [
                        "signal" => $values[$support_category_id]["signal"],
                        "memo"   => $values[$support_category_id]["memo"],
                    ];;
                }
            }

            $report->supportCategory2s()->attach($relation);
        }


        /*
         * 団体と支援先のリレーションの追加
         * (過去に入力した支援先を取得するために使用)
         */

        $report->organization->shelters()->syncWithoutDetaching($request->input('shelter_id'));


        // 日報の記録用ログ
        $for_json = Report::with(["tags", "supportCategory2s"])->find($report->id);
        \Log::info("日報 UPDATE:".json_encode($for_json, JSON_UNESCAPED_UNICODE));


        $request->session()->flash('status', '更新に成功しました。');

        return redirect(route('reports.show', ["report" => $report->id]));
    }

    /*
     * 日報を削除する
     * @param Request $request
     * @param Report $report
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Report $report)
    {
//        TODO ユーザーに権限があれば削除可能にする。

        $report->delete();

        $request->session()->flash('status', '削除に成功しました。');

        return redirect(route('reports.index'));
    }

    /*
     * 日報の検索結果をCSVでダウンロードする
     * org_view_line_indexの特定
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    private function downloadCsv($query)
    {
        $file_name = "支援日報.csv";

        Storage::makeDirectory("temp_csv");

        // フォルダ権限解除のためまず仮の空のファイルを「S3ではなく実サーバー上に」作成する。
        $path = Storage::path("temp_csv/".Str::uuid().".csv", '');

        $file = new SplFileObject($path, 'w');

        $header = [
            "日報データid",
            "支援日",
            "支援団体",
            "記入者",
            "支援先",
            "災害情報",
            "情報共有会議用メモ",
            "内部の申し送りメモ"
        ];

        $support_category1s = SupportCategory1::get();

        // 可変部分のヘッダ
        foreach ($support_category1s as $support_category1) {
            $header[] = "支援先状況_".$support_category1->name;
        }


        // ヘッダ先頭にBOM追加(fputで出力するとストリームフィルタが行と認識してしまうのでここでセット)
        $header[0] = "\xEF\xBB\xBF".$header[0];

        // 1行のみ
        $file->fputcsv($header);


        $query->chunk(100, function($records) use ($file, $support_category1s) {

            foreach ($records as $record) {

                $line = [];

                $line[] = $record->id;
                $line[] = $record->report_date;
                $line[] = $record->organization->name;
                $line[] = $record->writer;
                $line[] = $record->shelter->name;


                $line[] = $record->writer;

                $line[] = $record->comment;

                $hidden_comment = "";

                if (filled(\Auth::user()->organization_id) && (\Auth::user()->organization_id == $record->organization_id)) {
                    $hidden_comment = $record->hidden_comment;
                }

                $line[] = $hidden_comment;

                $info = $record->getSupportCategoryInfo();

                // 可変部分のヘッダ
                foreach ($support_category1s as $support_category1) {

                    if (isset($info[$support_category1->id])) {

                        $value = "";

                        switch ($info[$support_category1->id]['signal']) {

                            case 1:
                                $value .= "OK";
                                break;
                            case 2:
                                $value .= "一部課題あり";
                                break;
                            case 3:
                                $value .= "非常に課題あり";
                                break;
                        }

                        $line[] = $value; //なし

                    } else {
                        $line[] = ""; //なし
                    }




                }


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
