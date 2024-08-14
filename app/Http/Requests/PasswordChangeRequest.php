<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * パスワード変更のリクエスト
 */
class PasswordChangeRequest extends FormRequest
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
            'password'              => [
                'PUT' => 'required|alpha_dash|min:8|confirmed',
            ],
            'password_confirmation' => [
                'PUT' => 'required|alpha_dash|min:8',
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
     *　パスワードのエラーチェックを行う
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {

            /*
             * パスワード
             */
            
            // 形式チェックを行う。

            if ( ! preg_match("/[a-z]+/", $this->password)
                || ! preg_match("/[A-Z]+/", $this->password)
                || ! preg_match("/[0-9]+/", $this->password)) {
                $validator->errors()->add('password', "パスワードには半角英数字と大文字のアルファベットを含める必要があります。");
            }

        });
    }

    /*
     * 属性のカスタムエラーメッセージを定義
     * @return array
     */
    public function attributes() {
        return [
            'password' => 'パスワード',
            'password_confirmation' => 'パスワード(確認)',
        ];
    }
}
