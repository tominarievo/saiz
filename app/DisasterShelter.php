<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Class DisasterShelter
 *
 * このクラスは災害と避難所の間の多対多の関連付けを表すピボットモデルです。
 *
 * @package App
 */
class DisasterShelter extends Pivot
{
    use HasFactory;
}
