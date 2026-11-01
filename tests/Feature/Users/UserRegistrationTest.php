<?php

namespace Tests\Feature\Users;

use App\User;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Security\SecurityTestCase;

class UserRegistrationTest extends SecurityTestCase
{
    public function test_admin_can_register_organization_user_without_bcc_setting(): void
    {
        $organization = $this->createOrganization('テスト支援団体');
        $admin = $this->createUser(null);

        $this->assertSame([], config('mail.bcc'));

        $this->actingAs($admin)->post(route('users.store'), [
            'organization_id' => $organization->id,
            'is_valid' => '1',
            'name' => '新規ユーザー',
            'username' => 'new-user@example.test',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ])->assertRedirect(route('organizations.show', ['organization' => $organization->id]));

        $user = User::where('username', 'new-user@example.test')->firstOrFail();
        $this->assertSame($organization->id, (int) $user->organization_id);
    }

    public function test_server_error_page_renders(): void
    {
        config(['app.debug' => false]);

        Route::get('/_test_server_error', fn () => abort(500));

        $this->get('/_test_server_error')
            ->assertStatus(500)
            ->assertSee('システムエラー');
    }
}
