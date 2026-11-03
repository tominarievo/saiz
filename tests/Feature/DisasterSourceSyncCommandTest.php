<?php

namespace Tests\Feature;

use App\Disaster;
use App\Jobs\ParseDisasterSourceJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DisasterSourceSyncCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_command_detects_and_dispatches_source_job(): void
    {
        $disaster = new Disaster();
        $disaster->name = '資料同期テスト災害';
        $disaster->status = true;
        $disaster->event_date = '2026-11-01';
        $disaster->save();

        Storage::fake('local');
        Cache::flush();
        Queue::fake();

        Http::fake([
            'https://example.test/document.pdf' => Http::response('dummy pdf body', 200, [
                'Content-Type' => 'application/pdf',
                'Last-Modified' => 'Wed, 21 Oct 2026 07:28:00 GMT',
            ]),
        ]);

        $this->artisan('disaster-intelligence:sync', [
            'disaster' => $disaster->id,
            '--url' => 'https://example.test/document.pdf',
            '--type' => 'meeting_insights',
            '--org' => 'テスト県',
        ])->assertExitCode(0);

        Queue::assertPushed(ParseDisasterSourceJob::class, function (ParseDisasterSourceJob $job) use ($disaster) {
            return $job->disasterId === $disaster->id
                && $job->type === 'meeting_insights'
                && $job->sourceOrg === 'テスト県';
        });

        $files = Storage::disk('local')->allFiles('disaster-sources/' . $disaster->id);
        $this->assertCount(1, $files);
    }
}
