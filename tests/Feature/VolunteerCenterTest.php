<?php

namespace Tests\Feature;

use App\Disaster;
use App\LocalGovernment;
use App\Prefecture;
use App\VolunteerCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VolunteerCenterTest extends TestCase
{
    use RefreshDatabase;

    private function createDisaster(): array
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
        $localGovernment->code = '162051';
        $localGovernment->name = '氷見市';
        $localGovernment->government_type = LocalGovernment::GOVERNMENT_TYPE_TOWN;
        $localGovernment->is_designated_city = false;
        $localGovernment->save();

        $disaster = new Disaster();
        $disaster->name = 'ボランティアセンターテスト災害';
        $disaster->status = true;
        $disaster->npo_col_2 = '令和8年度';
        $disaster->event_date = '2026-08-27';
        $disaster->save();
        $disaster->localGovernments()->sync([$localGovernment->id]);

        return [$disaster, $localGovernment];
    }

    public function test_guest_can_fetch_volunteer_centers_with_latest_recruitment(): void
    {
        [$disaster, $localGovernment] = $this->createDisaster();

        VolunteerCenter::create([
            'local_government_id' => $localGovernment->id,
            'name' => '氷見市災害ボランティア・支え合いセンター',
            'address' => '富山県氷見市中央町12-21',
            'latitude' => 36.860023,
            'longitude' => 136.985229,
            'phone' => '090-5687-2202',
            'url' => 'https://himivc-toyama.jimdofree.com/',
            'latest_recruitment_title' => 'ボランティア募集受付中',
            'latest_recruitment_summary' => '事前登録制で募集しています。',
            'latest_recruitment_url' => 'https://himivc-toyama.jimdofree.com/',
            'recruitment_updated_at' => now(),
            'source_name' => '公式サイト',
            'source_url' => 'https://himivc-toyama.jimdofree.com/',
            'fetched_at' => now(),
        ]);

        $this->getJson(route('disasters.volunteer_centers', $disaster->id))
            ->assertOk()
            ->assertJsonPath('meta.available', true)
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('centers.0.name', '氷見市災害ボランティア・支え合いセンター')
            ->assertJsonPath('centers.0.latest_recruitment.title', 'ボランティア募集受付中')
            ->assertJsonPath('centers.0.latest_recruitment.summary', '事前登録制で募集しています。');
    }
}
