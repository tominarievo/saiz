<?php

namespace Tests\Feature;

use Tests\Feature\Security\SecurityTestCase;

class FormShimTest extends SecurityTestCase
{
    public function test_admin_edit_form_renders_with_method_spoofing()
    {
        $admin = $this->createUser(null);

        $response = $this->actingAs($admin)->get('/admin_users/' . $admin->id . '/edit');

        $response->assertStatus(200);
        $response->assertSee('name="_method" value="PUT"', false);
        $response->assertSee('name="name"', false);
    }
}
