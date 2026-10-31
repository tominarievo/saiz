<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Storage;

class FileDownloadTest extends SecurityTestCase
{
    public function test_guest_cannot_download_file()
    {
        Storage::fake('local');
        Storage::disk('local')->put('upload_files/test.txt', 'secret-content');

        $this->get('/file_download?file_path=upload_files/test.txt&file_name=test.txt')
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_download_allowed_file()
    {
        Storage::fake('local');
        Storage::disk('local')->put('upload_files/test.txt', 'secret-content');

        $organization = $this->createOrganization('Org A');
        $user = $this->createUser($organization->id);

        $this->actingAs($user)
            ->get('/file_download?file_path=upload_files/test.txt&file_name=test.txt')
            ->assertStatus(200)
            ->assertSee('secret-content');
    }

    public function test_path_traversal_is_rejected()
    {
        Storage::fake('local');

        $organization = $this->createOrganization('Org A');
        $user = $this->createUser($organization->id);

        $this->actingAs($user)
            ->get('/file_download?file_path=../.env&file_name=.env')
            ->assertStatus(404);

        $this->actingAs($user)
            ->get('/file_download?file_path=upload_files/../../.env&file_name=.env')
            ->assertStatus(404);
    }
}
