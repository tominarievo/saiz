<?php

/**
 * このファイルは、ローカル自治体モデルと関連するクラスを含みます。
 *
 * PHP version 7
 *
 * @category Models
 * @package  App
 */

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Prefecture;

/**
 * LocalGovernmentクラス
 *
 * このクラスは、ローカル自治体のモデルを表します。
 *
 * @category Models
 * @package  App
 */
class LocalGovernment extends Model
{
    use HasFactory;

    /**
     * モデルのデフォルトの属性値。
     *
     * @var array
     */
    protected $guarded = ['id']; // createメソッドを使用する為に追加

    const GOVERNMENT_TYPE_PREFECTURE = 1;
    const GOVERNMENT_TYPE_TOWN       = 0;
    const GOVERNMENT_TYPE_OTHER      = 2;

    /**
     * ローカル自治体に属する都道府県の関連を取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function prefecture()
    {
        return $this->belongsTo(Prefecture::class)->withDefault(new Prefecture());
    }

    /**
     * ローカル自治体に属する町の関連を取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function town()
    {
        return $this->belongsTo(Town::class)->withDefault(new Town());
    }

    /**
     * 都道府県の条件を設定します。
     *
     * @param $query
     * @return object query
     */
    public function scopeConditionForPrefecture($query)
    {
        return $query->where('government_type', self::GOVERNMENT_TYPE_TOWN);
    }

    /**
     * 一部事務組合等の条件を設定します。
     *
     * @param $query
     * @return object query
     */
    public function scopeConditionForUnion($query)
    {
        return $query->where('government_type', self::GOVERNMENT_TYPE_OTHER);
    }

    /**
     * 自治体が県であるかどうかを判定します。
     *
     * @return bool
     */
    public function isPrefecture()
    {
        return $this->government_type == self::GOVERNMENT_TYPE_PREFECTURE;
    }

    /**
     * 自治体コードからIDを取得します。
     *
     * @param [int] $code
     * @return string
     */
    public static function getLocalGovernmentId($code)
    {
        $localGovernment = LocalGovernment::where('code', $code)->first();

        return $localGovernment ? $localGovernment->id : null;
    }

    /**
     * 自治体コードから名称を取得します。
     *
     * @param [int] $code
     * @return string
     */
    public function getLocalGovernmentName($code)
    {
        $localGovernment = LocalGovernment::where('code', $code)->first();
        return $localGovernment ? $localGovernment->name : '';
    }

    /**
     * 様式CSVの県コードアップデート処理を行います。
     *
     * @param [array] $list
     * @param [string] $upload_time (Y-m-d H:i:s)
     * @return void
     */
    public static function formatImportPrefecture($list, $upload_time)
    {

        foreach ($list as $row) {

            // エクセル保存で6桁コードの先頭から0が欠如する場合を想定して0埋めする。
            $row['local_government_code'] = sprintf('%06d', $row['local_government_code']);
            $record = self::where('code', $row['local_government_code'])
            ->where('government_type', self::GOVERNMENT_TYPE_TOWN)
            ->first();

            if ($record === null) {
                // 新規登録
                // 自治体コードを分解
                $prefecture_code = substr($row['local_government_code'], 0, 2);
                // 先頭2桁から、prefectures.codeを検索してIDセット
                $prefecture = new Prefecture;
                $prefecture_id = $prefecture->getPrefectureIdByCode($prefecture_code);

                // $prefecture_idがnullならskip
                if ($prefecture_id !== null) {
                    LocalGovernment::create([
                        'order' => 0,
                        'prefecture_id' => $prefecture_id,
                        'prefecture_code' => $prefecture_code,
                        'code' => $row['local_government_code'],
                        'name' => $row['name'],
                        'government_type' => self::GOVERNMENT_TYPE_TOWN,
                        'is_designated_city' => $row['is_designated_city']
                    ]);
                }

            } else {
                // 更新処理
                $record->is_designated_city = $row['is_designated_city'];
                $record->name = $row['name'];
                $record->updated_at = date('Y-m-d H:i:s');
                $record->save();
            }
        }

        // 更新されなかったレコードを取得
        $not_update_records = self::where('government_type', self::GOVERNMENT_TYPE_TOWN)
            ->where('updated_at', '<', $upload_time)
            ->get();

        // 更新されなかったレコードを全消去
        $record = [];
        foreach($not_update_records as $record){
            self::destroy($record->id);
        }

    }

    /**
     * 様式CSVの組合コードアップデート処理を行います。
     *
     * @param [array] $list
     * @param [string] $upload_time (Y-m-d H:i:s)
     * @return void
     */
    public static function formatImportUnion($list, $upload_time)
    {

        foreach ($list as $row) {

            // エクセル保存で6桁コードの先頭から0が欠如する場合を想定して0埋めする。
            $row['local_government_code'] = sprintf('%06d', $row['local_government_code']);

            $record = self::where('code', $row['local_government_code'])
            ->where('government_type', self::GOVERNMENT_TYPE_OTHER)
            ->first();

            if ($record === null) {
                // 新規登録
                // 自治体コードを分解
                $prefecture_code = substr($row['local_government_code'], 0, 2);
                // 先頭2桁から、prefectures.codeを検索してIDセット
                $prefecture = new Prefecture;
                $prefecture_id = $prefecture->getPrefectureIdByCode($prefecture_code);

                // $prefecture_idがnullならskip
                if ($prefecture_id !== null) {
                    LocalGovernment::create([
                        'order' => 0,
                        'prefecture_id' => $prefecture_id,
                        'prefecture_code' => $prefecture_code,
                        'code' => $row['local_government_code'],
                        'name' => $row['name'],
                        'government_type' => self::GOVERNMENT_TYPE_OTHER,
                        'is_designated_city' => 0
                    ]);
                }

            } else {
                // 更新処理
                $record->name = $row['name'];
                $record->updated_at = date('Y-m-d H:i:s');
                $record->save();
            }
        }

        // 更新されなかったレコードを取得
        $not_update_records = self::where('government_type', self::GOVERNMENT_TYPE_OTHER)
            ->where('updated_at', '<', $upload_time)
            ->get();

        // 更新されなかったレコードを全消去
        $record = [];
        foreach($not_update_records as $record){
            self::destroy($record->id);
        }

    }

}
