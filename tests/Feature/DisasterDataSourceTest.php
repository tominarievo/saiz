<?php

namespace Tests\Feature;

use App\Disaster;
use App\MeetingInsight;
use App\Organization;
use App\RoadRegulation;
use App\Services\DisasterDataSource\DisasterDataSourceRecorder;
use App\WasteSite;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisasterDataSourceTest extends TestCase
{
    use RefreshDatabase;

    private function createDisaster(bool $status = true): Disaster
    {
        $disaster = new Disaster();
        $disaster->name = '出典テスト災害';
        $disaster->status = $status;
        $disaster->event_date = '2026-08-13';
        $disaster->save();

        return $disaster;
    }

    private function createOrganization(): Organization
    {
        $organization = new Organization();
        $organization->name = '出典テスト団体';
        $organization->status = true;
        $organization->save();

        return $organization;
    }

    private function createSourceData(Disaster $disaster, Carbon $collectedAt): void
    {
        RoadRegulation::create([
            'disaster_id' => $disaster->id,
            'road_name' => 'テスト道路',
            'regulation_type' => 'full_closure',
            'geometry' => ['type' => 'Point', 'coordinates' => [140.1, 35.6]],
            'source_org' => 'テスト県',
            'source_url' => 'https://example.test/roads.pdf',
            'source_published_at' => $collectedAt->copy()->subDay(),
            'scraped_at' => $collectedAt,
        ]);

        WasteSite::create([
            'disaster_id' => $disaster->id,
            'name' => 'テスト仮置場',
            'status' => 'open',
            'geometry' => ['type' => 'Point', 'coordinates' => [140.2, 35.7]],
            'source_org' => 'テスト市',
            'source_url' => 'https://example.test/waste.pdf',
            'source_published_at' => $collectedAt->copy()->subDay(),
            'scraped_at' => $collectedAt,
        ]);

        MeetingInsight::create([
            'disaster_id' => $disaster->id,
            'meeting_name' => 'テスト会議',
            'held_at' => $collectedAt->copy()->subWeek(),
            'decisions' => ['テスト決定'],
            'source_org' => 'テスト県',
            'source_url' => 'https://example.test/meeting.pdf',
            'source_published_at' => $collectedAt->copy()->subDay(),
            'scraped_at' => $collectedAt,
        ]);

        $disaster->informationLinks()->create([
            'description' => 'テスト最新情報',
            'url' => 'https://example.test/news',
            'source' => 'ai',
            'fetched_at' => $collectedAt,
        ]);
    }

    public function test_rebuild_command_records_sources_and_collection_dates(): void
    {
        $disaster = $this->createDisaster();
        $collectedAt = Carbon::parse('2026-11-05 10:00:00');
        $this->createSourceData($disaster, $collectedAt);

        $this->artisan('disaster-data-sources:rebuild', ['disaster' => $disaster->id])
            ->assertExitCode(0);

        $this->assertSame(4, $disaster->dataSources()->count());
        $this->assertDatabaseHas('disaster_data_sources', [
            'disaster_id' => $disaster->id,
            'category' => 'roads',
            'source_name' => 'テスト県',
            'source_url' => 'https://example.test/roads.pdf',
        ]);
        $this->assertDatabaseHas('disaster_data_sources', [
            'disaster_id' => $disaster->id,
            'category' => 'information',
            'source_url' => 'https://example.test/news',
            'title' => 'テスト最新情報',
        ]);

        $roadSource = $disaster->dataSources()->where('category', 'roads')->firstOrFail();
        $this->assertTrue($roadSource->collected_at->equalTo($collectedAt));
    }

    public function test_sources_page_lists_categories_and_collection_dates(): void
    {
        $disaster = $this->createDisaster();
        $collectedAt = Carbon::parse('2026-11-05 10:00:00');
        $this->createSourceData($disaster, $collectedAt);

        $this->artisan('disaster-data-sources:rebuild', ['disaster' => $disaster->id])
            ->assertExitCode(0);

        $this->get('/disasters/' . $disaster->id . '/sources')
            ->assertOk()
            ->assertSee('データ出典')
            ->assertSee('最終収集日')
            ->assertSee('道路規制')
            ->assertSee('2026/11/05 10:00')
            ->assertSee('テスト県');
    }

    public function test_disaster_show_and_intelligence_pages_show_collection_dates(): void
    {
        $disaster = $this->createDisaster();
        $collectedAt = Carbon::parse('2026-11-05 10:00:00');
        $this->createSourceData($disaster, $collectedAt);

        $this->artisan('disaster-data-sources:rebuild', ['disaster' => $disaster->id])
            ->assertExitCode(0);

        $this->get('/disasters/' . $disaster->id)
            ->assertOk()
            ->assertSee('最終収集日')
            ->assertSee('データ出典');

        $this->get('/disasters/' . $disaster->id . '/intelligence')
            ->assertOk()
            ->assertSee('最終収集日')
            ->assertSee('データ出典');
    }

    public function test_sources_page_is_not_available_for_private_disaster_to_guest(): void
    {
        $disaster = $this->createDisaster(false);

        $this->get('/disasters/' . $disaster->id . '/sources')->assertNotFound();
    }

    public function test_recorder_keeps_latest_collection_date_and_does_not_duplicate_source(): void
    {
        $disaster = $this->createDisaster();
        $recorder = app(DisasterDataSourceRecorder::class);
        $earlier = Carbon::parse('2026-11-01 09:00:00');
        $later = Carbon::parse('2026-11-05 09:00:00');

        $recorder->record($disaster->id, 'roads', 'テスト県', 'https://example.test/road.pdf', null, $earlier);
        $recorder->record($disaster->id, 'roads', 'テスト県', 'https://example.test/road.pdf', null, $later);

        $this->assertSame(1, $disaster->dataSources()->count());
        $this->assertTrue($disaster->dataSources()->firstOrFail()->collected_at->equalTo($later));
    }

    public function test_information_link_ingest_records_source(): void
    {
        $disaster = $this->createDisaster();
        $json = json_encode([
            'links' => [
                ['description' => 'AI収集の最新情報', 'url' => 'https://www.pref.chiba.lg.jp/ai-news'],
            ],
        ], JSON_UNESCAPED_UNICODE);

        $this->artisan('disaster-links:ingest', [
            'disaster' => $disaster->id,
            '--json' => $json,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('disaster_data_sources', [
            'disaster_id' => $disaster->id,
            'category' => 'information',
            'source_url' => 'https://www.pref.chiba.lg.jp/ai-news',
            'title' => 'AI収集の最新情報',
        ]);
    }

    public function test_intelligence_source_ingest_records_source(): void
    {
        $disaster = $this->createDisaster();
        $json = json_encode([
            'sources' => [
                [
                    'type' => 'roads',
                    'url' => 'https://www.pref.chiba.lg.jp/roads-discovery',
                    'source_org' => 'テスト県',
                    'title' => '道路規制情報',
                    'credibility_rank' => 'A',
                ],
            ],
        ], JSON_UNESCAPED_UNICODE);

        $this->artisan('disaster-intelligence:source-ingest', [
            'disaster' => $disaster->id,
            '--json' => $json,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('disaster_data_sources', [
            'disaster_id' => $disaster->id,
            'category' => 'roads',
            'source_name' => 'テスト県',
            'source_url' => 'https://www.pref.chiba.lg.jp/roads-discovery',
        ]);
    }
}
