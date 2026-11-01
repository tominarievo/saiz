<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * オンラインサインアップのリクエスト
 */
class SignupRequest extends FormRequest
{
    /**
     * サインアップは未認証の利用者が行うため、常に許可する
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
        return [
            'organization_name'     => 'required|string|max:200',
            'name'                  => 'required|string|min:2|max:255',
            'email'                 => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'username')->whereNull('deleted_at'),
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'password'              => \App\Rules\PasswordRules::rules(),
            'terms'                 => 'accepted',
            // ボット対策（honeypot）: 入力があれば拒否する
            'website'               => 'prohibited',
        ];
    }

    /**
     * バリデーションエラーメッセージを取得
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'organization_name'     => '支援団体名',
            'name'                  => '担当者名',
            'email'                 => 'メールアドレス',
            'password'              => 'パスワード',
            'password_confirmation' => 'パスワード（確認）',
            'terms'                 => '利用規約への同意',
            'website'               => 'ウェブサイト',
        ];
    }
}
