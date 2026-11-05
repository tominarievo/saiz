<?php

namespace Tests\Feature;

use App\Disaster;
use App\MeetingInsight;
use App\RoadRegulation;
use App\WasteSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisasterIntelligenceApiTest extends TestCase
{
    use RefreshDatabase;

    private function createDisaster(): Disaster
    {
        $disaster = new Disaster();
        $disaster->name = '専門支援情報テスト災害';
        $disaster->status = true;
        $disaster->event_date = '2026-11-01';
        $disaster->save();

        return $disaster;
    }

    public function test_roads_api_returns_geojson_with_attribution(): void
    {
        $disaster = $this->createDisaster();

        RoadRegulation::create([
            'disaster_id' => $disaster->id,
            'road_name' => '国道テスト線',
            'section' => 'テスト交差点〜テスト橋',
            'regulation_type' => 'full_closure',
            'vehicle_classes' => ['light_truck', '2t', '4t', 'large'],
            'reason' => '冠水',
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [[140.1, 35.6], [140.2, 35.7]],
            ],
            'confidence_rank' => 'A',
            'source_org' => 'テスト県',
            'source_url' => 'https://example.test/roads.pdf',
            'source_published_at' => now(),
            'scraped_at' => now(),
            'source_credibility' => 'A',
            'source_credibility_reason' => '県の公式資料',
        ]);

        $this->getJson('/api/v1/disaster/roads?disaster_id=' . $disaster->id)
            ->assertOk()
            ->assertJsonPath('type', 'FeatureCollection')
            ->assertJsonPath('features.0.geometry.type', 'LineString')
            ->assertJsonPath('features.0.properties.source.org', 'テスト県')
            ->assertJsonPath('features.0.properties.source.credibility', 'A')
            ->assertJsonPath('features.0.properties.source.credibility_reason', '県の公式資料')
            ->assertJsonPath('features.0.properties.ai_extracted', false)
            ->assertJsonPath('meta.count', 1);
    }

    public function test_roads_api_filters_by_vehicle_class(): void
    {
        $disaster = $this->createDisaster();

        RoadRegulation::create([
            'disaster_id' => $disaster->id,
            'road_name' => '軽トラのみ通行可',
            'regulation_type' => 'large_vehicle',
            'vehicle_classes' => ['light_truck'],
            'geometry' => ['type' => 'Point', 'coordinates' => [140.1, 35.6]],
            'confidence_rank' => 'B',
            'source_org' => 'テスト市',
            'source_url' => 'https://example.test/road-1.pdf',
        ]);

        $this->getJson('/api/v1/disaster/roads?disaster_id=' . $disaster->id . '&vehicle=4t')
            ->assertOk()
            ->assertJsonPath('meta.count', 0);

        $this->getJson('/api/v1/disaster/roads?disaster_id=' . $disaster->id . '&vehicle=light_truck')
            ->assertOk()
            ->assertJsonPath('meta.count', 1);
    }

    public function test_waste_sites_api_returns_geojson_and_filters_vehicle_limit(): void
    {
        $disaster = $this->createDisaster();

        WasteSite::create([
            'disaster_id' => $disaster->id,
            'name' => 'テスト仮置場',
            'status' => 'open',
            'geometry' => ['type' => 'Point', 'coordinates' => [140.1, 35.6]],
            'max_payload_tons' => 2,
            'entry_allowed' => true,
            'accepted_items' => ['木くず'],
            'rejected_items' => ['危険物'],
            'support_conditions' => ['permit_required' => true],
            'source_org' => 'テスト市',
            'source_url' => 'https://example.test/waste.pdf',
        ]);

        $this->getJson('/api/v1/disaster/waste-sites?disaster_id=' . $disaster->id)
            ->assertOk()
            ->assertJsonPath('type', 'FeatureCollection')
            ->assertJsonPath('features.0.properties.name', 'テスト仮置場')
            ->assertJsonPath('features.0.properties.source.org', 'テスト市')
            ->assertJsonPath('meta.count', 1);

        $this->getJson('/api/v1/disaster/waste-sites?disaster_id=' . $disaster->id . '&vehicle=4t')
            ->assertOk()
            ->assertJsonPath('meta.count', 1);

        $this->getJson('/api/v1/disaster/waste-sites?disaster_id=' . $disaster->id . '&vehicle=large')
            ->assertOk()
            ->assertJsonPath('meta.count', 1);
    }

    public function test_waste_sites_api_deduplicates_same_name_and_keeps_latest_source(): void
    {
        $disaster = $this->createDisaster();

        WasteSite::create([
            'disaster_id' => $disaster->id,
            'name' => '同じ仮置場',
            'status' => 'open',
            'geometry' => ['type' => 'Point', 'coordinates' => [140.1, 35.6]],
            'entry_allowed' => true,
            'source_org' => '古い市',
            'source_url' => 'https://example.test/old.pdf',
            'source_published_at' => now()->subDays(10),
            'scraped_at' => now()->subDays(10),
        ]);

        WasteSite::create([
            'disaster_id' => $disaster->id,
            'name' => '同じ仮置場',
            'status' => 'open',
            'geometry' => ['type' => 'Point', 'coordinates' => [140.2, 35.7]],
            'entry_allowed' => true,
            'source_org' => '新しい市',
            'source_url' => 'https://example.test/new.pdf',
            'source_published_at' => now(),
            'scraped_at' => now(),
        ]);

        $this->getJson('/api/v1/disaster/waste-sites?disaster_id=' . $disaster->id)
            ->assertOk()
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('features.0.properties.source.org', '新しい市');
    }

    public function test_meetings_api_returns_summary_and_deduplicates_by_held_at(): void
    {
        $disaster = $this->createDisaster();

        MeetingInsight::create([
            'disaster_id' => $disaster->id,
            'meeting_name' => '古い会議名',
            'held_at' => now()->subDay(),
            'decisions' => ['古い決定'],
            'source_org' => '古い県',
            'source_url' => 'https://example.test/old-meeting.pdf',
            'source_published_at' => now()->subDays(2),
            'scraped_at' => now()->subDays(2),
        ]);

        MeetingInsight::create([
            'disaster_id' => $disaster->id,
            'meeting_name' => '最新の会議名',
            'held_at' => now()->subDay(),
            'decisions' => ['新しい決定'],
            'policy_changes' => ['撤収予定'],
            'unmet_needs' => ['重機不足'],
            'source_org' => '新しい県',
            'source_url' => 'https://example.test/new-meeting.pdf',
            'source_credibility' => 'A',
            'source_credibility_reason' => '公式資料',
            'source_published_at' => now(),
            'scraped_at' => now(),
        ]);

        MeetingInsight::create([
            'disaster_id' => $disaster->id,
            'meeting_name' => '内容なしの最新会議',
            'held_at' => now()->subDay(),
            'source_org' => '空の県',
            'source_url' => 'https://example.test/empty-meeting.pdf',
            'source_published_at' => now()->addMinute(),
        ]);

        $this->getJson('/api/v1/disaster/meetings?disaster_id=' . $disaster->id)
            ->assertOk()
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('meetings.0.meeting_name', '最新の会議名')
            ->assertJsonPath('meetings.0.decisions.0', '新しい決定')
            ->assertJsonPath('meetings.0.source.org', '新しい県')
            ->assertJsonPath('meetings.0.source.credibility', 'A');
    }

    public function test_intelligence_page_is_public(): void
    {
        $disaster = $this->createDisaster();

        $this->get('/disasters/' . $disaster->id . '/intelligence')
            ->assertOk()
            ->assertSee('専門支援情報')
            ->assertSee('disaster-intelligence-map-' . $disaster->id);
    }
}
