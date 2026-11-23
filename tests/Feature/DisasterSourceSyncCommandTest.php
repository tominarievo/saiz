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
        config([
            'disaster-intelligence.crawler.respect_robots' => false,
            'disaster-intelligence.crawler.resolve_dns' => false,
            'disaster-intelligence.crawler.min_interval_ms' => 0,
        ]);

        Http::fake([
            'https://www.pref.chiba.lg.jp/document.pdf' => Http::response('dummy pdf body', 200, [
                'Content-Type' => 'application/pdf',
                'Last-Modified' => 'Wed, 21 Oct 2026 07:28:00 GMT',
            ]),
        ]);

        $this->artisan('disaster-intelligence:sync', [
            'disaster' => $disaster->id,
            '--url' => 'https://www.pref.chiba.lg.jp/document.pdf',
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

    public function test_sync_skips_unchanged_content_even_when_last_modified_changes(): void
    {
        $disaster = new Disaster();
        $disaster->name = '内容ハッシュ同期テスト災害';
        $disaster->status = true;
        $disaster->event_date = '2026-11-01';
        $disaster->save();

        Storage::fake('local');
        Cache::flush();
        Queue::fake();
        config([
            'disaster-intelligence.crawler.respect_robots' => false,
            'disaster-intelligence.crawler.resolve_dns' => false,
            'disaster-intelligence.crawler.min_interval_ms' => 0,
        ]);

        $headCalls = 0;
        $oldModified = 'Wed, 21 Oct 2026 07:28:00 GMT';
        $newModified = 'Thu, 22 Oct 2026 07:28:00 GMT';

        Http::fake([
            'https://www.pref.chiba.lg.jp/document.pdf' => function ($request) use (&$headCalls, $oldModified, $newModified) {
                if ($request->method() === 'HEAD') {
                    $headCalls++;

                    return Http::response('', 200, [
                        'Last-Modified' => $headCalls === 1 ? $oldModified : $newModified,
                    ]);
                }

                return Http::response('same pdf body', 200, [
                    'Content-Type' => 'application/pdf',
                    'Last-Modified' => $headCalls === 1 ? $oldModified : $newModified,
                ]);
            },
        ]);

        $this->artisan('disaster-intelligence:sync', [
            'disaster' => $disaster->id,
            '--url' => 'https://www.pref.chiba.lg.jp/document.pdf',
            '--type' => 'meeting_insights',
            '--org' => 'テスト県',
        ])->assertExitCode(0);

        $this->artisan('disaster-intelligence:sync', [
            'disaster' => $disaster->id,
            '--url' => 'https://www.pref.chiba.lg.jp/document.pdf',
            '--type' => 'meeting_insights',
            '--org' => 'テスト県',
        ])->assertExitCode(0);

        Queue::assertPushed(ParseDisasterSourceJob::class, 1);
    }

    public function test_sync_reparses_changed_content_only_once_per_day(): void
    {
        $disaster = new Disaster();
        $disaster->name = '再パース間隔テスト災害';
        $disaster->status = true;
        $disaster->event_date = '2026-11-01';
        $disaster->save();

        Storage::fake('local');
        Cache::flush();
        Queue::fake();
        config([
            'disaster-intelligence.crawler.respect_robots' => false,
            'disaster-intelligence.crawler.resolve_dns' => false,
            'disaster-intelligence.crawler.min_interval_ms' => 0,
            'disaster-intelligence.source_reparse_cooldown' => 86400,
        ]);

        $calls = 0;
        $getCalls = 0;
        $base = strtotime('2026-10-21 07:28:00 UTC');

        Http::fake([
            'https://www.pref.chiba.lg.jp/document.pdf' => function ($request) use (&$calls, &$getCalls, $base) {
                $calls++;
                $modified = gmdate('D, d M Y H:i:s \G\M\T', $base + $calls * 3600);

                if ($request->method() === 'HEAD') {
                    return Http::response('', 200, [
                        'Last-Modified' => $modified,
                    ]);
                }

                $getCalls++;

                return Http::response('pdf body ' . $getCalls, 200, [
                    'Content-Type' => 'application/pdf',
                    'Last-Modified' => $modified,
                ]);
            },
        ]);

        $args = [
            'disaster' => $disaster->id,
            '--url' => 'https://www.pref.chiba.lg.jp/document.pdf',
            '--type' => 'meeting_insights',
            '--org' => 'テスト県',
        ];

        $this->artisan('disaster-intelligence:sync', $args)->assertExitCode(0);
        Queue::assertPushed(ParseDisasterSourceJob::class, 1);

        $this->artisan('disaster-intelligence:sync', $args)->assertExitCode(0);
        Queue::assertPushed(ParseDisasterSourceJob::class, 1);

        $this->travel(25)->hours();

        $this->artisan('disaster-intelligence:sync', $args)->assertExitCode(0);
        Queue::assertPushed(ParseDisasterSourceJob::class, 2);
    }
}
