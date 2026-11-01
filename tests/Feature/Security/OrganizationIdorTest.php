<?php

namespace Tests\Feature\Security;

class OrganizationIdorTest extends SecurityTestCase
{
    public function test_organization_user_cannot_update_or_delete_another_organization()
    {
        $orgA = $this->createOrganization('Org A');
        $orgB = $this->createOrganization('Org B');
        $userA = $this->createUser($orgA->id);

        $this->actingAs($userA)
            ->put('/organizations/' . $orgB->id, ['name' => 'Hacked'])
            ->assertStatus(403);

        $this->actingAs($userA)
            ->delete('/organizations/' . $orgB->id)
            ->assertStatus(403);
    }

    public function test_organization_user_can_update_own_organization()
    {
        $orgA = $this->createOrganization('Org A');
        $userA = $this->createUser($orgA->id);

        $this->actingAs($userA)
            ->put('/organizations/' . $orgA->id, ['name' => 'Org A Updated'])
            ->assertStatus(302);
    }

    public function test_only_admin_can_delete_organization()
    {
        $orgA = $this->createOrganization('Org A');
        $admin = $this->createUser(null);

        $this->actingAs($admin)
            ->delete('/organizations/' . $orgA->id)
            ->assertStatus(302);
    }
}
