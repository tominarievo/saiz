<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * パスワードの再発行の新パスワード設定リクエスト
 *
 */
class PasswordRegenerationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'password' => [
                'POST' => 'required|alpha_dash|min:6|confirmed',
            ],
            'password_confirmation' => [
                'POST' => 'required|alpha_dash|min:6',
            ],
            'hash' => [
                'POST' => 'required',
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

    public function attributes() {
        return [
            'password' => '新しいパスワード',
            'password_confirmation' => '新しいパスワード確認用',
        ];
    }
}
