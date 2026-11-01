<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadImageFileRequest;
use App\Http\Requests\UploadInformationFileRequest;

use Illuminate\Http\Request;

/**
 * ユーティリティコントローラー
 */
class UtilController extends Controller
{
    /**
     * ファイルのアップロード
     * @param UploadInformationFileRequest $request
     * @return array
     */
    public function uploadInformationFile(UploadInformationFileRequest $request)
    {
        $file_name = request()->file->getClientOriginalName();

        $path = request()->file->store('upload_files');

        return [
            'file_type' => request()->file_type,
            'file_path' => $path,
            'file_name' => $file_name
        ];
    }

    /**
     * 画像ファイルのアップロード
     * @param UploadImageFileRequest $request
     * @return array
     */
    public function uploadImageFile(UploadImageFileRequest $request)
    {
        $file_name = request()->file->getClientOriginalName();

        $path = request()->file->store('upload_files');

        return [
            'file_type' => request()->file_type,
            'file_path' => $path,
            'file_name' => $file_name
        ];
    }
}
