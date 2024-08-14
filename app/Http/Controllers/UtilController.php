<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadImageFileRequest;
use App\Http\Requests\UploadInformationFileRequest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    /**
     * ファイルのダウンロード
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function frontFileDownload(Request $request)
    {
        $file_path = $request->input("file_path");
        $file_name = $request->input("file_name");

        // 存在チェック
        abort_unless(Storage::exists($file_path), 404);

        $content_type = "text/plain";
        $content_disposition = 'filename="'.$file_name.'"';

        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        switch ($ext) {
            case "pdf":
                $content_type = 'application/pdf';
                $content_disposition = 'inline; '.$content_disposition;
                break;
            case "csv":
                $content_type = 'text/csv';
                break;
            case "xlsx":
                $content_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                $content_disposition = 'inline; '.$content_disposition;
                break;
            case "zip":
                $content_type = 'application/zip';
                break;
        }

        $headers = [
            'Content-Type'        => $content_type,
            'Content-disposition' => $content_disposition
        ];

        return \Response::make(Storage::get($file_path), 200, $headers);
    }
}
