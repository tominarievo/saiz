<?php

namespace App\Support;

use App\Shelter;
use Carbon\Carbon;

/**
 * 本日の支援先状況を集計する。
 */
class ShelterStatusSummary
{
    /**
     * 本日のシグナル別支援先数を返す。
     *
     * @return array<int, array{id: int, count: int, label: string, css_class: string}>
     */
    public static function today(): array
    {
        $today = Carbon::today();

        $signals = [
            1 => ['label' => 'OK', 'css_class' => 'info'],
            2 => ['label' => '一部課題あり', 'css_class' => 'warning'],
            3 => ['label' => '重大課題あり', 'css_class' => 'danger'],
        ];

        $counts = [];

        foreach ($signals as $signal => $meta) {
            $count = Shelter::query()
                ->whereHas('reports', function ($reportQuery) use ($today, $signal) {
                    $reportQuery->where('report_date', '>=', $today)
                        ->where('report_date', '<=', $today)
                        ->whereHas('supportCategory2s', function ($categoryQuery) use ($signal) {
                            $categoryQuery->where('signal', $signal);
                        });
                })
                ->count();

            $counts[] = [
                'id' => $signal,
                'count' => $count,
                'label' => $meta['label'],
                'css_class' => $meta['css_class'],
            ];
        }

        return $counts;
    }
}
