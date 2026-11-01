<?php

namespace Tests\Feature\Auth;

use App\Mail\SignupReceivedMail;
use App\Organization;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SignupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * サインアップの正常な入力値
     *
     * @param array $overrides
     * @return array
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'organization_name'     => '新規支援団体',
            'name'                  => '代表 太郎',
            'email'                 => 'signup@example.test',
            'password'              => 'StrongPassword_1!',
            'password_confirmation' => 'StrongPassword_1!',
            'terms'                 => '1',
        ], $overrides);
    }

    /**
     * 既存ユーザーを作成する
     *
     * @param string $email
     * @return User
     */
    private function createExistingUser(string $email): User
    {
        $organization = new Organization();
        $organization->name = '既存支援団体';
        $organization->status = true;
        $organization->save();

        $user = new User();
        $user->organization_id = $organization->id;
        $user->username = $email;
        $user->email = $email;
        $user->name = '既存ユーザー';
        $user->password = Hash::make('ExistingPassword_1!');
        $user->is_valid = true;
        $user->is_system_admin = false;
        $user->save();

        return $user;
    }

    public function test_signup_form_is_displayed_for_guest(): void
    {
        $this->get(route('signup.create'))
            ->assertOk()
            ->assertSee('支援団体名');
    }

    public function test_signup_creates_organization_and_user(): void
    {
        Mail::fake();

        $this->post(route('signup.store'), $this->validPayload())
            ->assertRedirect(route('signup.complete'));

        $this->assertNotNull(Organization::where('name', '新規支援団体')->first());
        $this->assertNotNull(User::where('username', 'signup@example.test')->first());
    }

    public function test_signed_up_user_is_inactive_and_linked_to_organization(): void
    {
        Mail::fake();

        $this->post(route('signup.store'), $this->validPayload());

        $organization = Organization::where('name', '新規支援団体')->firstOrFail();
        $user = User::where('username', 'signup@example.test')->firstOrFail();

        $this->assertSame((int) $organization->id, (int) $user->organization_id);
        $this->assertFalse((bool) $user->is_valid);
        $this->assertFalse((bool) $user->is_system_admin);
    }

    public function test_inactive_signed_up_user_cannot_log_in(): void
    {
        Mail::fake();

        $this->post(route('signup.store'), $this->validPayload());

        $this->from('/login')->post('/login', [
            'username' => 'signup@example.test',
            'password' => 'StrongPassword_1!',
        ])->assertRedirect('/login')->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_admin_can_approve_signed_up_user(): void
    {
        Mail::fake();

        $this->post(route('signup.store'), $this->validPayload());

        $user = User::where('username', 'signup@example.test')->firstOrFail();

        $admin = new User();
        $admin->username = 'admin@example.test';
        $admin->email = 'admin@example.test';
        $admin->name = '管理者';
        $admin->password = Hash::make('AdminPassword_1!');
        $admin->is_valid = true;
        $admin->is_system_admin = true;
        $admin->save();

        $this->actingAs($admin)
            ->get(route('users.edit', $user->id))
            ->assertOk()
            ->assertSee('有効（ログインを許可する）');

        $this->actingAs($admin)
            ->put(route('users.update', $user->id), [
                'name' => $user->name,
                'username' => $user->username,
                'is_valid' => '1',
            ])
            ->assertRedirect(route('organizations.show', ['organization' => $user->organization_id]));

        $this->assertTrue((bool) $user->fresh()->is_valid);

        $this->post(route('logout'));
        $this->post('/login', [
            'username' => 'signup@example.test',
            'password' => 'StrongPassword_1!',
        ])->assertRedirect('/home');

        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_successful_signup_redirects_to_complete_page(): void
    {
        Mail::fake();

        $this->post(route('signup.store'), $this->validPayload())
            ->assertRedirect(route('signup.complete'));

        $this->get(route('signup.complete'))->assertOk();
    }

    public function test_signup_sends_received_mail(): void
    {
        Mail::fake();

        $this->post(route('signup.store'), $this->validPayload());

        Mail::assertSent(SignupReceivedMail::class, function (SignupReceivedMail $mail) {
            return $mail->hasTo('signup@example.test');
        });
    }

    public function test_duplicate_email_is_rejected(): void
    {
        Mail::fake();

        $this->createExistingUser('signup@example.test');
        $organizationCount = Organization::count();

        $this->from(route('signup.create'))
            ->post(route('signup.store'), $this->validPayload())
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::count());
        $this->assertSame($organizationCount, Organization::count());
    }

    public function test_password_confirmation_mismatch_is_rejected(): void
    {
        Mail::fake();

        $this->from(route('signup.create'))
            ->post(route('signup.store'), $this->validPayload(['password_confirmation' => 'Different_1']))
            ->assertSessionHasErrors('password');

        $this->assertSame(0, User::count());
        $this->assertSame(0, Organization::count());
    }

    public function test_required_fields_are_validated(): void
    {
        Mail::fake();

        $this->from(route('signup.create'))
            ->post(route('signup.store'), [])
            ->assertSessionHasErrors(['organization_name', 'name', 'email', 'password', 'terms']);

        $this->assertSame(0, User::count());
        $this->assertSame(0, Organization::count());
    }

    public function test_honeypot_field_blocks_submission(): void
    {
        Mail::fake();

        $this->from(route('signup.create'))
            ->post(route('signup.store'), $this->validPayload(['website' => 'http://spam.example']))
            ->assertSessionHasErrors('website');

        $this->assertSame(0, User::count());
        $this->assertSame(0, Organization::count());
    }

    public function test_authenticated_user_is_redirected_from_signup(): void
    {
        $user = $this->createExistingUser('existing@example.test');

        $this->actingAs($user)
            ->get(route('signup.create'))
            ->assertRedirect('/home');
    }
}
