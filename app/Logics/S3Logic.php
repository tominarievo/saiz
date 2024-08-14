<?php

namespace App\Logics;

use Illuminate\Support\Facades\Storage;

class S3Logic
{
    /**
     * 案件の仕様により、S3へのhttpsアクセスが禁止されているためいったん実ファイルを作成して読み込み直す
     * @param $relative_path
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public static function getRealServerFilePath($relative_path)
    {
        if (Storage::getDefaultDriver() === 's3') {

            $tmp_content = Storage::get($relative_path);

            // localに存在しなければ同じ内容のファイルを作成する。
            if ( ! Storage::disk('local')->exists($relative_path)) {
                Storage::disk('local')->put($relative_path, $tmp_content);
            }

            $csv = Storage::disk('local')->path($relative_path);

        } else {

            $csv = Storage::path($relative_path);
        }

        return $csv;
    }

    /**
     * 案件の仕様により、S3へのhttpsアクセスが禁止されているためいったんS3とlocalどちらも削除する。
     * @param $relative_path
     */
    public static function deleteFile($relative_path) {

        if (Storage::getDefaultDriver() === 's3') {

            // S3上のものを削除
            if (Storage::exists($relative_path)) {
                Storage::delete($relative_path);
            }

            // localにコピーがあれば削除
            if (Storage::disk('local')->exists($relative_path)) {
                Storage::disk('local')->delete($relative_path);
            }

        } else {

            if (Storage::exists($relative_path)) {
                Storage::delete($relative_path);
            }
        }
    }
}
