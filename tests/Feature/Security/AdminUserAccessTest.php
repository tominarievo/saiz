<?php

namespace Tests\Feature\Security;

class AdminUserAccessTest extends SecurityTestCase
{
    public function test_organization_user_cannot_access_admin_users()
    {
        $organization = $this->createOrganization('Org A');
        $user = $this->createUser($organization->id);

        $this->actingAs($user)
            ->get('/admin_users')
            ->assertStatus(403);
    }

    public function test_admin_can_access_admin_users()
    {
        $admin = $this->createUser(null);

        $this->actingAs($admin)
            ->get('/admin_users')
            ->assertStatus(200);
    }
}
