<?php

namespace App\Policies;

use App\Organization;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function index(User $user)
    {
//        return $user->isAdmin();
    }

    public function create(User $user)
    {
//        return $user->isAdmin();
    }

    public function delete(User $user, User $target)
    {
//        return $user->isAdmin();
    }

    public function update(User $user, User $target)
    {
//        return $user->isAdmin();
    }

    public function passsword_change(User $user, User $target)
    {
        return ($user->id === $target->id);
    }

    /*
     * 全体管理者
     */

    public function admin_index(User $user)
    {
        return $user->isAdmin();
    }

    public function admin_create(User $user)
    {
        return $user->isAdmin();
    }

    public function admin_delete(User $user, User $target)
    {
        // 対象は全体管理者のみ
        if ( ! $target->isAdmin()) {
            return false;
        }

        return $user->isAdmin();
    }

    public function admin_update(User $user, User $target)
    {
        // 対象は全体管理者のみ
        if ( ! $target->isAdmin()) {
            return false;
        }

        return $user->isAdmin();
    }

    /**
     * 所属組織のどれか1つでもデータセットの承認権限があるかどうか
     * @param User $user
     * @param User $target
     * @return bool
     */
    public function approve_dataset(User $user, User $target)
    {
        if (blank($target->datasetOrganization)) {
            return false;
        }

        // 1組織でも承認権限があるかどうかをチェック
        foreach ($target->datasetOrganization as $organization) {
            if ($organization->pivot->auth_level3) {
                return true;
            }
        }

        return false;
    }

    /**
     * 所属組織のどれか1つでもデータセットの作成があるかどうか
     * @param User $user
     * @param User $target
     * @return bool
     */
    public function create_dataset(User $user, User $target)
    {
        if (blank($target->datasetOrganization)) {
            return false;
        }

        // 1組織でも承認権限があるかどうかをチェック
        foreach ($target->datasetOrganization as $organization) {
            if ($organization->pivot->auth_level2) {
                return true;
            }
        }

        return false;
    }
}
