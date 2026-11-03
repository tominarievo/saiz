<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * 会議議事録のインテリジェンス抽出結果
 */
class MeetingInsight extends Model
{
    /**
     * 一括代入を許可する属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'disaster_id',
        'meeting_name',
        'held_at',
        'decisions',
        'policy_changes',
        'unmet_needs',
        'source_page',
        'source_org',
        'source_url',
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
        'held_at' => 'datetime',
        'decisions' => 'array',
        'policy_changes' => 'array',
        'unmet_needs' => 'array',
        'source_page' => 'integer',
        'source_published_at' => 'datetime',
        'scraped_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * この抽出結果が属する災害
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function disaster()
    {
        return $this->belongsTo(Disaster::class);
    }
}
