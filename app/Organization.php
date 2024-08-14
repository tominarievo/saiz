<?php

/**
 * このファイルは、組織情報を管理するためのモデルクラスです。
 *
 * PHP version 7
 *
 * @category Models
 * @package  App
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * Organizationクラス
 *
 * このクラスは、組織情報を管理するためのモデルクラスです。
 *
 * @category Models
 * @package  App
 */
class Organization extends Model
{
    use SoftDeletes;

    /**
     * モデルのfillableな属性。
     *
     * @var array
     */
    protected $fillable = [
        'name', 'org_code', 'level'
    ];

    /**
     * この組織に関連するシード情報を取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function seeds()
    {
        return $this->belongsToMany(SupportCategory2::class, "organization_seed")
            ->withPivot('comment', 'id')
            ->orderBy('support_category1_id', 'ASC');
    }

    /*
     * NPO追加分
     */

    /**
     * この組織に関連するユーザー情報を取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * この組織に関連する避難所情報を取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function shelters() {
        return $this->belongsToMany(Shelter::class);
    }

    /**
     * この組織に関連する地方自治体情報を取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function localGovernment() {
        return $this->belongsTo(LocalGovernment::class)->withDefault(new LocalGovernment());
    }

}
