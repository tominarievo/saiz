<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 組織リクエスト
 */
class OrganizationRequest extends FormRequest
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
            'name' => [
                'POST' => 'required|string|max:200',
                'PUT' => 'required|string|max:200',
            ]
        ];

//        // 2階層目の組織でお問い合わせを受ける場合は部分必須チェック。
//        if (config('const.setting.USE_ORG_LEVEL_2_CONTACT')) {
//            $rules['contact_email'] = [
//                'POST' => 'required_if:level,2|email|max:200',
//                'PUT'  => 'required_if:level,2|email|max:200',
//            ];
//        }

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
            'name' => '名称',
            'npo_col_1' => '支援団体コード',
        ];
    }

    /*
     * バリデーションエラーメッセージを取得します。
     * @return array
     */
    public function messages() {
        return [
            'contact_email.required_if' => 'アイデアボックス用メールアドレスを指定してください',
        ];
    }
}
