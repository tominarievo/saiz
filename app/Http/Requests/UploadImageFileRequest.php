<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 画像ファイルアップロード用リクエスト
 */
class UploadImageFileRequest extends FormRequest
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
        return [
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,gif',
                'max:' . config('const.setting.max_csv_size'),
            ],
        ];
    }

    /**
     * 属性のカスタムエラーメッセージを定義
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'file' => 'アップロードファイル',
        ];
    }
}
