<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * ShelterDisasterTypeの中間テーブルモデル
 * 
 * このモデルは避難所と災害種別の中間テーブルに関する情報を扱います。
 */
class ShelterDisasterType extends Pivot
{
    use HasFactory;

    /**
     * テーブル名を指定します。
     * 
     * @var string テーブル名
     */
    protected $table = 'shelter_disaster_type';
}
