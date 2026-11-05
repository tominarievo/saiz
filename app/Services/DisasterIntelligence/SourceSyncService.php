<?php

namespace App\Services\DisasterIntelligence;

use App\Jobs\ParseDisasterSourceJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * 自治体サイトの更新検知と取得・ジョブ投入を行う。
 */
class SourceSyncService
{
    /**
     * 更新があればファイルを保存してジョブを投入する。
     *
     * @param int $disasterId
     * @param array<string, mixed> $source
     * @param bool $force
     * @return string|null 保存先パス。更新なしの場合はnull。
     */
    public function sync(int $disasterId, array $source, bool $force = false): ?string
    {
        $url = trim((string) ($source['url'] ?? ''));

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('同期URLが不正です: ' . $url);
        }

        $type = (string) ($source['type'] ?? 'roads');
        $cacheKey = 'disaster-source-modified:' . $disasterId . ':' . sha1($url);
        $lastModified = Cache::get($cacheKey);
        $headers = [];

        if (!$force && $lastModified) {
            $headers['If-Modified-Since'] = $lastModified;
        }

        $head = Http::withHeaders($headers)
            ->timeout(30)
            ->head($url);

        if ($head->status() === 304) {
            return null;
        }

        if (!$head->successful() && !in_array($head->status(), [405, 501], true)) {
            throw new RuntimeException('更新確認に失敗しました: HTTP ' . $head->status() . ' ' . $url);
        }

        $remoteModified = $head->header('Last-Modified');

        if (!$force && $remoteModified && $lastModified && strtotime($remoteModified) <= strtotime($lastModified)) {
            return null;
        }

        $response = Http::withHeaders($headers)
            ->timeout(120)
            ->get($url);

        if ($response->status() === 304) {
            return null;
        }

        if (!$response->successful()) {
            throw new RuntimeException('資料の取得に失敗しました: HTTP ' . $response->status() . ' ' . $url);
        }

        $extension = $this->extension($url, $response->header('Content-Type'));
        $path = trim((string) config('disaster-intelligence.source_root', 'disaster-sources'), '/')
            . '/' . $disasterId . '/' . sha1($url) . '.' . $extension;

        Storage::disk(config('disaster-intelligence.storage_disk', 'local'))
            ->put($path, $response->body());

        $lastModified = $remoteModified ?: $response->header('Last-Modified') ?: now()->toRfc7231String();
        Cache::forever($cacheKey, $lastModified);

        $publishedAt = $source['published_at'] ?? null;

        if (!$publishedAt && $remoteModified) {
            $publishedAt = date(DATE_ATOM, strtotime($remoteModified));
        }

        $credibility = $source['credibility_rank'] ?? $this->domainRank($url);
        $credibilityReason = $source['credibility_reason'] ?? 'URLドメインから判定';

        ParseDisasterSourceJob::dispatch(
            $disasterId,
            $type,
            $path,
            $url,
            (string) ($source['source_org'] ?? '不明'),
            $publishedAt ? (string) $publishedAt : null,
            isset($source['source_page']) ? (int) $source['source_page'] : null,
            $credibility,
            $credibilityReason
        );

        return $path;
    }

    /**
     * 公式ドメインを基準に信頼度を判定する。
     */
    private function domainRank(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host) ?? $host;

        if (str_ends_with($host, '.go.jp') || str_ends_with($host, '.lg.jp')) {
            return 'A';
        }

        if (preg_match('/^(city|town|vill|pref)\./', $host) && str_ends_with($host, '.jp')) {
            return 'A';
        }

        if (str_ends_with($host, '.or.jp') || in_array($host, ['jvoad.jp', 'kvoad.com'], true)) {
            return 'B';
        }

        return 'C';
    }

    /**
     * URLとContent-Typeから拡張子を決める。
     */
    private function extension(string $url, ?string $contentType): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['pdf', 'txt', 'html', 'htm', 'md', 'csv'], true)) {
            return $extension;
        }

        $contentType = strtolower((string) $contentType);

        return match (true) {
            str_contains($contentType, 'pdf') => 'pdf',
            str_contains($contentType, 'html') => 'html',
            str_contains($contentType, 'text/plain') => 'txt',
            default => 'bin',
        };
    }
}
