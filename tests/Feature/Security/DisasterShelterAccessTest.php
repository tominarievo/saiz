<?php

namespace Tests\Feature\Security;

use App\Disaster;

class DisasterShelterAccessTest extends SecurityTestCase
{
    private function createDisaster(): Disaster
    {
        $disaster = new Disaster();
        $disaster->name = 'Test disaster';
        $disaster->status = true;
        $disaster->save();

        return $disaster;
    }

    public function test_organization_user_cannot_edit_disaster_shelters()
    {
        $organization = $this->createOrganization('Org A');
        $user = $this->createUser($organization->id);
        $disaster = $this->createDisaster();

        $this->actingAs($user)
            ->get('/disaster_shelters/edit/' . $disaster->id)
            ->assertStatus(403);
    }

    public function test_admin_can_edit_disaster_shelters()
    {
        $admin = $this->createUser(null);
        $disaster = $this->createDisaster();

        $this->actingAs($admin)
            ->get('/disaster_shelters/edit/' . $disaster->id)
            ->assertStatus(200);
    }
}
