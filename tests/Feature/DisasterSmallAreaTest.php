<?php

namespace Tests\Feature;

use App\Disaster;
use App\LocalGovernment;
use App\Prefecture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DisasterSmallAreaTest extends TestCase
{
    use RefreshDatabase;

    private function createDisaster(): Disaster
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
        $localGovernment->code = '16205';
        $localGovernment->name = '氷見市';
        $localGovernment->government_type = LocalGovernment::GOVERNMENT_TYPE_TOWN;
        $localGovernment->is_designated_city = false;
        $localGovernment->save();

        $disaster = new Disaster();
        $disaster->name = '小区域界テスト災害';
        $disaster->status = true;
        $disaster->npo_col_2 = '令和8年度';
        $disaster->event_date = '2026-08-27';
        $disaster->save();
        $disaster->localGovernments()->sync([$localGovernment->id]);

        return $disaster;
    }

    private function boundaryPayload(): array
    {
        return [
            'type' => 'FeatureCollection',
            'metadata' => [
                'as_of' => '2020-10-01',
            ],
            'features' => [
                [
                    'type' => 'Feature',
                    'properties' => [
                        'key_code' => '162050010',
                        'name' => '伊勢大町',
                        'municipality' => '氷見市',
                        'population' => 986,
                        'households' => 430,
                        'age_65_plus' => 300,
                        'age_75_plus' => 150,
                    ],
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [[
                            [136.98, 36.85],
                            [136.99, 36.85],
                            [136.99, 36.86],
                            [136.98, 36.85],
                        ]],
                    ],
                ],
            ],
        ];
    }

    public function test_guest_can_fetch_small_area_boundaries_for_selected_municipalities(): void
    {
        Storage::fake('local');

        $disaster = $this->createDisaster();

        Storage::disk('local')->put(
            'population/16/small-areas/16205.geojson',
            json_encode($this->boundaryPayload(), JSON_UNESCAPED_UNICODE)
        );

        $this->getJson(route('disasters.small_areas', $disaster->id))
            ->assertOk()
            ->assertJsonPath('metadata.available', true)
            ->assertJsonPath('features.0.properties.name', '伊勢大町')
            ->assertJsonPath('features.0.properties.population', 986)
            ->assertJsonCount(1, 'features');
    }

    public function test_missing_small_area_boundary_file_is_reported(): void
    {
        Storage::fake('local');

        $disaster = $this->createDisaster();

        $this->getJson(route('disasters.small_areas', $disaster->id))
            ->assertOk()
            ->assertJsonPath('metadata.available', false)
            ->assertJsonPath('metadata.missing.0', '富山県 氷見市')
            ->assertJsonCount(0, 'features');
    }
}
