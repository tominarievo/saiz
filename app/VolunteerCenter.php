<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * 災害ボランティアセンター
 */
class VolunteerCenter extends Model
{
    /**
     * 一括代入を許可する属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'local_government_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'phone',
        'url',
        'latest_recruitment_title',
        'latest_recruitment_summary',
        'latest_recruitment_url',
        'recruitment_updated_at',
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
        'recruitment_updated_at' => 'datetime',
        'fetched_at' => 'datetime',
    ];

    /**
     * このセンターが属する市町村を取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function localGovernment()
    {
        return $this->belongsTo(LocalGovernment::class);
    }
}
