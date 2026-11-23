<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * 災害ごとのデータ収集元・収集日
 */
class DisasterDataSource extends Model
{
    /**
     * 一括代入を許可する属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'disaster_id',
        'category',
        'source_key',
        'source_name',
        'source_url',
        'title',
        'collected_at',
        'published_at',
        'metadata',
    ];

    /**
     * 属性のキャスト
     *
     * @var array<string, string>
     */
    protected $casts = [
        'collected_at' => 'datetime',
        'published_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * この出典が属する災害
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function disaster()
    {
        return $this->belongsTo(Disaster::class);
    }
}
