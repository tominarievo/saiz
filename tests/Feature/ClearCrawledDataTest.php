<?php

namespace Tests\Feature;

use App\Disaster;
use App\DisasterDataSource;
use App\DisasterIntelligenceSource;
use App\EvacuationShelter;
use App\LocalGovernment;
use App\MeetingInsight;
use App\Prefecture;
use App\RoadRegulation;
use App\VolunteerCenter;
use App\WasteSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClearCrawledDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_force_clears_only_cron_collected_data(): void
    {
        Storage::fake('local');

        $prefecture = new Prefecture();
        $prefecture->prefecture_code = '12';
        $prefecture->name = '千葉県';
        $prefecture->order = 12;
        $prefecture->save();

        $localGovernment = new LocalGovernment();
        $localGovernment->order = 1;
        $localGovernment->prefecture_id = $prefecture->id;
        $localGovernment->prefecture_code = '12';
        $localGovernment->code = '12100';
        $localGovernment->name = '千葉市';
        $localGovernment->government_type = LocalGovernment::GOVERNMENT_TYPE_TOWN;
        $localGovernment->is_designated_city = false;
        $localGovernment->save();

        $disaster = new Disaster();
        $disaster->name = 'クリアテスト災害';
        $disaster->status = true;
        $disaster->event_date = '2026-11-01';
        $disaster->save();
        $disaster->localGovernments()->attach($localGovernment->id);

        $road = RoadRegulation::create([
            'disaster_id' => $disaster->id,
            'road_name' => '国道1号',
            'regulation_type' => 'full_closure',
            'confidence_rank' => 'A',
            'source_org' => '千葉県',
            'source_url' => 'https://www.pref.chiba.lg.jp/road.pdf',
        ]);

        $waste = WasteSite::create([
            'disaster_id' => $disaster->id,
            'name' => '仮置場',
            'status' => 'open',
            'entry_allowed' => true,
            'source_org' => '千葉市',
            'source_url' => 'https://www.city.chiba.jp/waste.html',
        ]);

        $meeting = MeetingInsight::create([
            'disaster_id' => $disaster->id,
            'meeting_name' => '対策本部会議',
            'held_at' => now(),
            'source_org' => '千葉県',
            'source_url' => 'https://www.pref.chiba.lg.jp/meeting.pdf',
        ]);

        $intelligenceSource = DisasterIntelligenceSource::create([
            'disaster_id' => $disaster->id,
            'type' => 'roads',
            'url' => 'https://www.pref.chiba.lg.jp/road.pdf',
            'url_hash' => sha1('https://www.pref.chiba.lg.jp/road.pdf'),
            'source_org' => '千葉県',
            'credibility_rank' => 'A',
            'status' => 'active',
        ]);

        $dataSource = DisasterDataSource::create([
            'disaster_id' => $disaster->id,
            'category' => 'roads',
            'source_key' => sha1('roads|千葉県|https://www.pref.chiba.lg.jp/road.pdf'),
            'source_name' => '千葉県',
            'source_url' => 'https://www.pref.chiba.lg.jp/road.pdf',
        ]);

        $aiLink = $disaster->informationLinks()->create([
            'description' => 'AIリンク',
            'url' => 'https://www.pref.chiba.lg.jp/ai.html',
            'source' => 'ai',
        ]);

        $manualLink = $disaster->informationLinks()->create([
            'description' => '手動リンク',
            'url' => 'https://www.pref.chiba.lg.jp/manual.html',
            'source' => 'manual',
        ]);

        $shelter = EvacuationShelter::create([
            'local_government_id' => $localGovernment->id,
            'name' => 'テスト避難所',
            'shelter_type' => 'designated_shelter',
            'source_name' => '国土地理院',
            'source_url' => 'https://hinanmap.gsi.go.jp/',
            'is_active' => true,
            'activity_status' => 'open',
            'evacuees' => 12,
            'status_note' => '開設中',
            'status_source_name' => '千葉市',
            'status_source_url' => 'https://www.city.chiba.jp/shelter.html',
            'status_checked_at' => now(),
            'status_changed_at' => now(),
        ]);

        $center = VolunteerCenter::create([
            'local_government_id' => $localGovernment->id,
            'name' => 'テストボランティアセンター',
            'source_name' => '千葉県社会福祉協議会',
            'source_url' => 'https://www.chibakenshakyo.or.jp/',
            'latest_recruitment_title' => '募集',
            'latest_recruitment_summary' => '概要',
            'latest_recruitment_url' => 'https://www.chibakenshakyo.or.jp/recruit',
            'recruitment_updated_at' => now(),
            'fetched_at' => now(),
        ]);

        Storage::disk('local')->put('disaster-sources/1/test.pdf', 'dummy');

        $this->artisan('crawled-data:clear', ['--force' => true])->assertExitCode(0);

        $this->assertDatabaseMissing('road_regulations', ['id' => $road->id]);
        $this->assertDatabaseMissing('waste_sites', ['id' => $waste->id]);
        $this->assertDatabaseMissing('meeting_insights', ['id' => $meeting->id]);
        $this->assertDatabaseMissing('disaster_intelligence_sources', ['id' => $intelligenceSource->id]);
        $this->assertDatabaseMissing('disaster_data_sources', ['id' => $dataSource->id]);
        $this->assertDatabaseMissing('disaster_information_links', ['id' => $aiLink->id]);
        $this->assertDatabaseHas('disaster_information_links', ['id' => $manualLink->id]);

        $this->assertDatabaseHas('evacuation_shelters', [
            'id' => $shelter->id,
            'is_active' => false,
            'activity_status' => 'unknown',
            'evacuees' => null,
            'status_source_name' => null,
        ]);

        $this->assertDatabaseHas('volunteer_centers', [
            'id' => $center->id,
            'latest_recruitment_title' => null,
            'latest_recruitment_summary' => null,
            'recruitment_updated_at' => null,
            'fetched_at' => null,
        ]);

        Storage::disk('local')->assertMissing('disaster-sources/1/test.pdf');
    }

    public function test_dry_run_keeps_data(): void
    {
        $disaster = new Disaster();
        $disaster->name = 'DryRun災害';
        $disaster->status = true;
        $disaster->event_date = '2026-11-01';
        $disaster->save();

        $road = RoadRegulation::create([
            'disaster_id' => $disaster->id,
            'road_name' => '国道2号',
            'regulation_type' => 'full_closure',
            'confidence_rank' => 'A',
            'source_org' => 'テスト県',
            'source_url' => 'https://www.pref.chiba.lg.jp/road2.pdf',
        ]);

        $this->artisan('crawled-data:clear', ['--dry-run' => true])->assertExitCode(0);

        $this->assertDatabaseHas('road_regulations', ['id' => $road->id]);
    }
}
