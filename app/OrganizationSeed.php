<?php

/**
 * このファイルは、組織と支援カテゴリーの関連情報を管理するためのピボットモデルクラスです。
 *
 * PHP version 7
 *
 * @category Models
 * @package  App
 */

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * OrganizationSeedクラス
 *
 * このクラスは、組織と支援カテゴリーの関連情報を管理するためのピボットモデルクラスです。
 *
 * @category Models
 * @package  App
 */
class OrganizationSeed extends Pivot
{
    use HasFactory;

    /**
     * テーブル名
     *
     * @var string
     */
    protected $table = 'organization_seed';

    /**
     * このピボットモデルに関連する組織情報を取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class)->withDefault(new Organization());
    }

    /**
     * このピボットモデルに関連する支援カテゴリー1情報を取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function supportCategory1()
    {
        return $this->belongsTo(SupportCategory1::class)->withDefault(new SupportCategory1());
    }

    /**
     * このピボットモデルに関連する支援カテゴリー2情報を取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function supportCategory2()
    {
        return $this->belongsTo(SupportCategory2::class)->withDefault(new SupportCategory2());
    }
}
