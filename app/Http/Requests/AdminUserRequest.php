<?php

namespace App\Http\Requests;

use App\Organization;
use App\OrganizationClosureTree;
use App\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * 管理者ユーザーのリクエスト
 */
class AdminUserRequest extends FormRequest
{
    /**
     * 管理者ユーザーのリクエストを受け付けるかどうか
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * リクエストのバリデーション
     *
     * @return array
     */
    public function rules()
    {
        $user = $this->route('admin_user');

        $id = ($user) ? $user->id : null;

        $rules = [
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
     * バリデーションエラーメッセージ
     *
     * @return array
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        if ($this->method() === 'PUT') {

            $user = $this->route('admin_user');

            /*
             * 編集時のパスワードは画面上で入力があった場合のみ入力チェック
             * TODO: 最新の書き方だと他に方法がある気がする。
             */

            $validator->sometimes('password', 'required|alpha_dash|min:6|confirmed', function ($input) use ($user) {
                return (filled($input->password) && $input->password !== $user->password);
            });

            $validator->sometimes('password_confirmation', 'required|alpha_dash|min:6', function ($input) use ($user) {
                return (filled($input->password) && $input->password !== $user->password);
            });
        }
    }

    /**
     * バリデーションエラーメッセージ
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
