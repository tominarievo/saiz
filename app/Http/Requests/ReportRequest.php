<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/*
 * 日報のリクエストフォームの処理を行う
 * Class ReportRequest
 */
class ReportRequest extends FormRequest
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
            'organization_id' => [
                'POST' => 'required|string|max:100',
                'PUT' => 'required|string|max:100',
            ],
            'disaster_id' => [
                'POST' => 'required|string|max:100',
                'PUT' => 'required|string|max:100',
            ],
            'shelter_id' => [
                'POST' => 'required|string|max:100',
                'PUT' => 'required|string|max:100',
            ],
            'report_date' => [
                'POST' => 'required|date|max:100',
                'PUT' => 'required|date|max:100',
            ],
            'prev_writer' => [
                'POST' => 'required_with:use_prev_writer|string|max:100',
                'PUT'  => 'required_with:use_prev_writer|string|max:100',
            ],
            'writer' => [
                'POST' => 'required_without:use_prev_writer|string|max:100',
                'PUT' => 'required_without:use_prev_writer|string|max:100',
            ],
            'comment' => [
                'POST' => 'nullable|string|max:20000',
                'PUT'  => 'nullable|string|max:20000',
            ],
            'hidden_comment' => [
                'POST' => 'nullable|string|max:20000',
                'PUT'  => 'nullable|string|max:20000',
            ],
            'tag_list' => [
                'POST' => 'nullable|string|max:20000',
                'PUT'  => 'nullable|string|max:20000',
            ],
            'support_category1_ids' => [
                'POST' => 'bail|required',
                'PUT'  => 'bail|required',
            ],
            'supportCategory2s' => [
                'POST' => 'bail|required',
                'PUT'  => 'bail|required',
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
     *　バリデーションの前に実行される処理
    　* @param \Illuminate\Validation\Validator $validator
    　*/
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {

            /*
             * 種別(中)にチェックを入れた場合はフラグを必須にする
             */

            if (filled($this->support_category1_ids) && filled($this->supportCategory2s)) {
                foreach ($this->supportCategory2s as $support_category2_id) {
                    if ( ! isset($this->support_category_values[$support_category2_id]["signal"])) {
                        $validator->errors()->add("support_category_values[{$support_category2_id}][signal]", "状態を入力してください。");
                    }
                }
            }
        });

    }

    /*
     * 属性のカスタムエラーメッセージを定義
     * @return array
     */
    public function attributes() {
        return [
            'organization_id' => '支援団体',
            'disaster_id'     => '災害情報',
            'shelter_id'      => '支援先',
            'prev_writer'     => '記入者',
            'writer'          => '記入者',
            'report_date'     => '支援日',
            'use_prev_writer'     => '過去に入力した記入者の選択',
            'support_category1_ids'     => '支援先状況',
            'supportCategory2s'     => '支援種別(中)',
        ];
    }
}
