<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * WysiwygFileUploadController
 */
class WysiwygFileUploadController extends Controller
{
    /**
     * ファイルのアップロード
     * @param Request $request
     * @return array
     */
    public function upload(Request $request)
    {
        $file_path =  ($request->file('file'))
            ? $request->file('file')->store(Auth::user()->organization->org_dir_path('site_setting'), 'public')
            : '';

        $ret = [
            'url' => Storage::url($file_path)
        ];

        return $ret;
    }

}
