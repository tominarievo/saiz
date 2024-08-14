<?php

namespace App;

use App\Traits\FrontConditionTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Information
 *
 * このクラスは情報を表すモデルです。
 * メンバーは以下の通りです。
 * - id: 情報のID
 * - status: 状態
 * - information_category_id: 情報のカテゴリーID
 * - published_at: 公開日時
 * - title: タイトル
 * - content: 内容
 *
 * @package App
 */
class Information extends Model
{
    use HasFactory;
    use FrontConditionTrait;

    /**
     * キャストする必要のある属性
     *
     * @var array
     */
    protected $casts = [
        'published_at' => 'date',
    ];

    /**
     * モデルのマスアサインメントを許可する属性
     *
     * @var array
     */
    protected $fillable = [
        'status',
        'information_category_id',
        'published_at',
        'title',
        'content',
    ];

    /**
     * この情報に関連する情報カテゴリーを取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function informationCategory()
    {
        return $this->belongsTo(InformationCategory::class)->withDefault(InformationCategory::class);
    }

    /**
     * 基本的なエスケープ処理を行なった上で、
     * 本文内のURLをリンクに加工して取得します。
     *
     * @return array|string|string[]|null
     */
    public function getEditedContent()
    {
        // タグが含まれている場合はWYSIWYGコンテンツとして未加工とします。
        if (preg_match("/[\<\>]+/u", $this->content)) {
            return $this->content;
        }

        $result = nl2br(e($this->content));

        $pattern ='/(http|https):\/\/[!#$%&\'()*+,\/:;=?@\[\]0-9A-Za-z-._~]+/';

        // URLをaタグに変換します。
        $result = preg_replace_callback($pattern, function ($matches) {
            return '<a target="_blank" href="' . $matches[0] . '" class="save_history">'. $matches[0] . '</a>';
        }, $result);

        return $result;
    }

    /**
     * 添付ファイルのjsonを配列から1件のみ取得します。
     *
     * @return array|mixed
     */
    public function getOneFile()
    {
        if (blank($this->file_data)) {
            return null;
        }

        $json_list = json_decode($this->file_data);

        if ( ! $json_list) {
            return null;
        }

        return $json_list[0];
    }
}
