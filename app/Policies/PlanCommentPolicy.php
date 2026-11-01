<?php

namespace App\Policies;

use App\PlanComment;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlanCommentPolicy
{
    use HandlesAuthorization;

    /**
     * コメントの削除は投稿者本人または全体管理者のみ
     */
    public function delete(User $user, PlanComment $target)
    {
        if ($user->isAdmin()) {
            return true;
        }

        return ($target->user_id == $user->id);
    }

    /**
     * 既読状態の更新は予定の所属団体のユーザーのみ
     */
    public function read(User $user, PlanComment $target)
    {
        if ($user->isAdmin()) {
            return false;
        }

        return ($target->plan && $target->plan->organization_id == $user->organization_id);
    }
}
