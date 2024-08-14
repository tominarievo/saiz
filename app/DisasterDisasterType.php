<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Class DisasterDisasterType
 *
 * このクラスは災害と災害タイプの間の多対多の関連付けを表すピボットモデルです。
 *
 * @package App
 */
class DisasterDisasterType extends Pivot
{
    use HasFactory;
}
