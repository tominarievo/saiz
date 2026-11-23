<?php

namespace App\Services\DisasterIntelligence;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 取得先のrobots.txtを確認する。
 */
class RobotsTxt
{
    /**
     * @param SourceUrlGuard $guard
     */
    public function __construct(private readonly SourceUrlGuard $guard)
    {
    }

    /**
     * URLがrobots.txtで許可されているか判定する。
     *
     * @param string $url
     * @return bool
     */
    public function isAllowed(string $url): bool
    {
        $config = (array) config('disaster-intelligence.crawler', []);

        if (!(bool) ($config['respect_robots'] ?? true)) {
            return true;
        }

        $parts = parse_url($url);

        if (!is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme === '' || $host === '') {
            return false;
        }

        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        $robotsUrl = $scheme . '://' . $host . $port . '/robots.txt';
        $content = $this->robotsContent($robotsUrl);

        if ($content === false) {
            return false;
        }

        if ($content === null || trim($content) === '') {
            return true;
        }

        $path = (string) ($parts['path'] ?? '/');

        if (isset($parts['query']) && $parts['query'] !== '') {
            $path .= '?' . $parts['query'];
        }

        return $this->pathAllowed($content, $path, (string) ($config['user_agent'] ?? ''));
    }

    /**
     * robots.txtの中身からパスの可否を判定する。
     *
     * @param string $content
     * @param string $path
     * @param string $userAgent
     * @return bool
     */
    public function pathAllowed(string $content, string $path, string $userAgent): bool
    {
        $groups = $this->parseGroups($content);
        $product = strtolower(trim(strtok($userAgent, '/') ?: $userAgent));
        $specificRules = [];
        $wildcardRules = [];
        $hasSpecific = false;
        $hasWildcard = false;

        foreach ($groups as $group) {
            $specificMatch = false;
            $wildcardMatch = false;

            foreach ($group['agents'] as $agent) {
                $agent = strtolower(trim($agent));

                if ($agent === '*') {
                    $wildcardMatch = true;
                    continue;
                }

                if ($agent !== '' && $product !== '' && str_contains($product, $agent)) {
                    $specificMatch = true;
                }
            }

            if ($specificMatch) {
                $hasSpecific = true;
                $specificRules = array_merge($specificRules, $group['rules']);
            } elseif ($wildcardMatch) {
                $hasWildcard = true;
                $wildcardRules = array_merge($wildcardRules, $group['rules']);
            }
        }

        if (!$hasSpecific && !$hasWildcard) {
            return true;
        }

        $rules = $hasSpecific ? $specificRules : $wildcardRules;
        $best = null;

        foreach ($rules as $rule) {
            if ($rule['path'] === '') {
                continue;
            }

            if (!$this->pathMatches($rule['path'], $path)) {
                continue;
            }

            $length = strlen($rule['path']);

            if ($best === null
                || $length > $best['length']
                || ($length === $best['length'] && $rule['allow'])) {
                $best = ['length' => $length, 'allow' => $rule['allow']];
            }
        }

        return $best === null ? true : $best['allow'];
    }

    /**
     * robots.txtを取得する。拒否の場合はfalse、取得失敗時はnull。
     *
     * @param string $robotsUrl
     * @return string|false|null
     */
    private function robotsContent(string $robotsUrl): string|false|null
    {
        $cacheKey = 'robots-txt:' . sha1($robotsUrl);
        $cached = Cache::get($cacheKey, '__missing__');

        if ($cached !== '__missing__') {
            return $cached;
        }

        $config = (array) config('disaster-intelligence.crawler', []);
        $userAgent = (string) ($config['user_agent'] ?? 'SaizBot/1.0');
        $maxRedirects = max(0, (int) ($config['max_redirects'] ?? 3));
        $url = $robotsUrl;

        try {
            for ($attempt = 0; $attempt <= $maxRedirects; $attempt++) {
                $this->guard->assertAllowed($url);

                $response = Http::withHeaders(['User-Agent' => $userAgent])
                    ->timeout(10)
                    ->withOptions(['allow_redirects' => false])
                    ->get($url);

                if (!$response->redirect()) {
                    break;
                }

                $location = (string) $response->header('Location');

                if ($location === '') {
                    break;
                }

                $url = (string) UriResolver::resolve(new Uri($url), new Uri($location));
            }

            if (!isset($response)) {
                return null;
            }

            if ($response->successful()) {
                $content = $response->body();
                Cache::put($cacheKey, $content, now()->addDay());

                return $content;
            }

            if (in_array($response->status(), [401, 403], true)) {
                Cache::put($cacheKey, false, now()->addHours(6));

                return false;
            }

            if ($response->status() === 404) {
                Cache::put($cacheKey, '', now()->addDay());

                return '';
            }

            Log::warning('robots.txtの取得に失敗しました。', [
                'url' => $robotsUrl,
                'status' => $response->status(),
            ]);

            return null;
        } catch (Throwable $error) {
            Log::warning('robots.txtの確認をスキップしました。', [
                'url' => $robotsUrl,
                'message' => $error->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * robots.txtをグループ単位に解析する。
     *
     * @param string $content
     * @return array<int, array{agents: array<int, string>, rules: array<int, array{allow: bool, path: string}>}>
     */
    private function parseGroups(string $content): array
    {
        $groups = [];
        $current = null;

        foreach (preg_split('/\r\n|\n|\r/', $content) ?: [] as $line) {
            $line = trim((string) preg_replace('/#.*/', '', $line));

            if ($line === '') {
                continue;
            }

            $parts = explode(':', $line, 2);

            if (count($parts) !== 2) {
                continue;
            }

            $key = strtolower(trim($parts[0]));
            $value = trim($parts[1]);

            if ($key === 'user-agent') {
                if ($current !== null && $current['rules'] !== []) {
                    $groups[] = $current;
                    $current = null;
                }

                if ($current === null) {
                    $current = ['agents' => [], 'rules' => []];
                }

                $current['agents'][] = $value;
                continue;
            }

            if ($current === null) {
                continue;
            }

            if (in_array($key, ['allow', 'disallow'], true)) {
                $current['rules'][] = [
                    'allow' => $key === 'allow',
                    'path' => $value,
                ];
            }
        }

        if ($current !== null) {
            $groups[] = $current;
        }

        return $groups;
    }

    /**
     * robots.txtのパスパターンとURLパスを照合する。
     *
     * @param string $pattern
     * @param string $path
     * @return bool
     */
    private function pathMatches(string $pattern, string $path): bool
    {
        if ($pattern === '') {
            return true;
        }

        $anchored = str_ends_with($pattern, '$');

        if ($anchored) {
            $pattern = substr($pattern, 0, -1);
        }

        $regex = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . ($anchored ? '$' : '') . '#';

        return @preg_match($regex, $path) === 1;
    }
}
