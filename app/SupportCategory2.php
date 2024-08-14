<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 支援種別（中）のモデルクラス
 * 
 * 支援種別（中）テーブルのレコードに対応しています。
 */
class SupportCategory2 extends Model
{
    use HasFactory;

    /**
     * この支援種別（中）に関連する支援種別（大）を取得する。
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo 支援種別（中）に関連する支援種別（大）の関係性
     */
    public function supportCategory1()
    {
        return $this->belongsTo(SupportCategory1::class)->withDefault(new SupportCategory1());
    }
}
