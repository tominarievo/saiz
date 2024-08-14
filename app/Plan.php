<?php

/**
 * このファイルは、支援プラン情報を管理するためのモデルクラスです。
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

/**
 * Planクラス
 *
 * このクラスは、支援プラン情報を管理するためのモデルクラスです。
 *
 * @category Models
 * @package  App
 */
class Plan extends Model
{
    use HasFactory;

    /**
     * モデルのfillableな属性。
     *
     * @var array
     */
    protected $fillable = [
        "organization_id",
        "shelter_id",
        "from",
        "to",
        "description",
        "support_category1_id"
    ];

    /**
     * このプランに関連する組織情報を取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class)->withDefault(new Organization());
    }

    /**
     * このプランに関連する避難所情報を取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shelter()
    {
        return $this->belongsTo(Shelter::class)->withDefault(new Shelter());
    }

    /**
     * このプランに関連する支援カテゴリー2情報を取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function supportCategory2s()
    {
        return $this->belongsToMany(SupportCategory2::class)->withPivot('signal', 'memo');
    }

    /**
     * このプランに関連するプランコメント情報を取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function planComments()
    {
        return $this->hasMany(PlanComment::class)->with('user')->orderBy('post_datetime', 'DESC');
    }

    /**
     * 指定されたフォーマットで開始日時のラベルを取得します。
     *
     * @param string $format フォーマット（デフォルトは'Y/m/d'）
     * @return string 開始日時のラベル
     */
    public function getFromLabel($format = 'Y/m/d')
    {
        if (blank($this->from)) {
            return "";
        }

        $carbon = new Carbon($this->from);

        return $carbon->format($format);
    }

    /**
     * 支援種別の情報を整形して取得します。
     *
     * @return array 支援種別の情報
     */
    public function getSupportCategoryInfo()
    {
        $ret = [];

        foreach ($this->supportCategory2s as $supportCategory2) {

            // 支援種別(大)ごとに1単位となるようなデータを作成
            if ( ! isset($ret[$supportCategory2->support_category1_id])) {
                $ret[$supportCategory2->support_category1_id] = [
                    "id"   => $supportCategory2->supportCategory1->id,
                    "name" => $supportCategory2->supportCategory1->name,
                    "support2_list" => []
                ];
            }

            // 中分類
            $ret[$supportCategory2->support_category1_id]["support2_list"][] = $supportCategory2;
        }

        return $ret;
    }
}
