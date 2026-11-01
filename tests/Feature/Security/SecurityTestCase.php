<?php

namespace Tests\Feature\Security;

use App\Organization;
use App\Plan;
use App\PlanComment;
use App\Report;
use App\Shelter;
use App\SupportCategory1;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

abstract class SecurityTestCase extends TestCase
{
    use RefreshDatabase;

    protected function createOrganization($name)
    {
        $organization = new Organization();
        $organization->name = $name;
        $organization->save();

        return $organization;
    }

    protected function createUser($organizationId)
    {
        $user = new User();
        $user->organization_id = $organizationId;
        $user->username = 'user_' . uniqid() . '@example.test';
        $user->email = $user->username;
        $user->name = 'Test User';
        $user->password = Hash::make('password');
        $user->is_valid = true;
        $user->save();

        return $user;
    }

    protected function createShelter($name = 'Test Shelter')
    {
        $shelter = new Shelter();
        $shelter->name = $name;
        $shelter->status = true;
        $shelter->save();

        return $shelter;
    }

    protected function createReport($organizationId, $shelterId)
    {
        $report = new Report();
        $report->organization_id = $organizationId;
        $report->shelter_id = $shelterId;
        $report->disaster_id = 0;
        $report->report_date = now();
        $report->writer = 'tester';
        $report->save();

        return $report;
    }

    protected function createSupportCategory1($name = 'Test Category')
    {
        $category = new SupportCategory1();
        $category->name = $name;
        $category->save();

        return $category;
    }

    protected function createPlan($organizationId, $shelterId, $supportCategory1Id)
    {
        $plan = new Plan();
        $plan->organization_id = $organizationId;
        $plan->shelter_id = $shelterId;
        $plan->support_category1_id = $supportCategory1Id;
        $plan->from = '2026-01-01';
        $plan->to = '2026-01-31';
        $plan->save();

        return $plan;
    }

    protected function createPlanComment($planId, $userId, $comment = 'comment')
    {
        $planComment = new PlanComment();
        $planComment->plan_id = $planId;
        $planComment->user_id = $userId;
        $planComment->post_datetime = now();
        $planComment->comment = $comment;
        $planComment->save();

        return $planComment;
    }
}
