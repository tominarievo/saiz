<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ユーティリティロジッククラス
 *
 * このクラスはさまざまなユーティリティ関数を提供します。
 */
class UtilLogic extends Model
{
    use HasFactory;

    /**
     * コンテンツを編集して返します。
     *
     * @param string $content 編集するコンテンツ
     * @return string 編集されたコンテンツ
     */    
    public static function getEditedContent($content)
    {
        $result = nl2br(e($content));

        $pattern ='/(http|https):\/\/[!#$%&\'()*+,\/:;=?@\[\]0-9A-Za-z-._~]+/';

        // URLをaタグに変換する
        $result = preg_replace_callback($pattern, function ($matches)
            {
                return '<a href="' . $matches[0] . '" target="_blank">' . $matches[0] . '</a>';
            }, $result);

        return $result;
    }
}
