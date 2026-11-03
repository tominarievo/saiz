<?php

namespace Tests\Feature;

use App\Disaster;
use App\Information;
use App\Organization;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PublicTopTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_public_disaster_top(): void
    {
        $disaster = new Disaster();
        $disaster->name = '令和テスト地震';
        $disaster->status = true;
        $disaster->event_date = '2026-11-01';
        $disaster->save();

        $information = new Information();
        $information->title = '公開テストのお知らせ';
        $information->content = '<p>公開される本文です。</p>';
        $information->status = true;
        $information->published_at = now()->subDay();
        $information->save();

        $this->get('/')
            ->assertOk()
            ->assertSee('災害情報')
            ->assertSee('令和テスト地震')
            ->assertSee('公開テストのお知らせ')
            ->assertSee('公開される本文です。')
            ->assertSee('ログイン')
            ->assertDontSee('新規利用登録')
            ->assertDontSee('新規登録');
    }

    public function test_unpublished_information_is_not_shown_on_public_top(): void
    {
        $information = new Information();
        $information->title = '下書きのお知らせ';
        $information->content = '下書きの本文';
        $information->status = false;
        $information->published_at = now()->subDay();
        $information->save();

        $this->get('/')
            ->assertOk()
            ->assertDontSee('下書きのお知らせ');
    }

    public function test_authenticated_user_is_redirected_to_home(): void
    {
        $organization = new Organization();
        $organization->name = 'テスト支援団体';
        $organization->status = true;
        $organization->save();

        $user = new User();
        $user->organization_id = $organization->id;
        $user->username = 'public-top@example.test';
        $user->email = 'public-top@example.test';
        $user->name = 'テストユーザー';
        $user->password = Hash::make('TestPassword_1!');
        $user->is_valid = true;
        $user->save();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('home'));
    }
}
