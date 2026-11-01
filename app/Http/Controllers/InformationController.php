<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\InformationPostRequest;
use App\Http\Requests\InformationPullRequest;
use App\Information;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * 情報コントローラークラス
 */
class InformationController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!in_array($request->route()->getActionMethod(), ['index', 'show', 'file'], true)) {
                $this->authorizeAdmin();
            }
            return $next($request);
        });
    }

    /**
     * リソースの一覧を表示する。
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $condition = new \stdClass();
        $condition->keyword = $request->input('keyword');

        $query = Information::query();

        $query->when(filled($condition->keyword), function($q) use ($condition) {

            //連続スペースを1つの半角スペースに替えた上で検索
            $keyword = trim($condition->keyword);
            $keyword = preg_replace('/ +/', ' ', $keyword);
            $keyword = preg_replace('/　+/', ' ', $keyword);

            $words = explode(' ', $keyword);

            foreach ($words as $word) {
                // バラしたキーワードはAND検索
                $q->where(function ($sub) use ($word) {
                    // 複数のカラムが必要な場合はこの中でorWhereを使うことでスコープを閉じることができる。
                    $sub->where('title', 'like', "%{$word}%")
                        ->orWhere('content', 'like', "%{$word}%");
                });
            }
        });

        $list = $query->orderBy("published_at", "DESC")
            ->paginate(10)
            ->appends((array)$condition);

        return view ('information.index', compact('condition', 'list'));
    }

    /**
     * 新規リソースの作成フォームを表示する。
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $entity = new Information();
        $entity->status                 = true;
        $entity->formatted_published_at = Carbon::today()->format("Y/m/d");

        $this->setChoicesDataForView();

        return view ('information.create', compact('entity'));
    }

    /**
     * 画面上の選択肢などの共通処理を行う。
     *
     * @return void
     */
    private function setChoicesDataForView()
    {
        // 未処理
    }

    /**
     * ストレージに新しいリソースを保存する。
     *
     * @param InformationPostRequest $request
     * @return Response
     * @throws \Throwable
     */
    public function store(InformationPostRequest $request)
    {
        Log::info('Information store function started.'); // 開始ログ

        $file_data = $this->validatedFileData($request);

        DB::beginTransaction();
    
        $entity = new Information();
    
        try {
            $entity->fill($request->validated());
            $entity->status    = filled($request->input('status')) ?: false;
            $entity->is_pickup = filled($request->input('is_pickup')) ?: false;
            $entity->file_data = $file_data;
    
            $entity->save();
            Log::info('Information saved.', ['id' => $entity->id]); // 保存成功ログ
    
            $one_file = $entity->getOneFile();

            $image_res_json = null;

            if ($one_file) {
                Log::info('Uploading file to WordPress.', ['file_path' => $one_file->file_path]); // WordPressへのアップロード開始ログ
                $image_res_json = $this->uploadEyeCatchToWp($one_file->file_path, $one_file->file_name);
            }

            $url = config("wordpress.url")."/wp-json/wp/v2/posts";
            $data = [
                'title'   => $entity->title,
                'content' => $entity->content,
                'status'  => $entity->status ? 'publish' : 'draft',
                'categories' => [config('wordpress.category_id', 1)], // デフォルト値として1を使用
            ];

            // 添付ファイルがある場合のみfeatured_mediaをセットする。
            if ($image_res_json) {
                $data['featured_media'] = $image_res_json->id ?: null;
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_USERPWD, config("wordpress.user").":".config("wordpress.app_password"));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    
            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // cURLエラーが発生した場合
            if (curl_errno($ch)) {
                Log::error('Curl error while posting to WordPress.', ['error' => curl_error($ch)]);
                curl_close($ch);
                throw new \Exception("cURL error while posting to WordPress: " . curl_error($ch));
            }
            
            // レスポンスがfalseまたはHTTPステータスコードがエラーを示している場合
            if ($response === false || $httpcode >= 400) {
                $error_message = $response ? "Unexpected HTTP status code: $httpcode" : "No response from WordPress";
                Log::error('Error posting to WordPress.', ['http_code' => $httpcode, 'response' => $response]);
                curl_close($ch);
                throw new \Exception($error_message);
            }
            
            // 正常に処理された場合、レスポンスをログに記録
            Log::info("お知らせの新規登録でWordpress連携に成功しました。", ['response' => $response]);
            
            curl_close($ch);
            DB::commit(); // トランザクションをコミット

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error in Information store function.', ['error' => $e->getMessage()]); // エラーログ
            abort(500);
        }
    
        return redirect(route('information.index'));
    }
    /**
     * Wordpress連携で事前にメディア(画像)を送信する処理。
     * @param $relative_path
     * @param $file_name
     * @return mixed
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function uploadEyeCatchToWp($relative_path, $file_name)
    {
        $url = config("wordpress.url") . "/wp-json/wp/v2/media/";
        $data = Storage::get($relative_path);
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, config("wordpress.user") . ":" . config("wordpress.app_password"));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: image/png', "Content-Disposition: attachment; filename=" . $file_name
        ));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        if (curl_errno($ch)) {
            // cURLエラーが発生した場合
            curl_close($ch);
            throw new \Exception("Curl error: " . curl_error($ch));
        }
    
        if ($response === false || $http_code >= 400) {
            // HTTPエラーが発生した場合、またはレスポンスがfalseの場合
            curl_close($ch);
            throw new \Exception("WordPress media upload failed with status code: " . $http_code);
        }
    
        curl_close($ch);
        $res_json = json_decode($response);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            // JSONデコードエラーが発生した場合
            throw new \Exception("JSON decode error: " . json_last_error_msg());
        }
    
        if (is_null($res_json)) {
            // 応答がNULL、つまり予期せぬレスポンスだった場合
            throw new \Exception("Unexpected response from WordPress");
        }
    
        if (isset($res_json->code)) {
            // WordPressのレスポンスにエラーコードが含まれていた場合
            throw new \Exception("WordPress error: " . $res_json->message);
        }
    
        return $res_json;
    }
    
    /**
     * 指定されたリソースを表示する。
     *
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function show($id)
    {
        $entity = Information::conditionForFront()->findOrFail($id);

        return view('information.show', compact('entity'));
    }

    /**
     * お知らせの添付ファイルを取得する。
     * ファイルパスは受け取らず、お知らせIDと添付番号から閲覧権限を確認した上で返却する。
     *
     * @param Information $information
     * @param int $index
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function file(Information $information, $index)
    {
        $is_visible = Auth::user()->isAdmin()
            || Information::conditionForFront()->whereKey($information->id)->exists();

        abort_unless($is_visible, 404);

        $files = $information->getFiles();

        abort_unless(isset($files[$index]), 404);

        $file = $files[$index];

        abort_unless(Storage::exists($file['file_path']), 404);

        // Content-Typeは利用者が指定したファイル名ではなく、保存時の拡張子から判定する
        $ext = strtolower(pathinfo($file['file_path'], PATHINFO_EXTENSION));

        $inline_types = [
            'pdf'  => 'application/pdf',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
        ];

        $attachment_types = [
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt'  => 'text/plain; charset=UTF-8',
        ];

        if (isset($inline_types[$ext])) {
            $content_type = $inline_types[$ext];
            $disposition = 'inline';
        } else {
            $content_type = $attachment_types[$ext] ?? 'application/octet-stream';
            $disposition = 'attachment';
        }

        return Storage::response($file['file_path'], $file['file_name'] ?: basename($file['file_path']), [
            'Content-Type'           => $content_type,
            'X-Content-Type-Options' => 'nosniff',
        ], $disposition);
    }

    /**
     * 送信された添付ファイル情報を検証し、保存用のJSON文字列を返す。
     *
     * @param Request $request
     * @return string
     * @throws ValidationException
     */
    private function validatedFileData(Request $request)
    {
        $files = Information::normalizeFileData($request->input('file_data'));

        if ($files === null) {
            throw ValidationException::withMessages(['file_data' => '添付ファイルの情報が不正です。']);
        }

        foreach ($files as $file) {
            if (! Storage::exists($file['file_path'])) {
                throw ValidationException::withMessages(['file_data' => '添付ファイルが見つかりません。']);
            }
        }

        return json_encode($files, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * 指定されたリソースを編集するためのフォームを表示する。
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $entity = Information::findOrFail($id);

        // castsが指定されている時刻型データの表示フォーマットを調整
        $entity->formatted_published_at = $entity->published_at->format("Y/m/d");

        $this->setChoicesDataForView();

        return view ('information.edit', compact('entity'));
    }

    /**
     * ストレージ内のリソースを更新する。
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(InformationPullRequest $request, $id)
    {
        $entity = Information::findOrFail($id);

        $file_data = $this->validatedFileData($request);

        // トランザクションの開始
        DB::beginTransaction();

        try {
            $entity->fill($request->validated());

            // チェックボックスはfillで未チェックをセットできないので個別にセット
            $entity->status = filled($request->input('status')) ?: false;
            $entity->is_pickup = filled($request->input('is_pickup')) ?: false;

            // 事前にアップロード済みのファイル情報
            $entity->file_data = $file_data;

            $entity->save();

            $request->session()->flash('success', '編集を行いました。');

            // トランザクションのコミット
            DB::commit();

        } catch (\Throwable $e) {
            // トランザクションのロールバック
            DB::rollBack();

            Log::error($e->getMessage());

            abort(500);
        }

        return redirect(url()->previous());
    }

    /**
     * 指定されたリソースをストレージから削除する。
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(request $request, $id)
    {
        // トランザクションの開始
        DB::beginTransaction();

        $entity = Information::findOrFail($id);

        try {
            $entity->delete();

            $request->session()->flash('success', '削除を行いました。');

            // トランザクションのコミット
            DB::commit();

        } catch (\Throwable $e) {
            // トランザクションのロールバック
            DB::rollBack();

            Log::error($e->getMessage());

            abort(500);
        }

        return redirect(url()->previous());
    }
}
