<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * 災害情報の最新情報リンク
 */
class DisasterInformationLink extends Model
{
    /**
     * 一括代入を許可する属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'description',
        'url',
        'source',
        'fetched_at',
    ];

    /**
     * 属性のキャスト
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fetched_at' => 'datetime',
    ];
}
