<?php

namespace Tests\Feature;

use App\Disaster;
use App\HistoricalBoundary;
use App\LocalGovernment;
use App\Organization;
use App\Prefecture;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HistoricalBoundaryLayerTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        $admin = new User();
        $admin->username = 'historical-boundary-admin@example.test';
        $admin->email = 'historical-boundary-admin@example.test';
        $admin->name = '災害管理者';
        $admin->password = Hash::make('AdminPassword_1!');
        $admin->is_valid = true;
        $admin->is_system_admin = true;
        $admin->save();

        return $admin;
    }

    private function createDisasterInToyama(): Disaster
    {
        $prefecture = new Prefecture();
        $prefecture->prefecture_code = '16';
        $prefecture->name = '富山県';
        $prefecture->order = 16;
        $prefecture->save();

        $localGovernment = new LocalGovernment();
        $localGovernment->order = 1;
        $localGovernment->prefecture_id = $prefecture->id;
        $localGovernment->prefecture_code = '16';
        $localGovernment->code = '16321';
        $localGovernment->name = '富山市';
        $localGovernment->government_type = LocalGovernment::GOVERNMENT_TYPE_TOWN;
        $localGovernment->is_designated_city = false;
        $localGovernment->save();

        $disaster = new Disaster();
        $disaster->name = '合併前行政界テスト災害';
        $disaster->status = true;
        $disaster->npo_col_2 = '2026';
        $disaster->event_date = '2026-11-01';
        $disaster->save();
        $disaster->localGovernments()->sync([$localGovernment->id]);

        return $disaster;
    }

    private function createBoundary(): HistoricalBoundary
    {
        return HistoricalBoundary::create([
            'prefecture_code' => '16',
            'prefecture_name' => '富山県',
            'boundary_date' => '1920-01-01',
            'resolution' => 'h',
            'source' => '歴史的行政区域データセットβ版（CODH）/ 国土数値情報',
            'license' => 'CC BY-SA 4.0',
            'bbox' => [136.7, 36.2, 137.8, 36.9],
            'topojson' => [
                'type' => 'Topology',
                'objects' => [
                    'city' => [
                        'type' => 'GeometryCollection',
                        'geometries' => [],
                    ],
                ],
                'arcs' => [],
            ],
            'feature_count' => 0,
        ]);
    }

    public function test_historical_boundary_layer_returns_selected_prefecture(): void
    {
        $admin = $this->createAdmin();
        $disaster = $this->createDisasterInToyama();
        $this->createBoundary();

        $this->actingAs($admin)
            ->getJson(route('disasters.historical_boundaries', [
                'disaster' => $disaster->id,
                'date' => '1920-01-01',
            ]))
            ->assertOk()
            ->assertJsonPath('date', '1920-01-01')
            ->assertJsonPath('layers.0.prefecture_code', '16')
            ->assertJsonPath('layers.0.prefecture_name', '富山県')
            ->assertJsonPath('layers.0.topojson.type', 'Topology')
            ->assertJsonCount(1, 'layers');
    }

    public function test_show_page_contains_historical_layer_controls(): void
    {
        $admin = $this->createAdmin();
        $disaster = $this->createDisasterInToyama();
        $this->createBoundary();

        $this->actingAs($admin)
            ->get(route('disasters.show', $disaster->id))
            ->assertOk()
            ->assertSee('合併前行政界')
            ->assertSee('1920-01-01')
            ->assertSee('id="historical-date"', false)
            ->assertSee('id="historical-name"', false);
    }

    public function test_historical_boundary_layer_is_empty_when_not_imported(): void
    {
        $admin = $this->createAdmin();
        $disaster = $this->createDisasterInToyama();

        $this->actingAs($admin)
            ->getJson(route('disasters.historical_boundaries', [
                'disaster' => $disaster->id,
                'date' => '1920-01-01',
            ]))
            ->assertOk()
            ->assertJsonCount(0, 'layers');
    }

    public function test_guest_can_fetch_historical_boundary_layer(): void
    {
        $disaster = $this->createDisasterInToyama();

        $this->getJson(route('disasters.historical_boundaries', [
            'disaster' => $disaster->id,
            'date' => '1920-01-01',
        ]))->assertOk();
    }

    public function test_organization_user_can_fetch_historical_boundary_layer(): void
    {
        $disaster = $this->createDisasterInToyama();

        $organization = new Organization();
        $organization->name = 'テスト支援団体';
        $organization->status = true;
        $organization->save();

        $user = new User();
        $user->organization_id = $organization->id;
        $user->username = 'historical-boundary-user@example.test';
        $user->email = 'historical-boundary-user@example.test';
        $user->name = 'テストユーザー';
        $user->password = Hash::make('UserPassword_1!');
        $user->is_valid = true;
        $user->save();

        $this->actingAs($user)
            ->getJson(route('disasters.historical_boundaries', [
                'disaster' => $disaster->id,
                'date' => '1920-01-01',
            ]))
            ->assertOk();
    }
}
