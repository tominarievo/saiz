<?php

namespace App\Http\Middleware;

use App\Shelter;
use Carbon\Carbon;
use Closure;

/**
 * 共通ミドルウェア
 */
class MyCommonMiddleware
{
    /**
     * リクエストの処理
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        /*
         * 左メニュー下のシグナル表示
         */

        $condition = new \stdClass();
        $condition->start_date = Carbon::today();
        $condition->end_date   = Carbon::today();

        $signal_count = [];

        $signals = [
            1 => "OK",
            2 => "一部課題あり",
            3 => "非常に課題あり",
        ];

        $css_class = [
            1 => "info",
            2 => "warning",
            3 => "danger",
        ];

        foreach ($signals as $signal => $label) {

            $query = Shelter::query();

            $query->whereHas("reports", function($report_sub_q) use ($condition, $signal) {

                $report_sub_q->where('report_date', '>=', $condition->start_date);

                $report_sub_q->where('report_date', '<=', $condition->end_date);

                $report_sub_q->whereHas('supportCategory2s', function($category_query) use ($signal) {
                    $category_query->where('signal', $signal);
                });
            });

            $shelters = $query->get();

            $signal_count[] = [
                "id"    => $signal,
                "count" => $shelters->count(),
                "label" => $label,
                "css_class" => $css_class[$signal]
            ];
        }

        \View::share("signal_count", $signal_count);

        return $next($request);
    }
}
