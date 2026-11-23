<?php

namespace App\Services\DisasterIntelligence;

use Illuminate\Support\Facades\Cache;

/**
 * 取得先URLのスキーム・ホスト・解決先IPを検証する。
 */
class SourceUrlGuard
{
    /**
     * URLが取得ポリシーを満たすことを検証する。
     *
     * @param string $url
     * @param bool|null $resolveDns nullの場合は設定値に従う
     * @return void
     */
    public function assertAllowed(string $url, ?bool $resolveDns = null): void
    {
        $config = (array) config('disaster-intelligence.crawler', []);
        $parts = parse_url($url);

        if (!is_array($parts)) {
            throw new SourceNotAllowedException('URLを解析できません: ' . $url);
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new SourceNotAllowedException('http/https以外のURLは取得できません: ' . $url);
        }

        if ($scheme === 'http' && !(bool) ($config['allow_http'] ?? true)) {
            throw new SourceNotAllowedException('httpのURLは取得できません: ' . $url);
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new SourceNotAllowedException('ユーザー情報を含むURLは取得できません: ' . $url);
        }

        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '') {
            throw new SourceNotAllowedException('ホストがありません: ' . $url);
        }

        $host = rtrim($host, '.');

        if (filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) !== false) {
            throw new SourceNotAllowedException('IPアドレス直指定のURLは取得できません: ' . $url);
        }

        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        $allowedPorts = (array) ($config['allowed_ports'] ?? [80, 443]);

        if ($port !== null && !in_array($port, $allowedPorts, true)) {
            throw new SourceNotAllowedException('許可されていないポートです: ' . $url);
        }

        if (!(bool) ($config['enforce_allowlist'] ?? true)) {
            return;
        }

        if (!$this->isHostAllowed($host, $config)) {
            throw new SourceNotAllowedException('取得先として許可されていないホストです: ' . $host);
        }

        $shouldResolve = $resolveDns ?? (bool) ($config['resolve_dns'] ?? true);

        if (!$shouldResolve) {
            return;
        }

        foreach ($this->resolveHost($host) as $ip) {
            if (!$this->isPublicIp($ip)) {
                throw new SourceNotAllowedException('取得先が内部ネットワークを指しています: ' . $host . ' (' . $ip . ')');
            }
        }
    }

    /**
     * ホストが許可リストに含まれるか判定する。
     *
     * @param string $host
     * @param array<string, mixed>|null $config
     * @return bool
     */
    public function isHostAllowed(string $host, ?array $config = null): bool
    {
        $config ??= (array) config('disaster-intelligence.crawler', []);
        $normalized = $this->normalizeHost($host);

        foreach ((array) ($config['allowed_hosts'] ?? []) as $allowed) {
            if ($normalized === $this->normalizeHost((string) $allowed)) {
                return true;
            }
        }

        foreach ((array) ($config['allowed_host_suffixes'] ?? []) as $suffix) {
            $suffix = strtolower((string) $suffix);

            if ($suffix !== '' && str_ends_with($normalized, $suffix)) {
                return true;
            }
        }

        foreach ((array) ($config['allowed_host_patterns'] ?? []) as $pattern) {
            if (@preg_match((string) $pattern, $normalized) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * ホスト名を比較用に正規化する。
     *
     * @param string $host
     * @return string
     */
    public function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        $host = rtrim($host, '.');

        return preg_replace('/^www\./', '', $host) ?? $host;
    }

    /**
     * グローバルIPか判定する。
     *
     * @param string $ip
     * @return bool
     */
    public function isPublicIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        if (str_contains($ip, ':')) {
            $packed = @inet_pton($ip);

            if ($packed === false) {
                return false;
            }

            if ($packed === inet_pton('::1')) {
                return false;
            }

            if ((ord($packed[0]) & 0xfe) === 0xfc) {
                return false;
            }

            if (ord($packed[0]) === 0xfe && (ord($packed[1]) & 0xc0) === 0x80) {
                return false;
            }

            if (str_starts_with($ip, '::ffff:')) {
                return $this->isPublicIp(substr($ip, 7));
            }

            return true;
        }

        $long = ip2long($ip);

        if ($long === false) {
            return false;
        }

        if (($long & 0xFFC00000) === (ip2long('100.64.0.0') & 0xFFC00000)) {
            return false;
        }

        if (($long & 0xFF000000) === 0) {
            return false;
        }

        if (($long & 0xFFFF0000) === ip2long('169.254.0.0')) {
            return false;
        }

        if (($long & 0xF0000000) === 0xE0000000) {
            return false;
        }

        if (($long & 0xF0000000) === 0xF0000000) {
            return false;
        }

        return true;
    }

    /**
     * ホストの解決先IPを返す。
     *
     * @param string $host
     * @return array<int, string>
     */
    private function resolveHost(string $host): array
    {
        $cacheKey = 'source-dns:' . $host;
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $ips = [];
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);

        if (is_array($records)) {
            foreach ($records as $record) {
                if (isset($record['ip']) && is_string($record['ip'])) {
                    $ips[] = $record['ip'];
                }

                if (isset($record['ipv6']) && is_string($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        if ($ips === []) {
            $ipv4 = @gethostbynamel($host);

            if (is_array($ipv4)) {
                $ips = $ipv4;
            }
        }

        $ips = array_values(array_unique(array_filter($ips, 'is_string')));

        if ($ips === []) {
            throw new SourceNotAllowedException('取得先ホストを解決できません: ' . $host);
        }

        Cache::put($cacheKey, $ips, now()->addMinutes(10));

        return $ips;
    }
}
