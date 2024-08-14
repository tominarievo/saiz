<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\Disaster;
use App\Information;
use App\OrganizationClosureTree;
use App\Report;
use App\ReportSupportCategory2;
use App\ResourceDraft;
use App\Shelter;
use App\Signal;
use App\SupportCategory1;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

/**
 * ホーム画面のコントローラークラス
 */
class HomeController extends Controller
{
    /**
     * アプリケーションのダッシュボードを表示します。
     *
     * @param  \Illuminate\Http\Request  $request  リクエスト
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $type = $request->input('type');

        $start_date = $request->input('start_date');
        $end_date   = $request->input('end_date');

        $timeline_disaster_id   = $request->input('timeline_disaster_id');

        if (filled($start_date) && filled($end_date)) {

            $start_date_carbon = new Carbon($start_date);
            $end_date_carbon   = new Carbon($end_date);

        } else {

            $start_date_carbon = Carbon::today();
            $start_date_carbon->subDays(2);

            $end_date_carbon   = Carbon::today();

            $start_date = $start_date_carbon->format('Y/m/d');
            $end_date   = $end_date_carbon->format('Y/m/d');
        }

        // 日数差を計測
        $day_diff = $start_date_carbon->diffInDays($end_date_carbon);

        $day_list = [];

        $tmp_date = $end_date_carbon;

        for ($i = 0; $i < ($day_diff + 1); $i++) {

            $tmp = new Carbon($tmp_date->format('Y/m/d'));

            $day_list[] = $tmp;

            $tmp_date->subDay();
        }


        View::share("day_list"  , $day_list);
        View::share("start_date", $start_date);
        View::share("end_date"  , $end_date);


        $disasters = Disaster::where('status', true)
            ->orderBy('event_date', 'DESC')
            ->get()
            ->pluck('name', 'id')
            ->all();

        View::share("disasters", $disasters);


        $condition = new \stdClass();
        $condition->start_date = $start_date;
        $condition->end_date   = $end_date;
        $condition->disaster_id = $request->input('disaster_id');
        $condition->show_closed_shelter = $request->input('show_closed_shelter');
        $condition->timeline_disaster_id = $request->input('timeline_disaster_id');

        View::share("condition", $condition);


        /*
         * シグナル表示
         */

        $support_category1_list = SupportCategory1::get();

        $count_list = [];

        foreach ($support_category1_list as $support_category1)
        {
            $category1_element = new \stdClass();
            $category1_element->support_category1 = $support_category1;
            $category1_element->list      = [];

            // 最後の日報を取得する。
            $last_report = $this->getLastReport($support_category1, $condition);

            $category1_element->last_report        = $last_report;
            $category1_element->last_report_signal = $last_report ? $last_report->getStrongestSignal($support_category1->id) : null;

            $query = Shelter::query();

            $query->whereHas("reports", function($report_sub_q) use ($condition, $support_category1) {

                $report_sub_q->where('report_date', '>=', $condition->start_date);

                $report_sub_q->where('report_date', '<=', $condition->end_date);

                $report_sub_q->when(filled($condition->disaster_id), function($q) use ($condition) {
                    $q->where('disaster_id', $condition->disaster_id);
                });

                $report_sub_q->whereHas('supportCategory2s', function($category_query) use ($support_category1) {
                    $category_query->where('support_category1_id', $support_category1->id);
                });
            });

            $shelters = $query->get();

            $shelter_signal_unique_set = [];

            foreach ($shelters as $shelter) {

                // あらためて支援先の日報を検索
                foreach ($shelter->getSearchedReports($condition, $support_category1) as $report) {

                    // 日報の中間テーブルをループし、シグナルをカウントする。
                    foreach ($report->supportCategory2s as $report_category2) {

                        // 支援種別(大)に絞り込まれていない支援種別(中)のループなので、ループ中の支援種別(大)に絞り込む
                        if ($report_category2->support_category1_id != $support_category1->id) {
                            continue;
                        }

                        $shelter_unique_key = "{$shelter->id}.{$report->report_date}.{$report_category2->pivot->signal}";

                        // 支援先＋日付＋シグナルのカウントは1度だけとする。Setを使ってチェック。
                        if (Arr::has($shelter_signal_unique_set, $shelter_unique_key)) {
                            continue;
                        } else {
                            Arr::set($shelter_signal_unique_set, $shelter_unique_key, /* ダミー */true);
                        }

                        // このキーは日付＋シグナル
                        $key = "{$report->report_date}.{$report_category2->pivot->signal}";

                        if ( ! Arr::has($category1_element->list, $key)) {

                            Arr::set($category1_element->list, $key, 0);
                        }

                        $count = Arr::get($category1_element->list, $key);
                        Arr::set($category1_element->list, $key, $count + 1);
                    }
                }
            }

            $count_list[] = $category1_element;
        }

        View::share("count_list", $count_list);


        /*
         * 地図表示用
         */

        $shelter_qury = Shelter::query();

        if (blank($condition->show_closed_shelter) || ($condition->show_closed_shelter === 'false')) {
            $shelter_qury->where('status', true);
        }

        $shelters = $shelter_qury->get();

        // 地図用検索条件（空)
        $map_condition = new \stdClass();

        if (filled($shelters)) {

            foreach ($shelters as $shelter) {

                $signal = $shelter->getMaxSignalForMap($map_condition);

                // conditionは上のテーブルと共通
                $shelter->signal_id  = $signal->id;
                $shelter->signal_css = $signal->css_class;

                $category1_list = [];

                foreach ($support_category1_list as $support_category1) {

                    $tmp_signal = $shelter->getMaxSignalForMap($map_condition, $support_category1);

                    $category1_list[] = [
                        "id"         => $support_category1->id,
                        "label"      => $support_category1->name,
                        "signal_id"  => $tmp_signal->css_class,
                        "signal_css" => $tmp_signal->css_class,
                    ];
                }

                $shelter->category1_list = $category1_list;
            }
        }

        View::share("shelters", $shelters);

        // タイムラインの取得
        $this->getTimeline($timeline_disaster_id);


        $information_list = Information::conditionForFront()
            ->orderBy('published_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->take(5)
            ->get();

        return view('home', compact('type', 'information_list'));
    }

    /**
     * 支援種別の一番新しい日報を取得する。
     *
     * @param  mixed  $support_category1  支援種別
     * @param  mixed  $condition  条件
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    private function getLastReport($support_category1, $condition)
    {
        $report_query = Report::whereHas('shelter', function ($shelter_query) {
            $shelter_query->where('status', true);
        })->whereHas('supportCategory2s', function ($sub_query) use ($support_category1) {
            $sub_query->where('support_category1_id', $support_category1->id)
                ->where('signal', '!=', Signal::NO_SIGNAL);
        });

        // 災害情報の指定がある場合
        if (filled($condition->disaster_id)) {
            $report_query->where('disaster_id', $condition->disaster_id);
        }

        return $report_query->orderBy('report_date', "DESC")
            ->first();
    }

    /**
     * タイムライン情報を取得する。
     *
     * @param  mixed  $timeline_disaster_id  タイムラインの災害ID
     * @return void
     */
    private function getTimeline($timeline_disaster_id): void
    {
        $report_query = Report::query();

        if (!Auth::user()->isAdmin()) {
            // 団体ユーザーでログインし、メニューからの遷移時は支援団体の初期値は自身の団体
            $report_query->where("organization_id", Auth::user()->organization_id);
        }

        if (filled($timeline_disaster_id)) {
            $report_query->where("disaster_id", $timeline_disaster_id);
        }

        $timeline_reports = $report_query->take(10)
            ->orderBy('report_date', "DESC")
            ->get();

        View::share("timeline_reports", $timeline_reports);
    }
}
