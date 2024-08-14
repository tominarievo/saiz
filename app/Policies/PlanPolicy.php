<?php

namespace App\Policies;

use App\Plan;
use App\Report;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class PlanPolicy
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

    public function show_all(User $user, Plan $target)
    {
        if (blank(Auth::user()->organization_id)) {
            return true;
        }

        return ($target->organization_id == Auth::user()->organization_id);
    }

    public function delete(User $user, Plan $target)
    {
        if (blank(Auth::user()->organization_id)) {
            return true;
        }

        return ($target->organization_id == Auth::user()->organization_id);
    }

    public function update(User $user, Plan $target)
    {
        if (blank(Auth::user()->organization_id)) {
            return true;
        }

        return ($target->organization_id == Auth::user()->organization_id);
    }
}
