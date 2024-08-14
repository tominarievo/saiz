<?php

namespace App\Policies;

use App\Tag;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TagPolicy
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

    /*
     * 全体管理者
     */

    public function index(User $user)
    {
        if ( ! $user->isAdmin()) {
            return false;
        }

        return true;
    }

    public function create(User $user)
    {
        if ( ! $user->isAdmin()) {
            return false;
        }

        return true;
    }

    public function delete(User $user, Tag $target)
    {
        if ( ! $user->isAdmin()) {
            return false;
        }

        // 他組織のタグは操作不可
        if ($user->organization_id !== $target->organization_id) {
            return false;
        }

        return true;
    }

    public function update(User $user, Tag $target)
    {
        if ( ! $user->isAdmin()) {
            return false;
        }

        // 他組織のタグは操作不可
        if ($user->organization_id !== $target->organization_id) {
            return false;
        }

        return true;
    }
}
