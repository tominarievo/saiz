<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * 避難所・指定緊急避難場所
 */
class EvacuationShelter extends Model
{
    /**
     * 一括代入を許可する属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'local_government_id',
        'common_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'shelter_type',
        'disaster_types',
        'note',
        'is_active',
        'activity_status',
        'evacuees',
        'status_note',
        'status_source_name',
        'status_source_url',
        'status_checked_at',
        'status_changed_at',
        'source_name',
        'source_url',
        'fetched_at',
    ];

    /**
     * 属性のキャスト
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'disaster_types' => 'array',
        'is_active' => 'boolean',
        'evacuees' => 'integer',
        'status_checked_at' => 'datetime',
        'status_changed_at' => 'datetime',
        'fetched_at' => 'datetime',
    ];

    /**
     * この避難所が属する市町村を取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function localGovernment()
    {
        return $this->belongsTo(LocalGovernment::class);
    }
}
