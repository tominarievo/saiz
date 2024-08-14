<?php

/**
 * このファイルは、都道府県情報を管理するためのモデルクラスです。
 *
 * PHP version 7
 *
 * @category Models
 * @package  App
 */

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Prefectureクラス
 *
 * このクラスは、都道府県情報を管理するためのモデルクラスです。
 *
 * @category Models
 * @package  App
 */
class Prefecture extends Model
{
    use HasFactory;

    /**
     * コードから都道府県レコードのIDを取得します。
     *
     * @param string $code 都道府県コード
     * @return int|null 都道府県レコードのID。見つからない場合はnullを返します。
     */
    public function getPrefectureIdByCode($code)
    {
        $prefecture = self::where('prefecture_code', $code)->first();

        return !empty($prefecture)
            ? $prefecture->id
            : null;
    }
}
