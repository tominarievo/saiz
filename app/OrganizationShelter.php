<?php

/**
 * このファイルは、組織と避難所の関連情報を管理するためのピボットモデルクラスです。
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
 * OrganizationShelterクラス
 *
 * このクラスは、組織と避難所の関連情報を管理するためのピボットモデルクラスです。
 *
 * @category Models
 * @package  App
 */
class OrganizationShelter extends Pivot
{
    use HasFactory;
}
