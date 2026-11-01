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
        $file_path = (string) $request->input("file_path");
        $file_name = (string) $request->input("file_name");

        abort_if($file_path === '', 404);
        abort_if(strpos($file_path, '..') !== false || strpos($file_path, "\0") !== false, 404);
        abort_if(strpos($file_path, '/') === 0 || strpos($file_path, '\\') === 0, 404);

        $allowedPrefixes = [
            'upload_files/',
            'wysiwyg_uploads/',
            'organizations/',
            'organization/',
        ];

        $allowed = false;
        foreach ($allowedPrefixes as $prefix) {
            if (strpos($file_path, $prefix) === 0) {
                $allowed = true;
                break;
            }
        }

        abort_unless($allowed, 404);

        // 存在チェック
        abort_unless(Storage::exists($file_path), 404);

        $file_name = basename(str_replace(["\r", "\n", '"'], '', $file_name));
        if ($file_name === '') {
            $file_name = 'download';
        }

        $content_type = "application/octet-stream";
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
