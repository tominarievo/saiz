<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 支援種別（大）のモデルクラス
 * 支援種別（大）テーブルのレコードに対応しています。
 */
class SupportCategory1 extends Model
{
    use HasFactory;

    /**
     * 操作カテゴリを表す定数
     */
    const CATEGORY_OPERATION = 1;

    /**
     * この支援種別（大）に関連する支援種別（中）を取得する。
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany 支援種別（大）に関連する支援種別（中）の関係性
     */
    function supportCategory2s()
    {
        return $this->hasMany(SupportCategory2::class);
    }
}
