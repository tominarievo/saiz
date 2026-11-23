<?php

namespace Tests\Feature;

use App\Disaster;
use App\DisasterInformationLink;
use App\Services\CollectedInformationBlocklist;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectedInformationBlocklistTest extends TestCase
{
    use RefreshDatabase;

    private function createDisaster(): Disaster
    {
        $disaster = new Disaster();
        $disaster->name = '収集情報削除テスト災害';
        $disaster->status = true;
        $disaster->event_date = '2026-11-01';
        $disaster->save();

        return $disaster;
    }

    public function test_deleting_link_blocks_future_collection(): void
    {
        $disaster = $this->createDisaster();
        $link = $disaster->informationLinks()->create([
            'description' => 'テスト道路情報',
            'url' => 'https://www.pref.chiba.lg.jp/test.html',
            'source' => 'ai',
            'fetched_at' => now(),
        ]);

        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.test',
            'password' => bcrypt('secret'),
            'username' => 'admin',
            'organization_id' => null,
            'is_valid' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.collected_information.index', ['disaster_id' => $disaster->id]))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.collected_information.index', ['disaster_id' => $disaster->id, 'q' => 'テスト']))
            ->assertOk();

        $this->actingAs($admin)
            ->delete(route('admin.collected_information.destroy', ['type' => 'link', 'id' => $link->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('disaster_information_links', ['id' => $link->id]);
        $this->assertTrue(
            app(CollectedInformationBlocklist::class)->isUrlBlocked(
                $disaster->id,
                'https://www.pref.chiba.lg.jp/test.html'
            )
        );
    }

    public function test_bulk_delete_blocks_multiple_links(): void
    {
        $disaster = $this->createDisaster();
        $first = $disaster->informationLinks()->create([
            'description' => 'テスト道路情報1',
            'url' => 'https://www.pref.chiba.lg.jp/test1.html',
            'source' => 'ai',
            'fetched_at' => now(),
        ]);
        $second = $disaster->informationLinks()->create([
            'description' => 'テスト道路情報2',
            'url' => 'https://www.pref.chiba.lg.jp/test2.html',
            'source' => 'ai',
            'fetched_at' => now(),
        ]);

        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin2@example.test',
            'password' => bcrypt('secret'),
            'username' => 'admin2',
            'organization_id' => null,
            'is_valid' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.collected_information.bulk_destroy'), [
                'items' => ['link:' . $first->id, 'link:' . $second->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('disaster_information_links', ['id' => $first->id]);
        $this->assertDatabaseMissing('disaster_information_links', ['id' => $second->id]);
        $this->assertTrue(app(CollectedInformationBlocklist::class)->isUrlBlocked($disaster->id, 'https://www.pref.chiba.lg.jp/test1.html'));
        $this->assertTrue(app(CollectedInformationBlocklist::class)->isUrlBlocked($disaster->id, 'https://www.pref.chiba.lg.jp/test2.html'));
    }

    public function test_road_block_key_is_normalized(): void
    {
        $disaster = $this->createDisaster();
        $blocklist = app(CollectedInformationBlocklist::class);
        $key = CollectedInformationBlocklist::roadKey(
            'https://www.pref.chiba.lg.jp/road.pdf',
            '（一）犬成海士有木線',
            '市原市武士'
        );

        $blocklist->block($disaster->id, CollectedInformationBlocklist::TYPE_ROAD, $key, 'https://www.pref.chiba.lg.jp/road.pdf');

        $this->assertTrue($blocklist->isBlocked($disaster->id, CollectedInformationBlocklist::TYPE_ROAD, $key));
    }
}
