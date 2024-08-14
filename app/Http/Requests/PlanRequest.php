<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 支援計画のリクエスト
 */
class PlanRequest extends FormRequest
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
                'POST' => 'required',
                'PUT'  => 'required',
            ],
            'shelter_id' => [
                'POST' => 'required',
                'PUT' => 'required',
            ],
            'from' => [
                'POST' => 'required|date|max:100',
                'PUT' => 'required|date|max:100',
            ],
            'to' => [
                'POST' => 'required|date',
                'PUT' => 'required|date',
            ],
            'description' => [
                'POST' => 'nullable|max:1000',
                'PUT'  => 'nullable|max:1000',
            ],
            'support_category1_id' => [
                'POST' => 'required',
                'PUT' => 'required',
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
            'organization_id' => '支援団体',
            'disaster_id'     => '災害情報',
            'shelter_id'      => '支援先',
            'prev_writer'     => '記入者',
            'writer'          => '記入者',
            'report_date'     => '支援日',
            'use_prev_writer'     => '過去に入力した記入者の選択',
            'support_category1_ids'     => '支援先状況',
            'supportCategory2s'     => '支援種別(中)',
            'from'                 => '支援開始日',
            'to'                   => '支援終了日',
            'support_category1_id' => '支援種別',
        ];
    }
}
