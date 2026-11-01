<?php

namespace Tests\Feature\Security;

use App\Information;
use Illuminate\Support\Facades\Storage;

class FileDownloadTest extends SecurityTestCase
{
    private function createInformation(array $fileData, bool $published = true): Information
    {
        $information = new Information();
        $information->status = $published ? 1 : 0;
        $information->published_at = $published ? now()->subDay() : null;
        $information->title = 'Test information';
        $information->content = 'Test content';
        $information->file_data = json_encode($fileData);
        $information->save();

        return $information;
    }

    public function test_guest_cannot_download_information_file()
    {
        Storage::fake('local');
        Storage::disk('local')->put('upload_files/test.txt', 'secret-content');

        $information = $this->createInformation([
            ['file_path' => 'upload_files/test.txt', 'file_name' => 'test.txt'],
        ]);

        $this->get("/information/{$information->id}/file/0")
            ->assertRedirect(route('login'));
    }

    public function test_organization_user_can_download_published_information_file()
    {
        Storage::fake('local');
        Storage::disk('local')->put('upload_files/test.txt', 'secret-content');

        $organization = $this->createOrganization('Org A');
        $user = $this->createUser($organization->id);

        $information = $this->createInformation([
            ['file_path' => 'upload_files/test.txt', 'file_name' => 'test.txt'],
        ]);

        $this->actingAs($user)
            ->get("/information/{$information->id}/file/0")
            ->assertStatus(200)
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_organization_user_cannot_download_unpublished_information_file()
    {
        Storage::fake('local');
        Storage::disk('local')->put('upload_files/test.txt', 'secret-content');

        $organization = $this->createOrganization('Org A');
        $user = $this->createUser($organization->id);

        $information = $this->createInformation([
            ['file_path' => 'upload_files/test.txt', 'file_name' => 'test.txt'],
        ], false);

        $this->actingAs($user)
            ->get("/information/{$information->id}/file/0")
            ->assertStatus(404);
    }

    public function test_admin_can_download_unpublished_information_file()
    {
        Storage::fake('local');
        Storage::disk('local')->put('upload_files/test.txt', 'secret-content');

        $admin = $this->createUser(null);

        $information = $this->createInformation([
            ['file_path' => 'upload_files/test.txt', 'file_name' => 'test.txt'],
        ], false);

        $this->actingAs($admin)
            ->get("/information/{$information->id}/file/0")
            ->assertStatus(200);
    }

    public function test_invalid_or_traversal_file_data_is_rejected()
    {
        Storage::fake('local');

        $admin = $this->createUser(null);

        $information = $this->createInformation([
            ['file_path' => 'upload_files/../../.env', 'file_name' => '.env'],
        ]);

        $this->actingAs($admin)
            ->get("/information/{$information->id}/file/0")
            ->assertStatus(404);
    }
}
