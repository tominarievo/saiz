<?php

namespace Tests\Feature;

use App\Disaster;
use App\LocalGovernment;
use App\Prefecture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DisasterPopulationTest extends TestCase
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
        $disaster->name = '人口データテスト災害';
        $disaster->status = true;
        $disaster->npo_col_2 = '令和8年度';
        $disaster->event_date = '2026-08-27';
        $disaster->save();
        $disaster->localGovernments()->sync([$localGovernment->id]);

        return $disaster;
    }

    private function populationPayload(): array
    {
        return [
            'schema_version' => 1,
            'generated_at' => '2026-11-02T00:00:00Z',
            'municipality' => [
                'code' => '16205',
                'name' => '氷見市',
                'prefecture' => '富山県',
                'prefecture_code' => '16',
            ],
            'summary' => [
                'as_of' => '2026-10-01',
                'population' => 40402,
                'male' => 19505,
                'female' => 20897,
                'households' => 17128,
                'foreigners' => [
                    'total' => 721,
                    'male' => 376,
                    'female' => 345,
                ],
            ],
            'age_groups' => [
                'as_of' => '2020-10-01',
                'groups' => [
                    [
                        'label' => '0～4歳',
                        'total' => 1046,
                        'male' => 548,
                        'female' => 498,
                        'foreigners' => 2,
                    ],
                ],
            ],
            'small_areas' => [
                'as_of' => '2020-10-01',
                'items' => [
                    [
                        'name' => '窪',
                        'population' => 3789,
                        'male' => 1840,
                        'female' => 1949,
                        'age_65_plus' => 1214,
                        'age_75_plus' => 598,
                    ],
                ],
            ],
            'mesh' => null,
        ];
    }

    public function test_guest_can_fetch_population_data_for_selected_municipalities(): void
    {
        Storage::fake('local');

        $disaster = $this->createDisaster();

        Storage::disk('local')->put(
            'population/16/16205.json',
            json_encode($this->populationPayload(), JSON_UNESCAPED_UNICODE)
        );

        $this->getJson(route('disasters.population', $disaster->id))
            ->assertOk()
            ->assertJsonPath('meta.available', true)
            ->assertJsonPath('municipalities.0.municipality.name', '氷見市')
            ->assertJsonPath('municipalities.0.summary.population', 40402)
            ->assertJsonPath('municipalities.0.age_groups.groups.0.label', '0～4歳')
            ->assertJsonPath('municipalities.0.small_areas.items.0.name', '窪');
    }

    public function test_missing_population_file_is_reported(): void
    {
        Storage::fake('local');

        $disaster = $this->createDisaster();

        $this->getJson(route('disasters.population', $disaster->id))
            ->assertOk()
            ->assertJsonPath('meta.available', false)
            ->assertJsonPath('meta.missing.0', '富山県 氷見市')
            ->assertJsonCount(0, 'municipalities');
    }
}
