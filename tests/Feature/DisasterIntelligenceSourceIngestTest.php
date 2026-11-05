<?php

namespace Tests\Feature;

use App\Disaster;
use App\DisasterIntelligenceSource;
use App\Jobs\ParseDisasterSourceJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DisasterIntelligenceSourceIngestTest extends TestCase
{
    use RefreshDatabase;

    private function createDisaster(): Disaster
    {
        $disaster = new Disaster();
        $disaster->name = '収集元探索テスト災害';
        $disaster->status = true;
        $disaster->event_date = '2026-11-01';
        $disaster->save();

        return $disaster;
    }

    public function test_ingest_stores_sources_and_caps_credibility_by_domain(): void
    {
        $disaster = $this->createDisaster();
        $path = tempnam(sys_get_temp_dir(), 'sources-');

        file_put_contents($path, json_encode([
            'sources' => [
                [
                    'type' => 'roads',
                    'url' => 'https://www.pref.chiba.lg.jp/road.pdf',
                    'source_org' => '千葉県',
                    'credibility_rank' => 'B',
                    'credibility_reason' => '県の公式PDF',
                ],
                [
                    'type' => 'meeting_insights',
                    'url' => 'https://example.com/minutes.pdf',
                    'source_org' => '不明な団体',
                    'credibility_rank' => 'A',
                    'credibility_reason' => 'AI判定A',
                ],
                [
                    'type' => 'unknown',
                    'url' => 'https://example.com/ignored.pdf',
                    'source_org' => '無視',
                ],
            ],
        ], JSON_UNESCAPED_UNICODE));

        try {
            $this->artisan('disaster-intelligence:source-ingest', [
                'disaster' => $disaster->id,
                '--file' => $path,
            ])->assertExitCode(0);

            $this->artisan('disaster-intelligence:source-ingest', [
                'disaster' => $disaster->id,
                '--file' => $path,
            ])->assertExitCode(0);
        } finally {
            @unlink($path);
        }

        $this->assertSame(2, DisasterIntelligenceSource::count());

        $road = DisasterIntelligenceSource::where('type', 'roads')->firstOrFail();
        $this->assertSame('B', $road->credibility_rank);

        $meeting = DisasterIntelligenceSource::where('type', 'meeting_insights')->firstOrFail();
        $this->assertSame('C', $meeting->credibility_rank);
        $this->assertSame(sha1($meeting->url), $meeting->url_hash);
    }

    public function test_sync_includes_discovered_sources_and_passes_credibility(): void
    {
        $disaster = $this->createDisaster();

        DisasterIntelligenceSource::create([
            'disaster_id' => $disaster->id,
            'type' => 'roads',
            'url' => 'https://example.test/discovered-road.pdf',
            'url_hash' => sha1('https://example.test/discovered-road.pdf'),
            'source_org' => 'テスト県',
            'credibility_rank' => 'A',
            'credibility_reason' => '県の公式発表',
            'status' => 'active',
        ]);

        Storage::fake('local');
        Cache::flush();
        Queue::fake();

        Http::fake([
            'https://example.test/discovered-road.pdf' => Http::response('dummy pdf body', 200, [
                'Content-Type' => 'application/pdf',
                'Last-Modified' => 'Wed, 21 Oct 2026 07:28:00 GMT',
            ]),
        ]);

        $this->artisan('disaster-intelligence:sync', [
            'disaster' => $disaster->id,
        ])->assertExitCode(0);

        Queue::assertPushed(ParseDisasterSourceJob::class, function (ParseDisasterSourceJob $job) use ($disaster) {
            return $job->disasterId === $disaster->id
                && $job->sourceUrl === 'https://example.test/discovered-road.pdf'
                && $job->sourceCredibility === 'A'
                && $job->sourceCredibilityReason === '県の公式発表';
        });

        $this->assertNotNull(
            DisasterIntelligenceSource::where('disaster_id', $disaster->id)->firstOrFail()->last_synced_at
        );
    }
}
