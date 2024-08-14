<?php

/**
 * このファイルは、支援プランコメント情報を管理するためのモデルクラスです。
 *
 * PHP version 7
 *
 * @category Models
 * @package  App
 */

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PlanCommentクラス
 *
 * このクラスは、支援プランコメント情報を管理するためのモデルクラスです。
 *
 * @category Models
 * @package  App
 */
class PlanComment extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * モデルのbelongsTo関連。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * モデルのbelongsTo関連。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class)->with("organization")->withDefault(new User());
    }

    /**
     * post_datetime属性のアクセサ。
     *
     * @param mixed $value
     * @return string
     */
    public function getPostDatetimeAttribute($value)
    {
        if (blank($value)) {
            return "";
        }

        $carbon = new Carbon($value);

        return $carbon->format('Y/m/d H:i');
    }

    /**
     * 作成者の未読既読情報を取得します。
     *
     * @return mixed
     */
    public function getCreatorRead()
    {
        // 一意に絞られるデータ(1つのコメントにつき未読既読は1レコードしかつかない仕様)
        $read = PlanCommentRead::where("plan_comment_id", $this->id)
            ->first();

        return $read;
    }
}
