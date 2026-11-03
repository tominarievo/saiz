<?php

namespace Tests\Feature;

use App\Disaster;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DisasterInformationLinkTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        $admin = new User();
        $admin->username = 'disaster-link-admin@example.test';
        $admin->email = 'disaster-link-admin@example.test';
        $admin->name = '災害管理者';
        $admin->password = Hash::make('AdminPassword_1!');
        $admin->is_valid = true;
        $admin->is_system_admin = true;
        $admin->save();

        return $admin;
    }

    private function validPayload(array $informationLinks): array
    {
        return [
            'name' => '最新情報リンクテスト災害',
            'npo_col_2' => '2026',
            'event_date' => '2026-11-01',
            'local_government_ids' => [],
            'disaster_type_ids' => [],
            'information_links' => $informationLinks,
        ];
    }

    private function createDisasterWithLinks(array $informationLinks): Disaster
    {
        $disaster = new Disaster();
        $disaster->name = '最新情報リンク表示テスト災害';
        $disaster->status = true;
        $disaster->npo_col_2 = '2026';
        $disaster->event_date = '2026-11-01';
        $disaster->save();

        foreach ($informationLinks as $informationLink) {
            $disaster->informationLinks()->create($informationLink);
        }

        return $disaster;
    }

    public function test_information_links_can_be_registered(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post(route('disasters.store'), $this->validPayload([
                [
                    'description' => '令和8年8月豪雨の特設ページ',
                    'url' => 'https://example.com/rain',
                ],
                [
                    'description' => '支援情報',
                    'url' => 'https://example.com/support',
                ],
                [
                    'description' => '',
                    'url' => '',
                ],
            ]))
            ->assertRedirect(route('disasters.index'));

        $disaster = Disaster::where('name', '最新情報リンクテスト災害')->firstOrFail();

        $this->assertSame(2, $disaster->informationLinks()->count());
        $this->assertSame(
            ['令和8年8月豪雨の特設ページ', '支援情報'],
            $disaster->informationLinks()->pluck('description')->all()
        );
    }

    public function test_information_links_can_be_replaced_on_update(): void
    {
        $admin = $this->createAdmin();
        $disaster = $this->createDisasterWithLinks([
            [
                'description' => '更新前の情報',
                'url' => 'https://example.com/before',
            ],
        ]);

        $this->actingAs($admin)
            ->put(route('disasters.update', $disaster->id), [
                'name' => $disaster->name,
                'npo_col_2' => '2026',
                'event_date' => '2026-11-01',
                'local_government_ids' => [],
                'disaster_type_ids' => [],
                'information_links' => [
                    [
                        'description' => '更新後の情報',
                        'url' => 'https://example.com/after',
                    ],
                ],
            ])
            ->assertRedirect(route('disasters.index'));

        $this->assertSame(1, $disaster->informationLinks()->count());
        $this->assertSame('更新後の情報', $disaster->informationLinks()->first()->description);
    }

    public function test_partial_information_link_is_rejected(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->from(route('disasters.create'))
            ->post(route('disasters.store'), $this->validPayload([
                [
                    'description' => 'URLのない概要文',
                    'url' => '',
                ],
            ]))
            ->assertSessionHasErrors('information_links.0.url');

        $this->assertSame(0, Disaster::count());
    }

    public function test_admin_can_open_forms_with_information_links(): void
    {
        $admin = $this->createAdmin();
        $disaster = $this->createDisasterWithLinks([
            [
                'description' => '令和8年8月豪雨の特設ページ',
                'url' => 'https://example.com/rain',
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('disasters.create'))
            ->assertOk()
            ->assertSee('最新情報リンク');

        $this->actingAs($admin)
            ->get(route('disasters.edit', $disaster->id))
            ->assertOk()
            ->assertSee('最新情報リンク')
            ->assertSee('https://example.com/rain');
    }

    public function test_information_links_are_shown_on_detail(): void
    {
        $disaster = $this->createDisasterWithLinks([
            [
                'description' => '令和8年8月豪雨の特設ページ',
                'url' => 'https://example.com/rain',
            ],
        ]);

        $this->get(route('disasters.show', $disaster->id))
            ->assertOk()
            ->assertSee('最新情報')
            ->assertSee('令和8年8月豪雨の特設ページ')
            ->assertSee('https://example.com/rain');
    }
}
