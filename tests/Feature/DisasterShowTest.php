<?php

namespace Tests\Feature;

use App\Disaster;
use App\LocalGovernment;
use App\Organization;
use App\Prefecture;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DisasterShowTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        $admin = new User();
        $admin->username = 'disaster-show-admin@example.test';
        $admin->email = 'disaster-show-admin@example.test';
        $admin->name = '災害管理者';
        $admin->password = Hash::make('AdminPassword_1!');
        $admin->is_valid = true;
        $admin->is_system_admin = true;
        $admin->save();

        return $admin;
    }

    private function createPrefecture(string $code, string $name, int $order): Prefecture
    {
        $prefecture = new Prefecture();
        $prefecture->prefecture_code = $code;
        $prefecture->name = $name;
        $prefecture->order = $order;
        $prefecture->save();

        return $prefecture;
    }

    private function createLocalGovernment(
        Prefecture $prefecture,
        string $code,
        string $name,
        int $order
    ): LocalGovernment {
        $localGovernment = new LocalGovernment();
        $localGovernment->order = $order;
        $localGovernment->prefecture_id = $prefecture->id;
        $localGovernment->prefecture_code = $prefecture->prefecture_code;
        $localGovernment->code = $code;
        $localGovernment->name = $name;
        $localGovernment->government_type = LocalGovernment::GOVERNMENT_TYPE_TOWN;
        $localGovernment->is_designated_city = false;
        $localGovernment->save();

        return $localGovernment;
    }

    private function createDisaster(array $localGovernmentIds): Disaster
    {
        $disaster = new Disaster();
        $disaster->name = '詳細表示テスト災害';
        $disaster->status = true;
        $disaster->npo_col_2 = '2026';
        $disaster->event_date = '2026-11-01';
        $disaster->npo_col_3 = "1行目\n2行目";
        $disaster->save();
        $disaster->localGovernments()->sync($localGovernmentIds);

        return $disaster;
    }

    public function test_admin_can_view_disaster_detail_with_selected_areas(): void
    {
        $admin = $this->createAdmin();
        $tokyo = $this->createPrefecture('13', '東京都', 13);
        $osaka = $this->createPrefecture('27', '大阪府', 27);
        $hokkaido = $this->createPrefecture('01', '北海道', 1);

        $chiyoda = $this->createLocalGovernment($tokyo, '13101', '千代田区', 1);
        $osakaCity = $this->createLocalGovernment($osaka, '27102', '大阪市', 2);
        $sapporo = $this->createLocalGovernment($hokkaido, '01101', '札幌市', 3);

        $disaster = $this->createDisaster([$chiyoda->id, $osakaCity->id]);

        $this->actingAs($admin)
            ->get(route('disasters.show', $disaster->id))
            ->assertOk()
            ->assertSee('詳細表示テスト災害')
            ->assertSee('東京都')
            ->assertSee('大阪府')
            ->assertSee('千代田区')
            ->assertSee('大阪市')
            ->assertDontSee('北海道')
            ->assertDontSee('札幌市')
            ->assertSee('id="disaster-map"', false)
            ->assertSee('leaflet@1.9.4')
            ->assertSee('13101')
            ->assertSee('27102')
            ->assertDontSee('L.marker');
    }

    public function test_guest_can_view_disaster_detail(): void
    {
        $prefecture = $this->createPrefecture('13', '東京都', 13);
        $localGovernment = $this->createLocalGovernment($prefecture, '13101', '千代田区', 1);
        $disaster = $this->createDisaster([$localGovernment->id]);

        $this->get(route('disasters.show', $disaster->id))
            ->assertOk()
            ->assertSee('詳細表示テスト災害')
            ->assertSee('千代田区');
    }

    public function test_organization_user_can_view_disaster_detail(): void
    {
        $organization = new Organization();
        $organization->name = 'テスト支援団体';
        $organization->status = true;
        $organization->save();

        $user = new User();
        $user->organization_id = $organization->id;
        $user->username = 'disaster-show-user@example.test';
        $user->email = 'disaster-show-user@example.test';
        $user->name = 'テストユーザー';
        $user->password = Hash::make('UserPassword_1!');
        $user->is_valid = true;
        $user->save();

        $prefecture = $this->createPrefecture('13', '東京都', 13);
        $localGovernment = $this->createLocalGovernment($prefecture, '13101', '千代田区', 1);
        $disaster = $this->createDisaster([$localGovernment->id]);

        $this->actingAs($user)
            ->get(route('disasters.show', $disaster->id))
            ->assertOk();
    }
}
