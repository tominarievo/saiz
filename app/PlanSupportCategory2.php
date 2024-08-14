<?php

/**
 * このファイルは、支援プランと支援カテゴリー2の関連情報を管理するためのピボットモデルクラスです。
 *
 * PHP version 7
 *
 * @category Models
 * @package  App
 */

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * PlanSupportCategory2クラス
 *
 * このクラスは、支援プランと支援カテゴリー2の関連情報を管理するためのピボットモデルクラスです。
 *
 * @category Models
 * @package  App
 */
class PlanSupportCategory2 extends Pivot
{
    use HasFactory;
}
