<?php

namespace App;

use App\Traits\FrontConditionTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Information
 *
 * このクラスは情報を表すモデルです。
 * メンバーは以下の通りです。
 * - id: 情報のID
 * - status: 状態
 * - information_category_id: 情報のカテゴリーID
 * - published_at: 公開日時
 * - title: タイトル
 * - content: 内容
 *
 * @package App
 */
class Information extends Model
{
    use HasFactory;
    use FrontConditionTrait;

    /**
     * キャストする必要のある属性
     *
     * @var array
     */
    protected $casts = [
        'published_at' => 'date',
    ];

    /**
     * モデルのマスアサインメントを許可する属性
     *
     * @var array
     */
    protected $fillable = [
        'status',
        'information_category_id',
        'published_at',
        'title',
        'content',
    ];

    /**
     * この情報に関連する情報カテゴリーを取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function informationCategory()
    {
        return $this->belongsTo(InformationCategory::class)->withDefault(InformationCategory::class);
    }

    /**
     * 基本的なエスケープ処理を行なった上で、
     * 本文内のURLをリンクに加工して取得します。
     *
     * @return array|string|string[]|null
     */
    public function getEditedContent()
    {
        // タグが含まれている場合はWYSIWYGコンテンツとしてサニタイズします。
        if (preg_match("/[\<\>]+/u", $this->content)) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('Cache.DefinitionImpl', null);
            $config->set(
                'HTML.Allowed',
                'p,br,strong,b,em,i,u,ul,ol,li,a[href|target|rel],h1,h2,h3,h4,h5,h6,blockquote,pre,code,img[src|alt|width|height]'
            );
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);

            $purifier = new \HTMLPurifier($config);

            return $purifier->purify($this->content);
        }

        $result = nl2br(e($this->content));

        $pattern ='/(http|https):\/\/[!#$%&\'()*+,\/:;=?@\[\]0-9A-Za-z-._~]+/';

        // URLをaタグに変換します。
        $result = preg_replace_callback($pattern, function ($matches) {
            return '<a target="_blank" href="' . $matches[0] . '" class="save_history">'. $matches[0] . '</a>';
        }, $result);

        return $result;
    }

    /**
     * 添付ファイルのjsonを配列から1件のみ取得します。
     *
     * @return array|mixed
     */
    public function getOneFile()
    {
        $files = $this->getFiles();

        if (blank($files)) {
            return null;
        }

        return (object) $files[0];
    }

    /**
     * 添付ファイル情報を検証済みの配列で取得します。
     * 不正な形式・パスの要素は除外します。
     *
     * @return array<int, array{file_path: string, file_name: string}>
     */
    public function getFiles(): array
    {
        return static::normalizeFileData($this->file_data) ?? [];
    }

    /**
     * 画面(Vue)に渡す添付ファイル情報を取得します。
     * 保存済みファイルはパスではなくお知らせIDと添付番号のURLで参照させます。
     *
     * @return array
     */
    public function getFilesForView(): array
    {
        $ret = [];

        foreach ($this->getFiles() as $index => $file) {
            $file['url'] = $this->exists
                ? route('information.files.show', ['information' => $this->id, 'index' => $index])
                : '';
            $ret[] = $file;
        }

        return $ret;
    }

    /**
     * 画面から送信された添付ファイル情報(JSON)を検証・正規化します。
     * アップロード機能が発行したパス(upload_files/配下のファイル)以外は受け付けません。
     *
     * @param string|null $raw
     * @return array|null 正常時の正規化済み配列、不正な形式の場合、null
     */
    public static function normalizeFileData(?string $raw): ?array
    {
        if (blank($raw)) {
            return [];
        }

        $list = json_decode($raw, true);

        if (! is_array($list)) {
            return null;
        }

        $ret = [];

        foreach ($list as $file) {
            if (! is_array($file)) {
                return null;
            }

            $file_path = $file['file_path'] ?? '';
            $file_name = $file['file_name'] ?? '';

            // 画面上の空のアップロード枠は除外
            if ($file_path === '' || $file_path === null) {
                continue;
            }

            if (! is_string($file_path) || ! is_string($file_name)
                || ! preg_match('#\Aupload_files/[A-Za-z0-9]+(\.[A-Za-z0-9]+)?\z#', $file_path)
                || mb_strlen($file_name) > 255) {
                return null;
            }

            $ret[] = [
                'file_path' => $file_path,
                'file_name' => $file_name,
            ];
        }

        return $ret;
    }

    /**
     * 添付ファイルのjsonを配列として取得します。
     *
     * @return array
     */
    public function getFileDataArray()
    {
        return $this->getFiles();
    }
}
