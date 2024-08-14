<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * レポートとサポートカテゴリ2の中間テーブルモデルは、レポートとサポートカテゴリ2の関連情報を管理します。
 *
 * Class ReportSupportCategory2
 * @package App
 */
class ReportSupportCategory2 extends Pivot
{
    use HasFactory;
}
