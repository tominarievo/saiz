<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 支援種別（中）のリクエストを処理するクラス
 * 
 */
class SupportCategory2Request extends FormRequest
{
    /**
     * 利用者が認証されているかどうかを判定
     *
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
            'name' => [
                'POST' => 'required|string|max:200',
                'PUT' => 'required|string|max:200',
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

    /**
     * 属性のカスタムエラーメッセージを定義
     *
     * @return array
     */
    public function attributes() {
        return [
            'name' => '名称',
            'level1' => '対応力レベル1',
            'level2' => '対応力レベル2',
            'level3' => '対応力レベル3',
        ];
    }

    /**
     * カスタムエラーメッセージを定義
     *
     * @return array
     */
    public function messages() {
        return [
            'contact_email.required_if' => 'アイデアボックス用メールアドレスを指定してください',
        ];
    }
}
