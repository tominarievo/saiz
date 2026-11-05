<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * 道路規制・通行可否データ
 */
class RoadRegulation extends Model
{
    /**
     * 一括代入を許可する属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'disaster_id',
        'road_name',
        'section',
        'regulation_type',
        'vehicle_classes',
        'reason',
        'geometry',
        'latitude',
        'longitude',
        'confidence_rank',
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
        'vehicle_classes' => 'array',
        'geometry' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'source_published_at' => 'datetime',
        'scraped_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * この規制が属する災害
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function disaster()
    {
        return $this->belongsTo(Disaster::class);
    }
}
