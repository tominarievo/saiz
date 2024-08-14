<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * シグナルモデル
 * 
 * このモデルはシグナルに関する情報を扱います。
 */
class Signal extends Model
{
    use HasFactory;

    // シグナルの定数定義
    const SIGNAL_INFO = 1;
    const SIGNAL_WARNING = 2;
    const SIGNAL_DANGER = 3;
    const NO_SIGNAL = 9;


    /**
     * シグナルの情報を配列で取得します。
     * 
     * @return array シグナルの情報を含む配列
     */
    public static function getList()
    {
        // シグナルのラベルとCSSクラスの対応関係
        $signals = [
            3 => "非常に課題あり",
            2 => "一部課題あり",
            1 => "OK",
        ];

        // シグナルごとのCSSクラス
        $css_class = [
            3 => "danger",
            2 => "warning",
            1 => "info",
        ];

        $ret = [];

        foreach ($signals as $signal => $label) {

            $tmp = new \stdClass();
            $tmp->id        = $signal;
            $tmp->label     = $label;
            $tmp->css_class = $css_class[$signal];

            $ret[] = $tmp;
        }

        return $ret;
    }


    /**
     * 指定されたシグナルIDに対応するシグナル情報を取得します。
     * 
     * @param int $signal_id シグナルID
     * @return object|null 指定されたシグナルIDに対応するシグナル情報を含むオブジェクト。存在しない場合はnullを返します。
     */
    public static function getSignal($signal_id)
    {
        if ($signal_id == static::NO_SIGNAL) {
            $no_signal = new \stdClass();
            $no_signal->id        = static::NO_SIGNAL;
            $no_signal->label     = "";
            $no_signal->css_class = "gray";

            return $no_signal;
        }

        $list = static::getList();

        foreach ($list as $signal) {
            if ($signal->id == $signal_id) {
                return $signal;
            }
        }
    }
}
