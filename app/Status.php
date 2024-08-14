<?php

namespace App;

/**
 * ステータスモデル
 * 
 * このモデルはステータスに関する情報を提供します。
 */
class Status
{
    /**
     * セレクトボックスのオプションを取得します。
     * 
     * @return array セレクトボックスのオプションを含む配列
     */
    public static function selectOptions()
    {
        return [
            '1' => '公開',
            '0' => '非公開'
        ];
    }
}
