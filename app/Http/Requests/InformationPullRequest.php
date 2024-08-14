<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * お知らせのリクエスト
 */
class InformationPullRequest extends FormRequest
{
    /*
     * 利用者が認証されているかどうかを判定
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * 検証ルールを取得します。
     *
     * @return array
     */
    public function rules()
    {
        return [
            'status'       => ['nullable'],
            'information_category_id' => ['nullable'],
            'published_at' => ['required', 'date'],
            'title'        => ['required', "max:200"],
            'content'      => ['required', 'max:40000'],
        ];
    }

    /*
     * 属性のカスタムエラーメッセージを定義
     * @return array
     */
    public function attributes() {
        return [
            'status'       => 'ステータス',
            'information_category_id'       => 'カテゴリー',
            'published_at' => '公開日',
            'title'        => 'タイトル',
            'content'      => '本文',
        ];
    }
}
