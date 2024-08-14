<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * レポートとタグの中間テーブルモデルは、レポートとタグの関連情報を管理します。
 *
 * Class ReportTag
 * @package App
 */
class ReportTag extends Pivot
{
    use HasFactory;
}
