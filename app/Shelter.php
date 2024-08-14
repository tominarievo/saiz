<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 避難所モデル
 * 
 * このモデルは避難所に関する情報を扱います。
 */
class Shelter extends Model
{
    use HasFactory;

    /**
     * この避難所に関連する災害種別を取得する。
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany 避難所に関連する災害種別の関係性
     */
    public function disasterTypes() {
        return $this->belongsToMany(DisasterType::class, 'shelter_disaster_type');
    }

    /**
     * この避難所に関連する報告を取得する。
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany 避難所に関連する報告の関係性
     */
    public function reports() {
        return $this->hasMany(Report::class);
    }

    /**
     * この避難所に関連する組織を取得する。
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany 避難所に関連する組織の関係性
     */
    public function organizations() {
        return $this->belongsToMany(Organization::class);
    }

    /**
     * この避難所に関連する地方自治体を取得する。
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo 避難所に関連する地方自治体の関係性
     */
    public function localGovernment() {
        return $this->belongsTo(LocalGovernment::class)->withDefault(new LocalGovernment());
    }

    /**
     * 一番レベルの高いシグナルを取得する。
     * 
     * @param object $condition 条件に関するオブジェクト
     * @param SupportCategory1|null $support_category1 サポートカテゴリ1のオブジェクト（デフォルトはnull）
     * @return array 最高のシグナルを含む配列
     */
    public function getMaxSignal($condition, SupportCategory1 $support_category1 = null)
    {
        $query = Report::where('shelter_id', $this->id);

        if (isset($condition->start_date) && filled($condition->start_date)) {
            $query->where('report_date', '>=', $condition->start_date);
        }

        if (isset($condition->end_date) && filled($condition->end_date)) {
            $query->where('report_date', '<=', $condition->end_date);
        }

        $reports = $query->orderBy("report_date", "DESC")
            ->get();

        if (blank($reports)) {
            return [
                Signal::getSignal(Signal::NO_SIGNAL),
                null
            ];
        }

        $ret_signal = Signal::SIGNAL_INFO;
        $ret_report = null;

        foreach ($reports as $report) {

            foreach ($report->supportCategory2s as $category) {

                // 支援種別(大)が指定されている場合は一致しているデータ以外はスキップ
                if ($support_category1 && ($category->support_category1_id != $support_category1->id)) {
                    continue;
                }

                if ($category->pivot->signal == Signal::NO_SIGNAL) {
                    continue;
                }

                if ($category->pivot->signal > $ret_signal) {
                    $ret_signal = $category->pivot->signal;
                    $ret_report = $report;
                }
            }
        }

        return [
            Signal::getSignal($ret_signal),
            $ret_report
        ];
    }

    /**
     * マップ用に一番レベルの高いシグナルを取得する。
     * 
     * @param object $condition 条件に関するオブジェクト
     * @param SupportCategory1|null $support_category1 サポートカテゴリ1のオブジェクト（デフォルトはnull）
     * @return array 最高のシグナルを含む配列
     */
    public function getMaxSignalForMap($condition, SupportCategory1 $support_category1 = null)
    {
        $query = Report::where('shelter_id', $this->id);

        if (isset($condition->start_date) && filled($condition->start_date)) {
            $query->where('report_date', '>=', $condition->start_date);
        }

        if (isset($condition->end_date) && filled($condition->end_date)) {
            $query->where('report_date', '<=', $condition->end_date);
        }

        $reports = $query->orderBy("report_date", "DESC")
            ->get();

        if (blank($reports)) {
            return Signal::getSignal(Signal::NO_SIGNAL);
        }

        // 中分類1つずつにそれぞれの最新のものを持っておく。
        $sub_category_list = [];

        foreach ($reports as $report) {

            foreach ($report->supportCategory2s as $sub_category) {

                // 支援種別(大)が指定されている場合は一致しているデータ以外はスキップ
                if ($support_category1 && ($sub_category->support_category1_id != $support_category1->id)) {
                    continue;
                }

                if ($sub_category->pivot->signal == Signal::NO_SIGNAL) {
                    continue;
                }

                // 中分類毎に最新の1件が取得できれば良いので2件目以降の同じ中分類はスキップ
                if (isset($sub_category_list[$sub_category->id])) {
                    continue;
                }

                $sub_category_list[$sub_category->id] = [
                    "support_category1_id" => $sub_category->support_category1_id,
                    "report_date"          => $report->report_date,
                    "signal"               => $sub_category->pivot->signal
                ];

            }
        }

        /*
         * 大分類ごとの最大の信号を取得する。
         * ここでは中分類に一つでも黄色、オレンジがあればそちらを優先する。
         */

        $tmp_category_list = [];

        foreach ($sub_category_list as $el) {

            if ( ! isset($tmp_category_list[$el["support_category1_id"]])) {
				$tmp_category_list[$el["support_category1_id"]] = [
                    "id"     => $el["support_category1_id"],
                    "signal" => $el["signal"]
                ];
            }

            $max_signal = $tmp_category_list[$el["support_category1_id"]]["signal"];

			if ($max_signal < $el["signal"]) {
                $tmp_category_list[$el["support_category1_id"]]["signal"] = $el["signal"];
            }
        }

        /*
         * 戻すシグナルの決定
         */

        $ret_signal = Signal::SIGNAL_INFO;

        // 大分類の指定があればここで、指定した大分類の結果のみ取得する。
        if ($support_category1) {

            if (isset($tmp_category_list[$support_category1->id])) {
                return Signal::getSignal($tmp_category_list[$support_category1->id]["signal"]);
    		} else {
                return Signal::getSignal(Signal::NO_SIGNAL);
            }
        }

        foreach ($tmp_category_list as $item) {
            if ($ret_signal < $item["signal"]) {
                $ret_signal = $item["signal"];
            }
        }

        return Signal::getSignal($ret_signal);
    }

    /**
     * reportsを条件により検索した結果を全件取得する。
     * conditionの定義があいまいなので注意。
     * 
     * @param object $condition 条件に関するオブジェクト
     * @param SupportCategory1 $support_category1 サポートカテゴリ1のオブジェクト
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection 報告の検索結果を含む配列
     */
    public function getSearchedReports($condition, SupportCategory1 $support_category1)
    {
        $query = Report::where('shelter_id', $this->id);

        if (isset($condition->start_date) && filled($condition->start_date)) {
            $query->where('report_date', '>=', $condition->start_date);
        }

        if (isset($condition->end_date) && filled($condition->end_date)) {
            $query->where('report_date', '<=', $condition->end_date);
        }

        if (isset($condition->disaster_id) && filled($condition->disaster_id)) {
            $query->where('disaster_id', $condition->disaster_id);
        }

        $query->whereHas('supportCategory2s', function($category_query) use ($support_category1) {
            $category_query->where('support_category1_id', $support_category1->id);
        });

        return $query->get();
    }
}
