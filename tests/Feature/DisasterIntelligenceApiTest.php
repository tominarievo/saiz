<?php

namespace Tests\Feature;

use App\Disaster;
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
        ]);

        $this->getJson('/api/v1/disaster/roads?disaster_id=' . $disaster->id)
            ->assertOk()
            ->assertJsonPath('type', 'FeatureCollection')
            ->assertJsonPath('features.0.geometry.type', 'LineString')
            ->assertJsonPath('features.0.properties.source.org', 'テスト県')
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

    public function test_intelligence_page_is_public(): void
    {
        $disaster = $this->createDisaster();

        $this->get('/disasters/' . $disaster->id . '/intelligence')
            ->assertOk()
            ->assertSee('専門支援情報')
            ->assertSee('disaster-intelligence-map-' . $disaster->id);
    }
}
