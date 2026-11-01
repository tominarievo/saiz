<?php

namespace Tests\Feature\Security;

class ReportIdorTest extends SecurityTestCase
{
    public function test_organization_user_cannot_read_or_delete_another_organization_report()
    {
        $orgA = $this->createOrganization('Org A');
        $orgB = $this->createOrganization('Org B');
        $shelter = $this->createShelter();
        $userA = $this->createUser($orgA->id);
        $reportB = $this->createReport($orgB->id, $shelter->id);

        $this->actingAs($userA)
            ->get('/reports/' . $reportB->id)
            ->assertStatus(403);

        $this->actingAs($userA)
            ->delete('/reports/' . $reportB->id)
            ->assertStatus(403);
    }

    public function test_organization_user_can_delete_own_organization_report()
    {
        $orgA = $this->createOrganization('Org A');
        $shelter = $this->createShelter();
        $userA = $this->createUser($orgA->id);
        $reportA = $this->createReport($orgA->id, $shelter->id);

        $this->actingAs($userA)
            ->delete('/reports/' . $reportA->id)
            ->assertStatus(302);
    }
}
