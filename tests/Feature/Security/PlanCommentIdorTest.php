<?php

namespace Tests\Feature\Security;

class PlanCommentIdorTest extends SecurityTestCase
{
    public function test_organization_user_cannot_delete_another_organization_plan_comment()
    {
        $orgA = $this->createOrganization('Org A');
        $orgB = $this->createOrganization('Org B');
        $shelter = $this->createShelter();
        $category = $this->createSupportCategory1();
        $userA = $this->createUser($orgA->id);
        $userB = $this->createUser($orgB->id);
        $planB = $this->createPlan($orgB->id, $shelter->id, $category->id);
        $commentB = $this->createPlanComment($planB->id, $userB->id);

        $this->actingAs($userA)
            ->postJson('/plan_comments/delete', [
                'commentId' => $commentB->id,
                'plan_id' => $planB->id,
            ])
            ->assertStatus(403);
    }

    public function test_organization_user_can_delete_own_organization_plan_comment()
    {
        $orgA = $this->createOrganization('Org A');
        $shelter = $this->createShelter();
        $category = $this->createSupportCategory1();
        $userA = $this->createUser($orgA->id);
        $planA = $this->createPlan($orgA->id, $shelter->id, $category->id);
        $commentA = $this->createPlanComment($planA->id, $userA->id);

        $this->actingAs($userA)
            ->postJson('/plan_comments/delete', [
                'commentId' => $commentA->id,
                'plan_id' => $planA->id,
            ])
            ->assertStatus(200);
    }

    public function test_same_organization_user_cannot_delete_another_users_comment()
    {
        $orgA = $this->createOrganization('Org A');
        $shelter = $this->createShelter();
        $category = $this->createSupportCategory1();
        $userA = $this->createUser($orgA->id);
        $userA2 = $this->createUser($orgA->id);
        $planA = $this->createPlan($orgA->id, $shelter->id, $category->id);
        $commentA2 = $this->createPlanComment($planA->id, $userA2->id);

        $this->actingAs($userA)
            ->postJson('/plan_comments/delete', [
                'commentId' => $commentA2->id,
                'plan_id' => $planA->id,
            ])
            ->assertStatus(403);
    }

    public function test_organization_user_cannot_mark_another_organization_comment_read()
    {
        $orgA = $this->createOrganization('Org A');
        $orgB = $this->createOrganization('Org B');
        $shelter = $this->createShelter();
        $category = $this->createSupportCategory1();
        $userA = $this->createUser($orgA->id);
        $userB = $this->createUser($orgB->id);
        $planB = $this->createPlan($orgB->id, $shelter->id, $category->id);
        $commentB = $this->createPlanComment($planB->id, $userB->id);

        $this->actingAs($userA)
            ->postJson('/plan_comment_reads/update', [
                'comment_id' => $commentB->id,
                'read_status' => 1,
            ])
            ->assertStatus(403);
    }
}
