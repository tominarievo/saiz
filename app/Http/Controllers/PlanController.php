<?php

namespace App\Http\Controllers;

use App\Disaster;
use App\DisasterType;
use App\Http\Requests\PlanRequest;
use App\Http\Requests\ReportRequest;
use App\Logics\CrlfFilter;
use App\Organization;
use App\Plan;
use App\PlanComment;
use App\Prefecture;
use App\Report;
use App\Shelter;
use App\SupportCategory1;
use App\Tag;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use SplFileObject;

/**
 * 予定に関する操作を処理するコントローラー
 */
class PlanController extends Controller
{
    /**
     * 予定作成フォームを表示する
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $shelter_id = $request->input("shelter_id");
        $from       = $request->input("from");
        $from_page  = $request->input("from_page");
        $organization_id  = $request->input("organization_id");

        $report = new Plan();
        $report->shelter_id = $shelter_id;
        $report->from       = $from;
        $report->from_page  = $from_page;

        if (filled(\Auth::user()->organization_id)) {
            $report->organization_id = \Auth::user()->organization_id;
        } elseif (filled($organization_id)) {
            $report->organization_id = $organization_id;
        }

        $this->shareCommonVars($report);

        return view('plans.create', compact('report'));
    }

    /**
     * 共通の変数をビューと共有する
     *
     * @return void
     */
    private function shareCommonVars()
    {
        $support_category1s = SupportCategory1::get();
        View::share("support_category1s", $support_category1s);

        $shelters = Shelter::where('status', true)
            ->orderBy('npo_col_2', "ASC")
            ->orderBy('id', "ASC")
            ->get()
            ->pluck('name', 'id')
            ->all();
        View::share("shelters", $shelters);

        $org_query = Organization::where('status', true);

        // 支援団体ユーザーであれば自身の組織のみ
        if (filled(Auth::user()->organization_id)) {
            $org_query->where('id', Auth::user()->organization_id);
        }

        $organizations = $org_query
            ->orderBy('name', "ASC")
            ->orderBy('id', "ASC")
            ->get()
            ->pluck('name', 'id')
            ->all();

        View::share("organizations", $organizations);
    }

    /**
     * 予定を保存する
     *
     * @param PlanRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(PlanRequest $request)
    {
        DB::beginTransaction();

        try {

            $plan = new Plan();
            $plan->organization_id = \Auth::user()->organization_id ?: $request->input("organization_id");
            $plan->fill($request->validated());

            $this->setOrgViewLineIndex($plan);

            $plan->save();

            /*
             * 支援先と支援団体の紐付けを行う。
             * 紐付けが行われるタイミングは日報と予定のみ。(2024/04/26時点)
             */

            $plan->organization->shelters()->syncWithoutDetaching($plan->shelter_id);

            /*
             * 支援種別(中)
             */

            $plan->supportCategory2s()->detach();

            // 中間テーブルのカラムのネストした値をセットする
            if (filled($request->input('supportCategory2s'))) {

                $relation = [];

                foreach ($request->input('supportCategory2s') as $support_category_id) {

                    $relation[$support_category_id] = [
                        "signal" => 0, //未使用なので固定
                        "memo"   => "", //未使用なので固定
                    ];;
                }

                $plan->supportCategory2s()->attach($relation);
            }

            $request->session()->flash('status', '登録に成功しました。');

            DB::commit(); // トランザクションをコミット

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error in Information store function.', ['error' => $e->getMessage()]); // エラーログ
            abort(500);
        }

        // 戻り先を支援先ビューと支援団体ビューで切り替える。
        $from_page = $request->input("from_page");

        $url = filled($from_page) && $from_page === "organization_view"
            ? route('organization_views.index', ["organization_id" => $plan->organization_id])
            : route('shelter_views.index', ["shelter_id" => $plan->shelter_id]);

        return redirect($url);
    }

    /*
     * 予定編集フォームを表示する
     * @param ReportRequest $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(ReportRequest $request, $id)
    {
        $from_page  = $request->input("from_page");

        $plan = Plan::findOrFail($id);

        $this->authorize('update', $plan);

        $plan->from_page = $from_page;

        /*
         * 支援種別(大)をモデルにセットする。これをセットしておかないとview上のjsがカテゴリー(中)のチェックを外してしまうので注意。
         * 中間テーブルのデータもここで取得する。
         */

        $support_category1_ids = [];

        $support_category_values = [];

        foreach ($plan->supportCategory2s as $supportCategory2) {

            if ( ! in_array($supportCategory2->support_category1_id, $support_category1_ids)) {
                $support_category1_ids[] = $supportCategory2->support_category1_id;
            }

            // pivotカラムを画面用に保持
            $support_category_values[$supportCategory2->id]["signal"] =  0; //未使用なので固定
            $support_category_values[$supportCategory2->id]["memo"]   = $supportCategory2->pivot->memo;

        }

        $plan->support_category1_ids = $support_category1_ids;
        $plan->support_category_values = $support_category_values;

        $this->shareCommonVars($plan);

        return view('plans.edit', compact('plan'));
    }

    /*
     * 予定を更新する
     * @param PlanRequest $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(PlanRequest $request, $id)
    {
        DB::beginTransaction();

        try {

            $plan = Plan::findOrFail($id);

            $this->authorize('update', $plan);

            $plan->fill($request->validated());

            $this->setOrgViewLineIndex($plan);

            $plan->save();

            /*
             * 支援先と支援団体の紐付けを行う。
             * 紐付けが行われるタイミングは日報と予定のみ。(2024/04/26時点)
             */

            $plan->organization->shelters()->syncWithoutDetaching($plan->shelter_id);

            // 支援種別(中)
            $plan->supportCategory2s()->detach();

            // 中間テーブルのカラムのネストした値をセットする
            if (filled($request->input('supportCategory2s'))) {

                $relation = [];

                foreach ($request->input('supportCategory2s') as $support_category_id) {

                    $relation[$support_category_id] = [
                        "signal" => 0, //未使用なので固定
                        "memo"   => "", //未使用なので固定
                    ];;
                }

                $plan->supportCategory2s()->attach($relation);
            }

            $request->session()->flash('status', '更新に成功しました。');

            DB::commit(); // トランザクションをコミット

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error in Information store function.', ['error' => $e->getMessage()]); // エラーログ
            abort(500);
        }

        // 戻り先を支援先ビューと支援団体ビューで切り替える。
        $from_page = $request->input("from_page");

        $url = filled($from_page) && $from_page === "organization_view"
            ? route('organization_views.index', ["organization_id" => $plan->organization_id])
            : route('shelter_views.index', ["shelter_id" => $plan->shelter_id]);

        return redirect($url);
    }

    /*
     * 予定を削除する
     * @param Request $request
     * @param Plan $report
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            $plan = Plan::findOrFail($id);

            $this->authorize('update', $plan);

            // コメントを削除
            PlanComment::where('plan_id', $plan->id)
                ->delete();

            $plan->delete();

            $request->session()->flash('status', '削除に成功しました。');

            DB::commit(); // トランザクションをコミット

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error in Information store function.', ['error' => $e->getMessage()]); // エラーログ
            abort(500);
        }

        // 戻り先を支援先ビューと支援団体ビューで切り替える。
        $from_page = $request->input("from_page");

        $url = filled($from_page) && $from_page === "organization_view"
            ? route('organization_views.index', ["organization_id" => $plan->organization_id])
            : route('shelter_views.index', ["shelter_id" => $plan->shelter_id]);

        return redirect($url);
    }

    /*
     * 予定を検索する
     * org_view_line_indexの特定
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    private function setOrgViewLineIndex(Plan &$plan): void
    {
        $query = Plan::select("org_view_line_index")
            ->where('support_category1_id', $plan->support_category1_id)
            ->where('organization_id', $plan->organization_id)
            ->where('from', '<=', $plan->to)
            ->where('to', '>=', $plan->from);

        // 編集画面の場合は自身を除く
        if (filled($plan->id)) {
            $query->where('id', '<>', $plan->id);
        }

        $dupricated = $query->orderBy('org_view_line_index', "DESC")
            ->get()
            ->pluck("org_view_line_index")
            ->all();

        $next_index = 0;

        if (filled($dupricated)) {

            $max_index = $dupricated[0];

            if (count($dupricated) === $max_index + 1) {
                // 最大インデックス+1と行の数が一致している(=全ての行で期間が重なっている)場合
                $next_index = $max_index + 1;
            } else {
                // 全ての行で重なっていない場合は、空いているindexの中で1番小さい数値を取得する。
                for ($i = 0; $i < count($dupricated); $i++) {
                    if (!in_array($i, $dupricated)) {
                        $next_index = $i;
                        break;
                    }
                }
            }
        }

        $plan->org_view_line_index = $next_index;
    }
}
