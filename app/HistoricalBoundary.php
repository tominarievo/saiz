<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HistoricalBoundary extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'prefecture_code',
        'prefecture_name',
        'boundary_date',
        'resolution',
        'source',
        'license',
        'bbox',
        'topojson',
        'feature_count',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'boundary_date' => 'date',
        'bbox' => 'array',
        'topojson' => 'array',
    ];
}
