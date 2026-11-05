<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * AIが探索した災害インテリジェンスの収集元
 */
class DisasterIntelligenceSource extends Model
{
    /**
     * 一括代入を許可する属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'disaster_id',
        'type',
        'url',
        'url_hash',
        'source_org',
        'published_at',
        'credibility_rank',
        'credibility_reason',
        'status',
        'discovered_at',
        'last_synced_at',
        'metadata',
    ];

    /**
     * 属性のキャスト
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published_at' => 'datetime',
        'discovered_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * この収集元が属する災害
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function disaster()
    {
        return $this->belongsTo(Disaster::class);
    }
}
