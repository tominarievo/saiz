<?php

namespace Tests\Feature;

use App\Disaster;
use App\Services\DisasterIntelligence\WasteSiteStatusVerifier;
use App\WasteSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WasteSiteStatusTest extends TestCase
{
    use RefreshDatabase;

    private string $wastePage = '';

    private function fakeWastePage(string $page, string $llmStatus): void
    {
        $this->wastePage = $page;

        Http::fake(function ($request) use ($llmStatus) {
            if (str_contains($request->url(), 'llm.test')) {
                return Http::response([
                    'choices' => [
                        ['message' => ['content' => json_encode([
                            'sites' => [[
                                'index' => 0,
                                'status' => $llmStatus,
                                'reason' => 'AI判定',
                                'evidence' => 'AI根拠',
                                'source' => 'S1',
                            ]],
                        ])]],
                    ],
                ], 200);
            }

            return Http::response('<html><body>' . $this->wastePage . '</body></html>', 200, ['Content-Type' => 'text/html']);
        });
    }

    private function verifySite(Disaster $disaster, array $overrides = []): array
    {
        return app(WasteSiteStatusVerifier::class)->verify($disaster->id, [array_merge([
            'name' => 'テスト仮置場',
            'status' => 'open',
            'acceptance_end_at' => null,
            'accepted_items' => ['木くず'],
        ], $overrides)]);
    }

    private function llmCalls(): int
    {
        return Http::recorded(fn ($request) => str_contains($request->url(), 'llm.test'))->count();
    }

    private function createDisaster(): Disaster
    {
        $disaster = new Disaster();
        $disaster->name = '仮置場状態テスト災害';
        $disaster->status = true;
        $disaster->event_date = '2026-11-01';
        $disaster->save();

        config([
            'disaster-intelligence.waste_status_sources.' . $disaster->id => [
                [
                    'name' => 'テスト受入情報',
                    'url' => 'https://www.city.chiba.jp/waste.html',
                    'source_org' => '千葉市',
                ],
            ],
            'disaster-intelligence.crawler.enforce_allowlist' => true,
            'disaster-intelligence.crawler.resolve_dns' => false,
            'disaster-intelligence.crawler.respect_robots' => false,
            'disaster-intelligence.llm.base_url' => 'https://llm.test/v1',
            'disaster-intelligence.llm.api_key' => 'test-key',
            'disaster-intelligence.llm.retries' => 1,
        ]);

        return $disaster;
    }

    private function createSite(Disaster $disaster, array $overrides = []): WasteSite
    {
        return WasteSite::create(array_merge([
            'disaster_id' => $disaster->id,
            'name' => 'テスト仮置場',
            'status' => 'open',
            'geometry' => ['type' => 'Point', 'coordinates' => [140.1, 35.6]],
            'entry_allowed' => true,
            'source_org' => '千葉市',
            'source_url' => 'https://www.city.chiba.jp/waste.html',
        ], $overrides));
    }

    public function test_ai_status_and_source_are_normalized(): void
    {
        $disaster = $this->createDisaster();
        $this->createSite($disaster);

        Http::fake([
            'https://www.city.chiba.jp/waste.html' => Http::response(
                '<html><body>テスト仮置場 受入状況：×（11/15）</body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
            'https://llm.test/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'sites' => [
                            [
                                'index' => 0,
                                'status' => 'closed',
                                'reason' => '受入終了',
                                'evidence' => 'テスト仮置場は受入を終了しました。',
                                'source' => 'S1',
                            ],
                        ],
                    ])]],
                ],
            ], 200),
        ]);

        $results = app(WasteSiteStatusVerifier::class)->verify($disaster->id, [[
            'name' => 'テスト仮置場',
            'status' => 'open',
            'acceptance_end_at' => null,
            'accepted_items' => ['木くず'],
        ]]);

        $this->assertSame('closed', $results[0]['status']);
        $this->assertSame('https://www.city.chiba.jp/waste.html', $results[0]['source_url']);
        $this->assertSame('テスト仮置場は受入を終了しました。', $results[0]['evidence']);
    }

    public function test_decisive_closure_is_resolved_without_ai(): void
    {
        $disaster = $this->createDisaster();
        $this->fakeWastePage('テスト仮置場は11月15日をもって受入を終了しました。', 'open');

        $results = $this->verifySite($disaster);

        $this->assertSame('closed', $results[0]['status']);
        $this->assertStringContainsString('受入を終了しました', $results[0]['evidence']);
        $this->assertSame(0, $this->llmCalls());
    }

    public function test_planned_closure_is_sent_to_ai(): void
    {
        $disaster = $this->createDisaster();
        $this->fakeWastePage('テスト仮置場は12月28日で受入を終了する予定です。', 'open');

        $results = $this->verifySite($disaster);

        $this->assertSame('open', $results[0]['status']);
        $this->assertSame(1, $this->llmCalls());
    }

    public function test_site_not_mentioned_is_unknown_without_ai(): void
    {
        $disaster = $this->createDisaster();
        $this->fakeWastePage('別の公園仮置場は受入中です。', 'open');

        $results = $this->verifySite($disaster);

        $this->assertSame('unknown', $results[0]['status']);
        $this->assertSame(0, $this->llmCalls());
    }

    public function test_closed_site_is_skipped(): void
    {
        $disaster = $this->createDisaster();
        $this->fakeWastePage('テスト仮置場 受入状況：○', 'open');

        $results = $this->verifySite($disaster, ['status' => 'closed']);

        $this->assertSame([], $results);
        $this->assertSame(0, $this->llmCalls());
    }

    public function test_unchanged_excerpt_reuses_previous_ai_result_even_if_status_changed(): void
    {
        $disaster = $this->createDisaster();
        $this->fakeWastePage('テスト仮置場 受入状況：○', 'unknown');

        $first = $this->verifySite($disaster);
        $second = $this->verifySite($disaster, ['status' => 'unknown']);

        $this->assertSame($first, $second);
        $this->assertSame(1, $this->llmCalls());

        $this->wastePage = 'テスト仮置場 受入状況：×（11/18）';
        $this->verifySite($disaster, ['status' => 'unknown']);

        $this->assertSame(2, $this->llmCalls());
    }

    public function test_keyword_fallback_marks_closed(): void
    {
        $disaster = $this->createDisaster();
        $this->createSite($disaster);

        Http::fake([
            'https://www.city.chiba.jp/waste.html' => Http::response(
                '<html><body>テスト仮置場の受付は終了しました。</body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
            'https://llm.test/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => '{"sites":[]}']],
                ],
            ], 200),
        ]);

        $results = app(WasteSiteStatusVerifier::class)->verify($disaster->id, [[
            'name' => 'テスト仮置場',
            'status' => 'open',
            'acceptance_end_at' => null,
            'accepted_items' => [],
        ]]);

        $this->assertSame('closed', $results[0]['status']);
        $this->assertStringContainsString('受付は終了', $results[0]['evidence']);
    }

    public function test_force_updates_closed_status(): void
    {
        $disaster = $this->createDisaster();
        $site = $this->createSite($disaster);

        $this->mock(WasteSiteStatusVerifier::class, function ($mock) {
            $mock->shouldReceive('verify')->once()->andReturn([
                0 => [
                    'status' => 'closed',
                    'reason' => '受入終了',
                    'evidence' => '受入を終了しました。',
                    'source_url' => 'https://www.city.chiba.jp/waste.html',
                ],
            ]);
        });

        $this->artisan('disaster-intelligence:verify-waste-sites', [
            'disaster' => $disaster->id,
            '--force' => true,
        ])->assertExitCode(0);

        $site->refresh();

        $this->assertSame('closed', $site->status);
        $this->assertNotNull($site->closed_at);
        $this->assertNotNull($site->last_verified_at);
        $this->assertSame('https://www.city.chiba.jp/waste.html', $site->status_source_url);
        $this->assertSame('受入を終了しました。', $site->status_evidence);
    }

    public function test_force_reopens_site(): void
    {
        $disaster = $this->createDisaster();
        $site = $this->createSite($disaster, [
            'status' => 'closed',
            'closed_at' => now()->subDay(),
        ]);

        $this->mock(WasteSiteStatusVerifier::class, function ($mock) {
            $mock->shouldReceive('verify')->once()->andReturn([
                0 => [
                    'status' => 'open',
                    'reason' => '受入中',
                    'evidence' => '受入を再開しました。',
                    'source_url' => 'https://www.city.chiba.jp/waste.html',
                ],
            ]);
        });

        $this->artisan('disaster-intelligence:verify-waste-sites', [
            'disaster' => $disaster->id,
            '--force' => true,
        ])->assertExitCode(0);

        $site->refresh();

        $this->assertSame('open', $site->status);
        $this->assertNull($site->closed_at);
    }

    public function test_dry_run_keeps_status_unchanged(): void
    {
        $disaster = $this->createDisaster();
        $site = $this->createSite($disaster);

        $this->mock(WasteSiteStatusVerifier::class, function ($mock) {
            $mock->shouldReceive('verify')->once()->andReturn([
                0 => [
                    'status' => 'closed',
                    'reason' => '受入終了',
                    'evidence' => '受入を終了しました。',
                    'source_url' => 'https://www.city.chiba.jp/waste.html',
                ],
            ]);
        });

        $this->artisan('disaster-intelligence:verify-waste-sites', [
            'disaster' => $disaster->id,
        ])->assertExitCode(0);

        $site->refresh();

        $this->assertSame('open', $site->status);
        $this->assertNull($site->last_verified_at);
    }

    public function test_closed_waste_site_is_shown_as_notice(): void
    {
        $disaster = $this->createDisaster();

        $this->createSite($disaster, [
            'name' => '受入終了のお知らせ',
            'status' => 'closed',
            'closed_at' => now(),
            'geometry' => null,
            'status_evidence' => '一般受入は10月31日で終了しました。',
            'status_source_url' => 'https://www.city.chiba.jp/waste.html',
        ]);

        $this->get('/disasters/' . $disaster->id)
            ->assertOk()
            ->assertSee('仮置場の受入終了')
            ->assertSee('受入終了のお知らせ')
            ->assertSee('一般受入は10月31日で終了しました。');
    }
}
