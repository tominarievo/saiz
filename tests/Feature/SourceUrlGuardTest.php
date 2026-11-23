<?php

namespace Tests\Feature;

use App\Services\DisasterIntelligence\SourceNotAllowedException;
use App\Services\DisasterIntelligence\SourceUrlGuard;
use Tests\TestCase;

class SourceUrlGuardTest extends TestCase
{
    private function guard(): SourceUrlGuard
    {
        return app(SourceUrlGuard::class);
    }

    public function test_allows_official_and_approved_hosts(): void
    {
        $guard = $this->guard();

        foreach ([
            'https://www.pref.chiba.lg.jp/road.pdf',
            'https://www.city.takaoka.toyama.jp/page.html',
            'https://www.mlit.go.jp/road.pdf',
            'https://jvoad.jp/news',
            'https://www.kvoad.com/',
            'https://cvoad.jp/',
            'https://www.fukushi-kumamoto.or.jp/',
        ] as $url) {
            $guard->assertAllowed($url, false);
            $this->addToAssertionCount(1);
        }
    }

    public function test_rejects_unapproved_hosts(): void
    {
        $guard = $this->guard();

        foreach ([
            'https://example.com/road.pdf',
            'https://example.test/road.pdf',
            'https://evil.example.jp/road.pdf',
        ] as $url) {
            $this->assertRejected($guard, $url);
        }
    }

    public function test_rejects_private_and_literal_ip_targets(): void
    {
        $guard = $this->guard();

        foreach ([
            'http://127.0.0.1/',
            'http://169.254.169.254/latest/meta-data/',
            'http://10.0.0.1/',
            'http://192.168.1.1/',
            'http://[::1]/',
        ] as $url) {
            $this->assertRejected($guard, $url);
        }
    }

    public function test_rejects_userinfo_and_nonstandard_ports(): void
    {
        $guard = $this->guard();

        foreach ([
            'https://user@www.pref.chiba.lg.jp/road.pdf',
            'https://www.pref.chiba.lg.jp:8443/road.pdf',
        ] as $url) {
            $this->assertRejected($guard, $url);
        }
    }

    public function test_http_can_be_disallowed(): void
    {
        config(['disaster-intelligence.crawler.allow_http' => false]);

        $this->assertRejected($this->guard(), 'http://www.pref.chiba.lg.jp/road.pdf');
    }

    public function test_public_ip_detection(): void
    {
        $guard = $this->guard();

        $this->assertTrue($guard->isPublicIp('8.8.8.8'));
        $this->assertTrue($guard->isPublicIp('2001:4860:4860::8888'));
        $this->assertFalse($guard->isPublicIp('127.0.0.1'));
        $this->assertFalse($guard->isPublicIp('127.0.0.53'));
        $this->assertFalse($guard->isPublicIp('10.0.0.1'));
        $this->assertFalse($guard->isPublicIp('172.16.0.1'));
        $this->assertFalse($guard->isPublicIp('192.168.1.1'));
        $this->assertFalse($guard->isPublicIp('169.254.169.254'));
        $this->assertFalse($guard->isPublicIp('100.64.0.1'));
        $this->assertFalse($guard->isPublicIp('0.0.0.0'));
        $this->assertFalse($guard->isPublicIp('::1'));
        $this->assertFalse($guard->isPublicIp('fc00::1'));
        $this->assertFalse($guard->isPublicIp('fe80::1'));
    }

    private function assertRejected(SourceUrlGuard $guard, string $url): void
    {
        try {
            $guard->assertAllowed($url, false);
        } catch (SourceNotAllowedException $error) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail('取得が拒否されるべきURLが許可されました: ' . $url);
    }
}
