<?php

namespace App\Http\Requests;

use App\Organization;
use App\OrganizationClosureTree;
use App\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * ユーザーのリクエストを処理するクラス
 */
class UserRequest extends FormRequest
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
        $user = $this->route('user');

        $id = ($user) ? $user->id : null;

        $rules = [
            'organization_id'                  => [
                'POST' => 'required',
            ],
            'name'                  => [
                'POST' => 'required|string|min:2|max:255',
                'PUT'  => 'required|string|min:2|max:255',
            ],
            'username'              => [
                'POST' => 'required|string|max:255|' . Rule::unique('users')->whereNull('deleted_at'),
                'PUT'  => 'required|string|max:255|' . Rule::unique('users')->whereNull('deleted_at')->ignore($id),
            ],
            'password'              => [
                'POST' => 'required|alpha_dash|min:6|confirmed',
                // 編集時はwithValidatorでチェックを追加
            ],
            'password_confirmation' => [
                'POST' => 'required|alpha_dash|min:6',
                // 編集時はwithValidatorでチェックを追加
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
     * 検証前に追加のチェックを行う
     *
     * @return array
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {

            // 新規登録時のみ組織の権限チェック
            if ($this->method() === 'POST') {

                $organization = Organization::findOrFail($this->organization_id);

//                if ( ! $organization->isControlled(Auth::user())) {
//                    abort(403, '不正な組織が指定されています。');
//                }
            }

//            $emails = explode(',', $this->hidden_emails);
//
//            foreach ($emails as $email) {
//
//                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
//                    $validator->errors()->add('hidden_emails', "メールアドレスは、有効なメールアドレス形式で指定してください。");
//                }
//
//            }
        });


    }

    /**
     * バリデーションエラーメッセージを取得
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name'                  => 'ユーザー名',
            'username'              => 'メールアドレス',
            'password'              => 'パスワード',
            'password_confirmation' => 'パスワード確認',
        ];
    }
}
