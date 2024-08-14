<?php

namespace App\Providers;

use App\Organization;
use App\Plan;
use App\Policies\OrganizationPolicy;
use App\Policies\PlanPolicy;
use App\Policies\ReportPolicy;
use App\Policies\UserPolicy;
use App\Report;
use App\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
        User::class => UserPolicy::class,
        Report::class => ReportPolicy::class,
        Organization::class => OrganizationPolicy::class,
        Plan::class => PlanPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
