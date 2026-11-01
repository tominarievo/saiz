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
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,pdf', 'max:5120'],
        ]);

        $directory = 'wysiwyg_uploads/' . (Auth::user()->organization_id ?: 'admin');
        $file_path = $request->file('file')->store($directory, 'public');

        $ret = [
            'url' => Storage::url($file_path)
        ];

        return $ret;
    }

}
