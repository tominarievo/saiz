<?php

namespace App\Services\DisasterIntelligence;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * 取得ポリシーを適用したHTTPクライアント。
 */
class SourceHttpClient
{
    /**
     * @param SourceUrlGuard $guard
     * @param RobotsTxt $robots
     */
    public function __construct(
        private readonly SourceUrlGuard $guard,
        private readonly RobotsTxt $robots
    ) {
    }

    /**
     * HEADリクエストを送信する。
     *
     * @param string $url
     * @param array<string, string> $headers
     * @return Response
     */
    public function head(string $url, array $headers = []): Response
    {
        return $this->request('HEAD', $url, $headers);
    }

    /**
     * GETリクエストを送信する。
     *
     * @param string $url
     * @param array<string, string> $headers
     * @return Response
     */
    public function get(string $url, array $headers = []): Response
    {
        return $this->request('GET', $url, $headers);
    }

    /**
     * リダイレクトを検証しながらリクエストする。
     *
     * @param string $method
     * @param string $url
     * @param array<string, string> $headers
     * @return Response
     */
    private function request(string $method, string $url, array $headers = []): Response
    {
        $config = (array) config('disaster-intelligence.crawler', []);
        $maxRedirects = max(0, (int) ($config['max_redirects'] ?? 3));
        $timeout = max(1, (int) ($config['timeout'] ?? 60));
        $connectTimeout = max(1, (int) ($config['connect_timeout'] ?? 10));
        $maxBytes = max(1, (int) ($config['max_response_bytes'] ?? 20 * 1024 * 1024));
        $userAgent = (string) ($config['user_agent'] ?? 'SaizBot/1.0');
        $contact = trim((string) ($config['contact'] ?? ''));

        $defaultHeaders = [
            'User-Agent' => $userAgent,
            'Accept' => '*/*',
        ];

        if ($contact !== '') {
            $defaultHeaders['From'] = $contact;
        }

        $current = $url;

        for ($attempt = 0; $attempt <= $maxRedirects; $attempt++) {
            $this->guard->assertAllowed($current);

            if (!$this->robots->isAllowed($current)) {
                throw new SourceNotAllowedException('robots.txtで取得が禁止されています: ' . $current);
            }

            $this->throttle($current);

            $pending = Http::withHeaders(array_merge($defaultHeaders, $headers))
                ->timeout($timeout)
                ->connectTimeout($connectTimeout)
                ->withOptions(['allow_redirects' => false]);

            $response = $method === 'HEAD' ? $pending->head($current) : $pending->get($current);

            if ($response->redirect()) {
                $location = (string) $response->header('Location');

                if ($location === '') {
                    return $response;
                }

                $current = (string) UriResolver::resolve(new Uri($current), new Uri($location));
                continue;
            }

            if (strlen($response->body()) > $maxBytes) {
                throw new SourceNotAllowedException('取得先のレスポンスが大きすぎます: ' . $current);
            }

            $contentLength = (string) $response->header('Content-Length');

            if ($contentLength !== '' && (int) $contentLength > $maxBytes) {
                throw new SourceNotAllowedException('取得先のレスポンスが大きすぎます: ' . $current);
            }

            return $response;
        }

        throw new SourceNotAllowedException('リダイレクト回数が上限を超えました: ' . $url);
    }

    /**
     * 同一ホストへの連続アクセスを間隔調整する。
     *
     * @param string $url
     * @return void
     */
    private function throttle(string $url): void
    {
        $config = (array) config('disaster-intelligence.crawler', []);
        $minInterval = max(0, (int) ($config['min_interval_ms'] ?? 1000));

        if ($minInterval === 0) {
            return;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host === '') {
            return;
        }

        $cacheKey = 'source-http-last:' . $host;
        $last = (int) Cache::get($cacheKey, 0);
        $now = (int) floor(microtime(true) * 1000);
        $wait = $minInterval - ($now - $last);

        if ($last > 0 && $wait > 0) {
            usleep($wait * 1000);
        }

        Cache::put($cacheKey, (int) floor(microtime(true) * 1000), now()->addMinutes(10));
    }
}
