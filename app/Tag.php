<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
/**
 * タグモデル
 * 
 * このモデルクラスは検索などで利用するタグテーブルのレコードに対応しています。
 */
class Tag extends Model
{
    protected $fillable = ["name"];
}
