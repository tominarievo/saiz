<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 災害情報リクエスト
 */
class DisasterRequest extends FormRequest
{
    /**
     * もしユーザーが認証されていたら、このリクエストを行うことができるかどうかを判断します。
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * リクエストから検証ルールを取得します。
     * Get the validation rules that apply to the request.
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
            'npo_col_2' => [
                'POST' => 'required|string|max:200',
                'PUT' => 'required|string|max:200',
            ],
            'event_date' => [
                'POST' => 'required|date|max:200',
                'PUT' => 'required|date|max:200',
            ],
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

        $ret['local_government_ids'] = 'nullable|array';
        $ret['local_government_ids.*'] = ['integer', Rule::exists('local_governments', 'id')];
        $ret['information_links'] = 'nullable|array';
        $ret['information_links.*.description'] = 'nullable|string|max:1000';
        $ret['information_links.*.url'] = 'nullable|string|max:2048|url';

        return $ret;
    }

    /**
     * 追加のバリデーションを設定します。
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ((array) $this->input('information_links', []) as $index => $link) {
                $description = trim((string) ($link['description'] ?? ''));
                $url = trim((string) ($link['url'] ?? ''));

                if (($description === '') !== ($url === '')) {
                    $validator->errors()->add(
                        "information_links.{$index}.url",
                        '概要文とURLは両方入力してください。'
                    );
                }
            }
        });
    }

    /*
     * 属性を取得します。
     * @return array
     */
    public function attributes() {
        return [
            'name' => '名称',
            'npo_col_2' => '年度',
            'event_date' => '発生年月日',
            'local_government_ids' => '市町村',
            'local_government_ids.*' => '市町村',
            'information_links.*.description' => '概要文',
            'information_links.*.url' => 'URL',
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
