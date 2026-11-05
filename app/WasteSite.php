<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * 災害廃棄物・仮置場
 */
class WasteSite extends Model
{
    /**
     * 一括代入を許可する属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'disaster_id',
        'name',
        'status',
        'geometry',
        'latitude',
        'longitude',
        'acceptance_start_at',
        'acceptance_end_at',
        'vehicle_limit',
        'max_payload_tons',
        'entry_allowed',
        'accepted_items',
        'rejected_items',
        'support_conditions',
        'source_org',
        'source_url',
        'source_credibility',
        'source_credibility_reason',
        'source_published_at',
        'scraped_at',
        'raw_file_path',
        'metadata',
    ];

    /**
     * 属性のキャスト
     *
     * @var array<string, string>
     */
    protected $casts = [
        'geometry' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'acceptance_start_at' => 'datetime',
        'acceptance_end_at' => 'datetime',
        'max_payload_tons' => 'float',
        'entry_allowed' => 'boolean',
        'accepted_items' => 'array',
        'rejected_items' => 'array',
        'support_conditions' => 'array',
        'source_published_at' => 'datetime',
        'scraped_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * この仮置場が属する災害
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function disaster()
    {
        return $this->belongsTo(Disaster::class);
    }
}
