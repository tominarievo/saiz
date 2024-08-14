<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * ユーザーモデル
 * 
 * このモデルはユーザーに関する情報を扱います。
 */
class User extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    /**
     * モデルのマスアサインメント可能なフィールドを指定します。
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'role',
        'top_organization_id',
        'organization_id',
        'is_valid'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * ユーザーの役割を取得します。
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo 役割の関係性
     */
    public function role()
    {
        return $this->belongsTo(Role::class)->withDefault(new Role());
    }

    /**
     * ユーザーのデータセット組織を取得します。
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany データセット組織の関係性
     */
    public function datasetOrganization()
    {
        return $this->belongsToMany(Organization::class, 'dataset_user_organization')
            ->withPivot('auth_level1', 'auth_level2', 'auth_level3');
    }

    // belongsTo設定
    public function organization()
    {
        return $this->belongsTo(Organization::class)->withDefault();
    }

    /**
     * ユーザーの所属自治体を取得する。
     * ※relationではないので注意
     * @return $this
     */
    public function topOrganization()
    {
        return OrganizationClosureTree::getTopOrganization($this->organization);
    }

    /**
     * ユーザーがクリップしたデータセットを取得します。
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany クリップしたデータセットの関係性
     */
    public function clips()
    {
        // デフォルトはアルファベット順でテ中間テーブル名を探しに行くのでテーブル名を指定
        return $this->belongsToMany(Dataset::class, 'clips')->withPivot('id');
    }

    /**
     * ユーザーが管理者かどうかを判定します。
     * 
     * @return bool 管理者の場合はtrue、それ以外の場合はfalse
     */
    public function isAdmin()
    {
        return blank($this->organization_id);
    }

    /**
     * ユーザーがマネージャーかどうかを判定します。
     * 
     * @return bool マネージャーの場合はtrue、それ以外の場合はfalse
     */
    public function isManager()
    {
        return filled($this->organization_id);
    }

    /**
     * ユーザーがメンバーかどうかを判定します。（非推奨）
     * 
     * @return bool メンバーの場合はtrue、それ以外の場合はfalse
     * @deprecated
     */
    public function isMember()
    {
        return $this->role === 'member';
    }

    /**
     * 指定した組織のデータセットの承認権限を持つユーザーを取得します。
     * 
     * @param int $organization_id 組織ID
     * @return array 承認権限を持つユーザーの配列
     */
    public static function getDatasetAuthLevel3User($organization_id)
    {
        $users = User::where('is_system_admin', false)
            ->get();

        if (blank($users)) {
            return [];
        }

        $ret = [];

        foreach ($users as $user) {

            if (static::hasUserDatasetAuthLevel3($user, $organization_id)) {
                $ret[] = $user;
            }
        }

        return $ret;
    }

    /**
     * 指定したユーザーが指定した組織のデータセット承認権限を持つかどうかを判定します。
     * 
     * @param User $user ユーザーモデル
     * @param int $organization_id 組織ID
     * @return bool 承認権限を持つ場合はtrue、それ以外の場合はfalse
     */
    public static function hasUserDatasetAuthLevel3(User $user, $organization_id)
    {
        // 該当組織のデータセット閲覧権限があるか
        foreach ($user->datasetOrganization as $organization) {

            if ($organization->id != $organization_id) {
                continue;
            }

            if ($organization->pivot->auth_level3) {
                return true;
            }
        }

        return false;
    }


    /**
     * ユーザーが操作可能かどうかを取得する
     * @param User $user
     * @return bool
     * @deprecated dataeye
     */
    public function isControlled(User $user)
    {
        if ($user->isMember()) {
            return $this->organization_id === $user->organization_id;
        } else {
            return OrganizationClosureTree::where('ancestor', $user->organization_id)
                ->where('descendant', $this->organization_id)
                ->exists();
        }
    }

    /**
     * データセットの閲覧許可のある組織の一覧を取得する。
     * @return array
     */
    public function getOrganizationEnableToViewDatasetIds()
    {
        if (blank($this->datasetOrganization)) {
            return [];
        }

        $ret = [];

        // 該当組織のデータセット作成権限があるか
        foreach ($this->datasetOrganization as $organization) {
            if ($organization->pivot->auth_level1) {
                $ret[] = $organization->id;
            }
        }

        return $ret;
    }

    /**
     * データセットの認・却下の許可のある組織の一覧を取得する。
     * @return array
     */
    public function getOrganizationEnableToApproveDatasetIds()
    {
        if (blank($this->datasetOrganization)) {
            return [];
        }

        $ret = [];

        // 該当組織のデータセット作成権限があるか
        foreach ($this->datasetOrganization as $organization) {
            if ($organization->pivot->auth_level3) {
                $ret[] = $organization->id;
            }
        }

        return $ret;
    }

    /**
     * テンプレートの閲覧許可のある組織の一覧を取得する。
     * @return array
     */
    public function getTemplateEnableToViewOrganizationIds()
    {
        if (blank($this->datasetOrganization)) {
            return [];
        }

        $ret = [];

        // 該当組織のデータセット作成権限があるか
        foreach ($this->datasetOrganization as $organization) {
            if ($organization->pivot->auth_level1
            || $organization->pivot->auth_level2
            || $organization->pivot->auth_level3 ) {
                $ret[] = $organization->id;
            }
        }

        return $ret;
    }

    /**
     * ユーザーが指定可能なデータセットの公開範囲をidの配列で取得する。
     * @return array
     */
    public function getSharedTypeIds()
    {
        if (blank($this->role->policies)) {
            return [];
        }

        $ret = [];

        foreach ($this->role->policies as $policy) {

            if ($policy->isPolicy(Policy::CODE_DATASET_OPEN_DATA)) {
                $ret[] = ShareType::OPEN_DATA;
            } elseif ($policy->isPolicy(Policy::CODE_DATASET_OPEN_SHARED_DATA)) {
                $ret[] = ShareType::OPEN_SHARED_DATA;
            } elseif ($policy->isPolicy(Policy::CODE_DATASET_SHARED_DATA)) {
                $ret[] = ShareType::SHARED_DATA;
            } elseif ($policy->isPolicy(Policy::CODE_DATASET_CLOSED_DATA)) {
                $ret[] = ShareType::CLOSED_DATA;
            }
        }

        return $ret;
    }
}
