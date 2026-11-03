<?php

namespace Tests\Feature;

use App\Disaster;
use App\LocalGovernment;
use App\Organization;
use App\Prefecture;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SchoolDistrictLayerTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        $admin = new User();
        $admin->username = 'school-layer-admin@example.test';
        $admin->email = 'school-layer-admin@example.test';
        $admin->name = '災害管理者';
        $admin->password = Hash::make('AdminPassword_1!');
        $admin->is_valid = true;
        $admin->is_system_admin = true;
        $admin->save();

        return $admin;
    }

    private function createDisasterWithLocalGovernment(): array
    {
        $prefecture = new Prefecture();
        $prefecture->prefecture_code = '13';
        $prefecture->name = '東京都';
        $prefecture->order = 13;
        $prefecture->save();

        $localGovernment = new LocalGovernment();
        $localGovernment->order = 1;
        $localGovernment->prefecture_id = $prefecture->id;
        $localGovernment->prefecture_code = '13';
        $localGovernment->code = '131016';
        $localGovernment->name = '千代田区';
        $localGovernment->government_type = LocalGovernment::GOVERNMENT_TYPE_TOWN;
        $localGovernment->is_designated_city = false;
        $localGovernment->save();

        $disaster = new Disaster();
        $disaster->name = '学区レイヤーテスト災害';
        $disaster->status = true;
        $disaster->npo_col_2 = '2026';
        $disaster->event_date = '2026-11-01';
        $disaster->save();
        $disaster->localGovernments()->sync([$localGovernment->id]);

        return [$disaster, $localGovernment];
    }

    private function schoolDistrictGeoJson(): array
    {
        return [
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'properties' => [
                        'A27_001' => '13101',
                        'A27_002' => '千代田区立',
                        'A27_003' => 'B113210100001',
                        'A27_004' => '千代田小学校',
                        'A27_005' => '東京都千代田区',
                    ],
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [[
                            [139.75, 35.69],
                            [139.76, 35.69],
                            [139.76, 35.70],
                            [139.75, 35.69],
                        ]],
                    ],
                ],
            ],
        ];
    }

    public function test_school_district_layer_returns_selected_municipality_features(): void
    {
        Storage::fake('local');

        $admin = $this->createAdmin();
        [$disaster] = $this->createDisasterWithLocalGovernment();

        Storage::disk('local')->put(
            'school-districts/A27/13/13101.json',
            json_encode($this->schoolDistrictGeoJson(), JSON_UNESCAPED_UNICODE)
        );

        $this->actingAs($admin)
            ->getJson(route('disasters.school_districts', [
                'disaster' => $disaster->id,
                'type' => 'elementary',
            ]))
            ->assertOk()
            ->assertJsonPath('features.0.properties.A27_004', '千代田小学校')
            ->assertJsonPath('meta.available', true)
            ->assertJsonCount(1, 'features');
    }

    public function test_missing_school_district_data_returns_empty_collection(): void
    {
        Storage::fake('local');

        $admin = $this->createAdmin();
        [$disaster] = $this->createDisasterWithLocalGovernment();

        $this->actingAs($admin)
            ->getJson(route('disasters.school_districts', [
                'disaster' => $disaster->id,
                'type' => 'elementary',
            ]))
            ->assertOk()
            ->assertJsonPath('meta.available', false)
            ->assertJsonCount(0, 'features')
            ->assertJsonPath('meta.missing.0', '東京都 千代田区');
    }

    public function test_guest_can_fetch_school_district_layer(): void
    {
        Storage::fake('local');

        [$disaster] = $this->createDisasterWithLocalGovernment();

        $this->getJson(route('disasters.school_districts', [
            'disaster' => $disaster->id,
            'type' => 'elementary',
        ]))->assertOk();
    }

    public function test_organization_user_can_fetch_school_district_layer(): void
    {
        Storage::fake('local');

        [$disaster] = $this->createDisasterWithLocalGovernment();

        $organization = new Organization();
        $organization->name = 'テスト支援団体';
        $organization->status = true;
        $organization->save();

        $user = new User();
        $user->organization_id = $organization->id;
        $user->username = 'school-layer-user@example.test';
        $user->email = 'school-layer-user@example.test';
        $user->name = 'テストユーザー';
        $user->password = Hash::make('UserPassword_1!');
        $user->is_valid = true;
        $user->save();

        $this->actingAs($user)
            ->getJson(route('disasters.school_districts', [
                'disaster' => $disaster->id,
                'type' => 'elementary',
            ]))
            ->assertOk();
    }
}
