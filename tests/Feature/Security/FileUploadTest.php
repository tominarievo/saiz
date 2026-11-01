<?php

namespace Tests\Feature\Security;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadTest extends SecurityTestCase
{
    public function test_php_upload_is_rejected()
    {
        Storage::fake('local');

        $organization = $this->createOrganization('Org A');
        $user = $this->createUser($organization->id);

        $file = UploadedFile::fake()->create('evil.php', 10, 'application/x-php');

        $this->actingAs($user)
            ->postJson('/admin/information_file_upload', ['file' => $file])
            ->assertStatus(422);
    }

    public function test_pdf_upload_is_accepted()
    {
        Storage::fake('local');

        $organization = $this->createOrganization('Org A');
        $user = $this->createUser($organization->id);

        $file = UploadedFile::fake()->create('test.pdf', 10, 'application/pdf');

        $this->actingAs($user)
            ->postJson('/admin/information_file_upload', ['file' => $file])
            ->assertStatus(200)
            ->assertJsonStructure(['file_path', 'file_name']);
    }
}
