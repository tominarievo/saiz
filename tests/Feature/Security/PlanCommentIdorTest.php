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
}
