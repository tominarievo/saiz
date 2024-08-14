<?php

namespace App\Policies;

use App\Organization;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class OrganizationPolicy
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

    public function show_admin_information(User $user, Organization $target)
    {
        if (blank(Auth::user()->organization_id)) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Organization $target)
    {
        if (blank(Auth::user()->organization_id)) {
            return true;
        }

        return false;
    }

    public function update(User $user, Organization $target)
    {
        if (blank(Auth::user()->organization_id)) {
            return true;
        }

        return ($target->id == Auth::user()->organization_id);
    }
}
