<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InformationRequest extends FormRequest
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
            'title' => [
                'POST' => 'required|string|max:100',
                'PUT' => 'required|string|max:100',
            ],
            'content' => [
                'POST' => 'nullable|max:10000',
                'PUT' => 'nullable|max:10000',
            ],
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
    public function attributes() {
        return [
            'status' => '状態',
            'published_at' => '公開日時',
            'title' => 'タイトル',
            'content' => '本文',
        ];
    }
}
