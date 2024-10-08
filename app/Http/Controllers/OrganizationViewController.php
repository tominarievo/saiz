<?php

namespace App\Http\Controllers;

use App\Disaster;
use App\Organization;
use App\Plan;
use App\PlanComment;
use App\Report;
use App\ReportSupportCategory2;
use App\Shelter;
use App\SupportCategory1;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

/**
 * 支援団体ビュー画面のコントローラー
 */
class OrganizationViewController extends Controller
{
    /**
     * リソースの一覧を表示する。
     *
     * @param  \Illuminate\Http\Request  $request  リクエスト
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $timeline_disaster_id   = $request->input('timeline_disaster_id');

        // 日付範囲(仮で作成しているが2024/02/05現在未使用)
        $gant_start_date      =  $request->input('gant_start_date');
        $gant_end_date        =  $request->input('gant_end_date');

        $support_category1_id =  filled($request->input('support_category1_id'))
            ? $request->input('support_category1_id')
            : SupportCategory1::CATEGORY_OPERATION ;

        if (blank($gant_start_date)) {

            $start = new Carbon();
            $gant_start_date = $start->format('Y/m/d');
        }

        if (blank($gant_end_date)) {

            $end = new Carbon();
            $end->addMonths(3);
            $gant_end_date = $end->format('Y/m/d');
        }

        $condition = new \stdClass();
        $condition->organization_id           = $request->input('organization_id');
        $condition->include_disabled_shelters = $request->input('include_disabled_shelters');
        $condition->timeline_disaster_id      = $request->input('timeline_disaster_id');

        $condition->gant_start_date      = $gant_start_date;
        $condition->gant_end_date        = $gant_end_date;
        $condition->support_category1_id = $support_category1_id;

        // 団体ログインの場合は自団体
        if ( ! Auth::user()->isAdmin() && blank($condition->organization_id)) {
            $condition->organization_id =Auth::user()->organization_id;
        }

        View::share("condition", $condition);

        /*
         * タイムライン情報の取得
         */

        $this->getTimeline($condition);



        $organizations = Organization::get()
            ->pluck('name', 'id')
            ->all();

        View::share("organizations", $organizations);

        // 支援種別大
        $support_category1_ids = SupportCategory1::get()
            ->pluck('name', 'id')
            ->all();

        View::share("support_category1_ids", $support_category1_ids);


        $disasters = Disaster::where('status', true)
            ->get()
            ->pluck('name', 'id')
            ->all();

        View::share("disasters", $disasters);

        /*
         * ガントチャート用のソースを作成する。
         */

        $sources = [];

        if (filled($condition->organization_id)) {

            $organization = Organization::findOrFail($condition->organization_id);

            // 最初に日報をセット
            $sources[] = $this->makeReportSources($condition);


            // 予定をセット
            $plans = $this->makePlanSources($organization, $condition);

            if (filled($plans)) {
                $sources = array_merge($sources, $plans);
            }

            $dummy = new Carbon();
            $dummy->addYears(1);

            // 未来日付のカレンダー部分が表示されないjquery.Ganttの不具合対応のため非表示の行を追加する。
            // view上でcssを使用して非表示にしている。
            $sources[] = [
                "values" => [
                    "from"        => Carbon::today()->format("Y-m-d"),
                    "to"          => $dummy->format("Y-m-d"),
                    "dataObj" => [
                        "from"        => Carbon::today()->format("Y-m-d"),
                        "to"          => $dummy->format("Y-m-d"),
                        "customClass" => "ganttNone"
                    ],
                ]
            ];
        }

        View::share("sources", $sources);


        return view('organization_views.index');
    }

    /**
     * ガントチャートに表示する日報情報を作成する。
     * 同じ日に複数の日報があるので、数字で表現している。
     * @param $condition
     * @return array
     */
    private function makeReportSources($condition)
    {
        $report_query = Report::select(
            "id",
            "report_date",
            "organization_id",
            "shelter_id"
        )->whereHas("supportCategory2s", function($q) use ($condition) {
            $q->where('support_category1_id', $condition->support_category1_id);
        });

//        $report_query->where('report_date', ">=", Carbon::today());

        if (filled($condition->organization_id)) {
            $report_query->where('organization_id', $condition->organization_id);
        }

        $category1_reports = $report_query->orderBy("report_date", "ASC")
            ->orderBy("id", "DESC")
            ->get();

        $tmp_values = [];

        $report_day_list = [];

        // ここではまだ同じ大分類の同日に複数のレコードを持っている。
        foreach ($category1_reports as $i => $report) {

            if ( ! isset($report_day_list[$report->report_date])) {
                $report_day_list[$report->report_date] = [
                    "count" => 0,
                    "category2_labels" => []
                ];
            }

            $report_day_list[$report->report_date]["count"] += 1;

            // 大分類で絞り込んだ中分類情報
            $category2s = $report->getSubCategories($condition->support_category1_id);

            foreach ($category2s as $category2) {

                if ( ! in_array($category2->name, $report_day_list[$report->report_date]["category2_labels"])) {
                    $report_day_list[$report->report_date]["category2_labels"][] = $category2->name;
                }
            }

        }

        foreach ($report_day_list as $date => $obj) {

            $report_search_option = [
                "support_category1_id" => $condition->support_category1_id,
                "organization_id" => filled($condition->organization_id) ? $condition->organization_id : null,
                "start_date" => $date,
                "end_date"   => $date
            ];

            $tmp_value = [
                "isReport"    => true,
                "reportSearchUrl" => route("reports.index", $report_search_option),
                "from"        => $date,
                "to"          => $date,
                "label"       => $obj["count"],
                "desc"        => "".implode("<br>", $obj["category2_labels"])."",
                "customClass" => "ganttRed"
            ];

            // onItemClick用に自身を持つ
            $tmp_value["dataObj"] = $tmp_value;

            $tmp_values[] = $tmp_value;
        }

        return [
            "name"   => "　[活動報告]",
            "desc"   => "",
            "values" => $tmp_values
        ];
    }

    /**
     * タイムライン情報を取得する
     * @param $condition
     */
    private function getTimeline($condition): void
    {
        $comment_columns = [
            'id',
            DB::raw("'plan_comments' AS table_type"),
            'comment',
            'post_datetime AS target_datetime',
        ];

        $report_columns = [
            'id',
            DB::raw("'reports' AS table_type"),
            'comment',
            'report_date AS target_datetime',
        ];

        $comment_query = PlanComment::select($comment_columns)
            ->whereHas('plan', function($q) use ($condition) {
                if (filled($condition->organization_id)) {
                    $q->where("organization_id", $condition->organization_id);
                }
            });


        $report_query = Report::select($report_columns);

        if (!Auth::user()->isAdmin()) {
            // 団体ユーザーでログインし、メニューからの遷移時は支援団体の初期値は自身の団体
            $report_query->where("organization_id", Auth::user()->organization_id);
        }

        if (filled($condition->timeline_disaster_id)) {
            $report_query->where("disaster_id", $condition->timeline_disaster_id);
        }

        if (filled($condition->organization_id)) {
            $report_query->where("organization_id", $condition->organization_id);
        }

        $report_query->unionAll($comment_query);

        $timeline_reports = $report_query->orderBy('target_datetime', "DESC")
            ->orderBy('id', "DESC")
            ->paginate(10);

        $timeline_reports->appends((array)$condition);


        foreach ($timeline_reports as &$timeline_report) {

            if ($timeline_report->table_type === "plan_comments") {
                $timeline_report->plan_comment = PlanComment::find($timeline_report->id);
            } elseif ($timeline_report->table_type === "reports") {
                $timeline_report->report = Report::find($timeline_report->id);
            }
        }




        View::share("timeline_reports", $timeline_reports);
    }

    /**
     * ガントチャート用の予定情報を作成する
     * @param Organization $organization
     * @param \stdClass $condition
     * @return array
     */
    private function makePlanSources(Organization $organization, \stdClass $condition)
    {
        $sources = [];

        $max_org_view_line_index = Plan::where('support_category1_id', $condition->support_category1_id)
            ->where('organization_id', $organization->id)
            ->max("org_view_line_index");

        $max_org_view_line_index = $max_org_view_line_index ?: 0;

        // 行インデックス毎にリソースを作成する。resourcesの1要素がganttの1行に当たる。
        for ($i = 0; $i <= $max_org_view_line_index; $i++) {

            $query = Plan::where('support_category1_id', $condition->support_category1_id)
                ->where('organization_id', $organization->id)
                ->where('org_view_line_index', $i);

            $plans = $query->orderBy("from", "ASC")
                ->orderBy("to", "ASC")
                ->get();

            $plan_values = [];

            foreach ($plans as $plan) {

                $description = $plan->description;

                $is_editable = (blank(Auth::user()->organization_id) || ($plan->organization_id == Auth::user()->organization_id));

                $support_category_info = $plan->getSupportCategoryInfo();

                $plan_comments = [];

                $tmp_plan_comments = $plan->planComments;

                foreach ($tmp_plan_comments as $plan_comment) {

                    $tmp = $plan_comment;

                    // コメントに組織IDが無いのでここでセット
                    $tmp->organization_id   = $plan_comment->user->organization_id;

                    $tmp->post_comment_read = $plan_comment->getCreatorRead();

                    $plan_comments[] = $tmp;
                }

                // 各予定のラベル
                $label = $plan->shelter->name;

                $tmp_value = [
                    "id"          => $plan->id,
                    "from"        => $plan->from,
                    "to"          => $plan->to,
                    "label"       => $label,
                    "desc"        => $description,
                    "shelter"      => $plan->shelter,
                    "organization" => $plan->organization,
                    "editUrl"     => $is_editable ? route("plans.edit", ["plan" => $plan->id, 'from_page' => 'organization_view']) : "",
                    "deleteUrl"   => $is_editable ? route("plans.destroy", ["plan" => $plan->id]) : "",
                    "isEditable"  => $is_editable,
                    "planComments"    => $plan_comments,
                    "customClass" => "ganttBlue",
                    "support_category_info" => $support_category_info
                ];

                // onItemClick用に自身を持つ
                $tmp_value["dataObj"] = $tmp_value;

                $plan_values[] = $tmp_value;
            }


            // 1つの行の予定セットの完成
            $tmp = [
                "org_view_line_index"   => $i,
                "name"   => " ",
                "desc"   => "",
                "values" => filled($plan_values) ? $plan_values : []
            ];

            // セット
            $sources[] = $tmp;
        }

        return $sources;
    }
}
