<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * 管理者が削除した収集情報の再収集防止リスト
 */
class CollectedInformationBlock extends Model
{
    protected $fillable = [
        'disaster_id',
        'type',
        'block_key',
        'block_hash',
        'source_url',
        'created_by',
    ];

    public function disaster()
    {
        return $this->belongsTo(Disaster::class);
    }
}
