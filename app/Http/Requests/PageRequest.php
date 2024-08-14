<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ページリクエスト
 */
class PageRequest extends FormRequest
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
        $rules = [
            'content'                  => [
                'POST' => 'required|string|max:50000',
                'PUT'  => 'required|string|max:50000',
            ],
            'en_content'                  => [
                'POST' => 'required|string|max:50000',
                'PUT'  => 'required|string|max:50000',
            ]
        ];

        $ret = [];

        foreach ($rules as $key => $rule) {
            if (isset($rule[$this->method()])) {
                $ret[$key] = $rule[$this->method()];
            }
        }

        return $ret;
    }

    /*
     * 属性のカスタムエラーメッセージを定義
     * @return array
     */
    public function attributes()
    {
        return [
            'title'      => 'タイトル',
            'content'    => '本文',
            'en_content' => '英語本文',
        ];
    }
}
