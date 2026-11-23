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

    public function test_organization_user_can_view_disaster_information()
    {
        $organization = $this->createOrganization('Org A');
        $user = $this->createUser($organization->id);
        $publicDisaster = $this->createDisaster();

        $privateDisaster = new Disaster();
        $privateDisaster->name = 'Private disaster';
        $privateDisaster->status = false;
        $privateDisaster->save();

        $this->actingAs($user)
            ->get('/disasters')
            ->assertOk();

        $this->actingAs($user)
            ->get('/disasters/' . $publicDisaster->id)
            ->assertOk();

        $this->actingAs($user)
            ->get('/disasters/' . $privateDisaster->id)
            ->assertOk();
    }

    public function test_organization_user_cannot_manage_disasters()
    {
        $organization = $this->createOrganization('Org A');
        $user = $this->createUser($organization->id);
        $disaster = $this->createDisaster();

        $this->actingAs($user)->get('/disasters/create')->assertForbidden();
        $this->actingAs($user)->post('/disasters', [])->assertForbidden();
        $this->actingAs($user)->get('/disasters/' . $disaster->id . '/edit')->assertForbidden();
        $this->actingAs($user)->put('/disasters/' . $disaster->id, [])->assertForbidden();
        $this->actingAs($user)->delete('/disasters/' . $disaster->id)->assertForbidden();
    }

    public function test_organization_user_can_view_shelters_but_cannot_manage_them()
    {
        $organization = $this->createOrganization('Org A');
        $user = $this->createUser($organization->id);
        $shelter = $this->createShelter();

        $this->actingAs($user)->get('/shelters')->assertOk();
        $this->actingAs($user)->get('/shelters/' . $shelter->id)->assertOk();

        $this->actingAs($user)->get('/shelters/create')->assertForbidden();
        $this->actingAs($user)->post('/shelters', [])->assertForbidden();
        $this->actingAs($user)->get('/shelters/' . $shelter->id . '/edit')->assertForbidden();
        $this->actingAs($user)->put('/shelters/' . $shelter->id, [])->assertForbidden();
        $this->actingAs($user)->delete('/shelters/' . $shelter->id)->assertForbidden();
    }

    public function test_admin_can_manage_disasters_and_shelters()
    {
        $admin = $this->createUser(null);
        $disaster = $this->createDisaster();
        $shelter = $this->createShelter();

        $this->actingAs($admin)->get('/disasters/create')->assertOk();
        $this->actingAs($admin)->get('/disasters/' . $disaster->id . '/edit')->assertOk();
        $this->actingAs($admin)->get('/shelters/create')->assertOk();
        $this->actingAs($admin)->get('/shelters/' . $shelter->id . '/edit')->assertOk();
    }

    public function test_guest_cannot_view_non_public_disaster()
    {
        $privateDisaster = new Disaster();
        $privateDisaster->name = 'Private disaster';
        $privateDisaster->status = false;
        $privateDisaster->save();

        $this->get('/disasters/' . $privateDisaster->id)->assertNotFound();
    }
}
