<?php

namespace Tests\Feature;

use App\Services\DisasterIntelligence\SourceHttpClient;
use App\Services\DisasterIntelligence\SourceNotAllowedException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SourceHttpClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'disaster-intelligence.crawler.respect_robots' => true,
            'disaster-intelligence.crawler.resolve_dns' => false,
            'disaster-intelligence.crawler.min_interval_ms' => 0,
            'disaster-intelligence.crawler.user_agent' => 'TestBot/1.0',
            'disaster-intelligence.crawler.max_response_bytes' => 1024,
        ]);
    }

    private function client(): SourceHttpClient
    {
        return app(SourceHttpClient::class);
    }

    public function test_fetches_allowed_url_with_user_agent_and_robots_check(): void
    {
        Http::fake([
            'https://www.pref.chiba.lg.jp/robots.txt' => Http::response('', 404),
            'https://www.pref.chiba.lg.jp/document.pdf' => Http::response('dummy pdf body', 200, [
                'Content-Type' => 'application/pdf',
            ]),
        ]);

        $response = $this->client()->get('https://www.pref.chiba.lg.jp/document.pdf');

        $this->assertSame('dummy pdf body', $response->body());
        Http::assertSent(function ($request) {
            return $request->url() === 'https://www.pref.chiba.lg.jp/document.pdf'
                && $request->hasHeader('User-Agent', 'TestBot/1.0');
        });
        Http::assertSent(fn ($request) => $request->url() === 'https://www.pref.chiba.lg.jp/robots.txt');
    }

    public function test_robots_disallow_blocks_fetch(): void
    {
        Http::fake([
            'https://www.pref.chiba.lg.jp/robots.txt' => Http::response("User-agent: *\nDisallow: /private", 200),
        ]);

        $this->expectException(SourceNotAllowedException::class);

        try {
            $this->client()->get('https://www.pref.chiba.lg.jp/private/document.pdf');
        } finally {
            Http::assertNotSent(fn ($request) => $request->url() === 'https://www.pref.chiba.lg.jp/private/document.pdf');
        }
    }

    public function test_empty_disallow_allows_fetch(): void
    {
        Http::fake([
            'https://www.pref.chiba.lg.jp/robots.txt' => Http::response("User-agent: *\nDisallow:", 200),
            'https://www.pref.chiba.lg.jp/public.pdf' => Http::response('public body', 200),
        ]);

        $response = $this->client()->get('https://www.pref.chiba.lg.jp/public.pdf');

        $this->assertSame('public body', $response->body());
    }

    public function test_specific_user_agent_group_overrides_wildcard(): void
    {
        Http::fake([
            'https://www.pref.chiba.lg.jp/robots.txt' => Http::response(
                "User-agent: *\nDisallow: /private\n\nUser-agent: TestBot\nAllow: /private",
                200
            ),
            'https://www.pref.chiba.lg.jp/private/document.pdf' => Http::response('allowed body', 200),
        ]);

        $response = $this->client()->get('https://www.pref.chiba.lg.jp/private/document.pdf');

        $this->assertSame('allowed body', $response->body());
    }

    public function test_redirect_to_private_ip_is_blocked(): void
    {
        Http::fake([
            'https://www.pref.chiba.lg.jp/robots.txt' => Http::response('', 404),
            'https://www.pref.chiba.lg.jp/redirect' => Http::response('', 302, [
                'Location' => 'http://127.0.0.1/secret',
            ]),
        ]);

        $this->expectException(SourceNotAllowedException::class);

        try {
            $this->client()->get('https://www.pref.chiba.lg.jp/redirect');
        } finally {
            Http::assertNotSent(fn ($request) => str_contains($request->url(), '127.0.0.1'));
        }
    }

    public function test_unapproved_host_is_blocked_before_request(): void
    {
        Http::fake();

        $this->expectException(SourceNotAllowedException::class);

        try {
            $this->client()->get('https://example.com/document.pdf');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_oversized_response_is_rejected(): void
    {
        Http::fake([
            'https://www.pref.chiba.lg.jp/robots.txt' => Http::response('', 404),
            'https://www.pref.chiba.lg.jp/large.pdf' => Http::response(str_repeat('a', 2048), 200),
        ]);

        $this->expectException(SourceNotAllowedException::class);

        $this->client()->get('https://www.pref.chiba.lg.jp/large.pdf');
    }
}
