<?php
/**
 *
 */
namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait FrontConditionTrait
{
    /**
     * 公開可能な記事に限定するクエリスコープ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConditionForFront($query)
    {
        return $query->where('status', 1)
            ->where('published_at', '<=', date('Y/m/d H:i:s'));
    }

    /**
     * published_atの年を条件にする
     * @param $query
     * @param $year
     * @return mixed
     */
    public function scopeConditionYear($query, $year)
    {
        return $query->where(DB::raw("DATE_FORMAT(published_at, '%Y')"), '=', $year);
    }

    /**
     * 全記事から年をgroup by
     * @return array
     */
    public static function yearsCondition()
    {
        $raw = "DATE_FORMAT(published_at, '%Y')";

        $list = static::select(DB::raw("{$raw} AS year"))
            ->addSelect(DB::raw("count(*) AS result_count"))
            ->conditionForFront()
            ->groupBy(DB::raw($raw))
            ->get();

        $ret = [];

//        dd($list);

        foreach ($list as $row) {
            $ret[$row->year] = $row->year." (".$row->result_count.")";
        }

        return $ret;
    }
}