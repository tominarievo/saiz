<?php

namespace Tests\Feature;

use App\Disaster;
use App\EvacuationShelter;
use App\LocalGovernment;
use App\Prefecture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvacuationShelterTest extends TestCase
{
    use RefreshDatabase;

    private function createPrefecture(): Prefecture
    {
        $prefecture = new Prefecture();
        $prefecture->prefecture_code = '16';
        $prefecture->name = '富山県';
        $prefecture->order = 16;
        $prefecture->save();

        return $prefecture;
    }

    private function createLocalGovernment(Prefecture $prefecture, string $code, string $name): LocalGovernment
    {
        $localGovernment = new LocalGovernment();
        $localGovernment->order = 1;
        $localGovernment->prefecture_id = $prefecture->id;
        $localGovernment->prefecture_code = $prefecture->prefecture_code;
        $localGovernment->code = $code;
        $localGovernment->name = $name;
        $localGovernment->government_type = LocalGovernment::GOVERNMENT_TYPE_TOWN;
        $localGovernment->is_designated_city = false;
        $localGovernment->save();

        return $localGovernment;
    }

    private function createDisaster(LocalGovernment $localGovernment): Disaster
    {
        $disaster = new Disaster();
        $disaster->name = '避難所レイヤーテスト災害';
        $disaster->status = true;
        $disaster->npo_col_2 = '令和8年度';
        $disaster->event_date = '2026-08-27';
        $disaster->save();
        $disaster->localGovernments()->sync([$localGovernment->id]);

        return $disaster;
    }

    private function createShelter(LocalGovernment $localGovernment, string $name, string $type): EvacuationShelter
    {
        return EvacuationShelter::create([
            'local_government_id' => $localGovernment->id,
            'common_id' => 'E' . $localGovernment->code . $type,
            'name' => $name,
            'address' => '富山県' . $localGovernment->name . '1-1',
            'latitude' => 36.85,
            'longitude' => 136.98,
            'shelter_type' => $type,
            'disaster_types' => $type === 'emergency_site' ? ['洪水', '地震'] : null,
            'note' => null,
            'source_name' => '国土地理院 指定緊急避難場所・指定避難所データ',
            'source_url' => 'https://hinanmap.gsi.go.jp/',
            'fetched_at' => now(),
        ]);
    }

    public function test_guest_can_fetch_shelters_for_selected_municipalities(): void
    {
        $prefecture = $this->createPrefecture();
        $himi = $this->createLocalGovernment($prefecture, '162051', '氷見市');
        $takaoka = $this->createLocalGovernment($prefecture, '162027', '高岡市');
        $disaster = $this->createDisaster($himi);

        $activeShelter = $this->createShelter($himi, '氷見市立海峰小学校', 'designated_shelter');
        $this->createShelter($himi, '氷見市立海峰小学校', 'emergency_site');
        $this->createShelter($takaoka, '高岡市立学校', 'designated_shelter');

        $activeShelter->update([
            'is_active' => true,
            'activity_status' => 'open',
            'evacuees' => 12,
            'status_note' => 'テスト開設中',
            'status_source_name' => '氷見市 避難所情報',
            'status_source_url' => 'https://www.city.himi.toyama.jp/emergency/9236.html',
            'status_checked_at' => now(),
        ]);

        $response = $this->getJson(route('disasters.evacuation_shelters', $disaster->id))
            ->assertOk()
            ->assertJsonPath('meta.available', true)
            ->assertJsonPath('meta.count', 2)
            ->assertJsonPath('meta.active_count', 1)
            ->assertJsonPath('shelters.0.name', '氷見市立海峰小学校')
            ->assertJsonPath('shelters.0.is_active', true)
            ->assertJsonPath('shelters.0.evacuees', 12)
            ->assertJsonPath('shelters.0.status_note', 'テスト開設中')
            ->assertJsonCount(2, 'shelters');

        $this->assertNotNull($response->json('shelters.0.status_checked_at'));
    }
}
