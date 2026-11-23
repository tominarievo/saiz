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

        $shelter_statuses = Shelter::query()->pluck('status', 'id')->all();

        $reports_by_shelter = Report::with('supportCategory2s')
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->get()
            ->groupBy('shelter_id');

        $signal_cache = $this->buildSignalCache($reports_by_shelter);

        $count_list = $this->buildCountList(
            $support_category1_list,
            $reports_by_shelter,
            $shelter_statuses,
            $condition
        );

        View::share("count_list", $count_list);


        /*
         * 地図表示用
         */

        $shelter_qury = Shelter::query();

        if (blank($condition->show_closed_shelter) || ($condition->show_closed_shelter === 'false')) {
            $shelter_qury->where('status', true);
        }

        $shelters = $shelter_qury->get();

        if (filled($shelters)) {

            foreach ($shelters as $shelter) {

                $signal_data = $signal_cache[$shelter->id] ?? [
                    'overall' => Signal::NO_SIGNAL,
                    'by_category' => [],
                ];

                $signal = Signal::getSignal($signal_data['overall']);

                // conditionは上のテーブルと共通
                $shelter->signal_id  = $signal->id;
                $shelter->signal_css = $signal->css_class;

                $category1_list = [];

                foreach ($support_category1_list as $support_category1) {

                    $tmp_signal = Signal::getSignal(
                        $signal_data['by_category'][$support_category1->id] ?? Signal::NO_SIGNAL
                    );

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
     * @param mixed $support_category1
     * @param mixed $condition
     * @return \App\Report|null
     */
    private function getLastReport($support_category1, $condition)
    {
        $report_query = Report::with('validSupportCategory2s')
            ->whereHas('shelter', function ($shelter_query) {
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
     * 避難所ごとの最大シグナルを計算する。
     *
     * @param \Illuminate\Support\Collection $reports_by_shelter
     * @return array<int, array{overall: int, by_category: array<int, int>}>
     */
    private function buildSignalCache($reports_by_shelter): array
    {
        $cache = [];

        foreach ($reports_by_shelter as $shelter_id => $reports) {

            $latest_sub_categories = [];

            foreach ($reports as $report) {
                foreach ($report->supportCategory2s as $support_category2) {

                    $signal = (int) $support_category2->pivot->signal;

                    if ($signal === Signal::NO_SIGNAL) {
                        continue;
                    }

                    if (isset($latest_sub_categories[$support_category2->id])) {
                        continue;
                    }

                    $latest_sub_categories[$support_category2->id] = [
                        'support_category1_id' => (int) $support_category2->support_category1_id,
                        'signal' => $signal,
                    ];
                }
            }

            $by_category = [];

            foreach ($latest_sub_categories as $entry) {
                $by_category[$entry['support_category1_id']] = max(
                    $by_category[$entry['support_category1_id']] ?? 0,
                    $entry['signal']
                );
            }

            if ($by_category === []) {
                $overall = count($reports) === 0 ? Signal::NO_SIGNAL : Signal::SIGNAL_INFO;
            } else {
                $overall = max(Signal::SIGNAL_INFO, max($by_category));
            }

            $cache[$shelter_id] = [
                'overall' => $overall,
                'by_category' => $by_category,
            ];
        }

        return $cache;
    }

    /**
     * 支援種別ごとの日別シグナル件数と最終日報を計算する。
     *
     * @param \Illuminate\Support\Collection $support_category1_list
     * @param \Illuminate\Support\Collection $reports_by_shelter
     * @param array<int, mixed> $shelter_statuses
     * @param mixed $condition
     * @return array<int, \stdClass>
     */
    private function buildCountList($support_category1_list, $reports_by_shelter, array $shelter_statuses, $condition): array
    {
        $start_date = filled($condition->start_date)
            ? Carbon::parse($condition->start_date)->toDateString()
            : null;
        $end_date = filled($condition->end_date)
            ? Carbon::parse($condition->end_date)->toDateString()
            : null;

        $count_list = [];

        foreach ($support_category1_list as $support_category1) {

            $category1_element = new \stdClass();
            $category1_element->support_category1 = $support_category1;
            $category1_element->list = [];

            $last_report = $this->getLastReport($support_category1, $condition);
            $last_report_signal = $last_report ? $last_report->getStrongestSignal($support_category1->id) : null;

            $shelter_signal_unique_set = [];

            foreach ($reports_by_shelter as $shelter_id => $reports) {

                if (!array_key_exists($shelter_id, $shelter_statuses)) {
                    continue;
                }

                foreach ($reports as $report) {

                    $report_date = $report->getRawOriginal('report_date');
                    $report_date = $report_date === null ? null : substr((string) $report_date, 0, 10);

                    if ($this->reportInDateRange($report_date, $start_date, $end_date)
                        && $this->reportMatchesDisaster($report, $condition)) {

                        foreach ($report->supportCategory2s as $support_category2) {

                            if ((int) $support_category2->support_category1_id !== (int) $support_category1->id) {
                                continue;
                            }

                            $signal = $support_category2->pivot->signal;
                            $shelter_unique_key = "{$shelter_id}.{$report->report_date}.{$signal}";

                            if (isset($shelter_signal_unique_set[$shelter_unique_key])) {
                                continue;
                            }

                            $shelter_signal_unique_set[$shelter_unique_key] = true;

                            $key = "{$report->report_date}.{$signal}";
                            $category1_element->list[$key] = ($category1_element->list[$key] ?? 0) + 1;
                        }
                    }

                }
            }

            $category1_element->last_report = $last_report;
            $category1_element->last_report_signal = $last_report_signal;

            $count_list[] = $category1_element;
        }

        return $count_list;
    }

    /**
     * 日報が日付範囲内か判定する。
     *
     * @param mixed $report_date
     * @param string|null $start_date
     * @param string|null $end_date
     * @return bool
     */
    private function reportInDateRange($report_date, ?string $start_date, ?string $end_date): bool
    {
        if ($report_date === null) {
            return false;
        }

        $report_date = substr((string) $report_date, 0, 10);

        if ($start_date !== null && $report_date < $start_date) {
            return false;
        }

        if ($end_date !== null && $report_date > $end_date) {
            return false;
        }

        return true;
    }

    /**
     * 日報が災害条件に一致するか判定する。
     *
     * @param \App\Report $report
     * @param mixed $condition
     * @return bool
     */
    private function reportMatchesDisaster(Report $report, $condition): bool
    {
        return !filled($condition->disaster_id)
            || (int) $report->disaster_id === (int) $condition->disaster_id;
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
