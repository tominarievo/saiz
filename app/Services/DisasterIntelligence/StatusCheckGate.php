<?php

namespace App\Services\DisasterIntelligence;

use Illuminate\Support\Facades\Cache;

/**
 * 状態確認（道路の解除・仮置場の受入終了）でAIへ送る前の絞り込みを行う。
 *
 * - 本文の正規化と候補名の検索
 * - 同一文内の確定表現によるAI不要の判定
 * - 候補名周辺の抜粋作成
 * - 抜粋の指紋による前回AI結果の再利用
 */
class StatusCheckGate
{
    private const CACHE_PREFIX = 'disaster-intel:status-check:';

    /**
     * 照合用に本文を正規化する。全角英数字・空白を半角化し、空白を除去する。
     */
    public function normalizeText(string $text): string
    {
        $text = mb_convert_kana($text, 'asKV');
        $text = preg_replace("/\r\n?/u", "\n", $text) ?? $text;
        $text = preg_replace('/[ \t\x{00A0}\x{3000}]+/u', '', $text) ?? $text;
        $text = preg_replace("/\n{2,}/u", "\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * 候補名などを照合キーに正規化する。
     */
    public function normalizeKey(string $value): string
    {
        $value = mb_convert_kana($value, 'asKV');
        $value = preg_replace('/（旧[^）]*）|\(旧[^)]*\)/u', '', $value) ?? $value;
        $value = preg_replace('/[\s\x{3000}・,，、]+/u', '', $value) ?? $value;

        return trim($value);
    }

    /**
     * キーの出現位置を返す。3文字未満のキーは誤一致が多いため使わない。
     *
     * @param array<int, string> $keys
     * @return array<int, array{position: int, length: int}>
     */
    public function mentions(string $text, array $keys): array
    {
        $found = [];

        foreach ($keys as $key) {
            $length = mb_strlen($key);

            if ($length < 3) {
                continue;
            }

            $offset = 0;

            while (($position = mb_strpos($text, $key, $offset)) !== false) {
                $found[] = ['position' => $position, 'length' => $length];
                $offset = $position + $length;
            }
        }

        usort($found, fn (array $a, array $b) => $a['position'] <=> $b['position']);

        return $found;
    }

    /**
     * 候補名の直後（同じ文・行の中）に確定表現があるかを調べる。
     *
     * 次の候補らしき語（$stopPattern）が出た位置で文を打ち切り、別候補の記載を拾わない。
     * 否定・予定表現（$negationPattern）を含む場合は確定しない。
     *
     * @param array<int, array{label: string, name: string, url: string, text: string}> $texts 正規化済み本文
     * @param array<int, string> $keys
     * @return array{evidence: string, source_url: string}|null
     */
    public function decisiveMatch(
        array $texts,
        array $keys,
        string $pattern,
        string $negationPattern,
        ?string $stopPattern = null
    ): ?array {
        foreach ($texts as $source) {
            $text = $source['text'];

            foreach ($this->mentions($text, $keys) as $mention) {
                $keyEnd = $mention['position'] + $mention['length'];
                $tail = mb_substr($text, $keyEnd, 120);

                if (preg_match('/[。\n]/u', $tail, $boundary, PREG_OFFSET_CAPTURE) === 1) {
                    $tail = mb_substr($tail, 0, mb_strlen(substr($tail, 0, $boundary[0][1])) + 1);
                }

                if ($stopPattern !== null
                    && preg_match($stopPattern, $tail, $stop, PREG_OFFSET_CAPTURE) === 1) {
                    $tail = substr($tail, 0, $stop[0][1]);
                }

                if (preg_match($pattern, $tail) !== 1 || preg_match($negationPattern, $tail) === 1) {
                    continue;
                }

                $head = mb_substr($text, max(0, $mention['position'] - 40), min(40, $mention['position']));
                $parts = preg_split('/[。\n]/u', $head) ?: [$head];
                $head = (string) end($parts);

                return [
                    'evidence' => trim($head . mb_substr($text, $mention['position'], $mention['length']) . $tail),
                    'source_url' => $source['url'],
                ];
            }
        }

        return null;
    }

    /**
     * 候補名周辺の範囲を情報源ごとに返す。重なる範囲は結合する。
     *
     * @param array<int, array{label: string, name: string, url: string, text: string}> $texts 正規化済み本文
     * @param array<int, string> $keys
     * @return array<string, array<int, array{0: int, 1: int}>> ラベルごとの [開始, 終了] 範囲
     */
    public function windows(array $texts, array $keys): array
    {
        $before = max(0, (int) config('disaster-intelligence.status_check.excerpt_before', 150));
        $after = max(0, (int) config('disaster-intelligence.status_check.excerpt_after', 400));
        $windows = [];

        foreach ($texts as $source) {
            $length = mb_strlen($source['text']);
            $ranges = [];

            foreach ($this->mentions($source['text'], $keys) as $mention) {
                $ranges[] = [
                    max(0, $mention['position'] - $before),
                    min($length, $mention['position'] + $mention['length'] + $after),
                ];
            }

            if ($ranges !== []) {
                $windows[$source['label']] = $this->mergeRanges($ranges);
            }
        }

        return $windows;
    }

    /**
     * 範囲を本文の抜粋に変換する。
     *
     * @param array<int, array{label: string, name: string, url: string, text: string}> $texts
     * @param array<string, array<int, array{0: int, 1: int}>> $windows
     */
    public function excerptText(array $texts, array $windows): string
    {
        $parts = [];

        foreach ($texts as $source) {
            if (!isset($windows[$source['label']])) {
                continue;
            }

            $parts[] = $source['label'] . ':' . implode(' … ', array_map(
                fn (array $range) => mb_substr($source['text'], $range[0], $range[1] - $range[0]),
                $windows[$source['label']]
            ));
        }

        return implode("\n", $parts);
    }

    /**
     * 複数候補の範囲を結合し、AIへ送る本文を作る。各情報源の冒頭（表題・更新日）も付ける。
     *
     * @param array<int, array{label: string, name: string, url: string, text: string}> $texts
     * @param array<int, array<string, array<int, array{0: int, 1: int}>>> $candidateWindows
     */
    public function promptText(array $texts, array $candidateWindows): string
    {
        $merged = [];

        foreach ($candidateWindows as $windows) {
            foreach ($windows as $label => $ranges) {
                $merged[$label] = array_merge($merged[$label] ?? [], $ranges);
            }
        }

        $limit = max(1000, (int) config('disaster-intelligence.status_check.max_prompt_chars', 40000));
        $sections = [];

        foreach ($texts as $source) {
            if (!isset($merged[$source['label']])) {
                continue;
            }

            $excerpts = array_map(
                fn (array $range) => mb_substr($source['text'], $range[0], $range[1] - $range[0]),
                $this->mergeRanges($merged[$source['label']])
            );

            $sections[] = '### [' . $source['label'] . '] ' . $source['name'] . "\n"
                . '冒頭: ' . mb_substr($source['text'], 0, 200) . "\n"
                . '抜粋: ' . implode("\n…\n", $excerpts);
        }

        return mb_substr(implode("\n\n", $sections), 0, $limit);
    }

    public function fingerprint(string $candidate, string $excerpt): string
    {
        return hash('sha256', $candidate . "\n---\n" . $excerpt);
    }

    /**
     * 指紋が一致する前回のAI判定結果を返す。
     *
     * @return array<string, mixed>|null
     */
    public function cached(string $kind, int $disasterId, string $candidate, string $fingerprint): ?array
    {
        $entry = Cache::get($this->cacheKey($kind, $disasterId, $candidate));

        if (!is_array($entry)
            || !is_string($entry['fingerprint'] ?? null)
            || !hash_equals($entry['fingerprint'], $fingerprint)
            || !is_array($entry['result'] ?? null)) {
            return null;
        }

        return $entry['result'];
    }

    /**
     * AI判定結果を指紋とともに保存する。
     *
     * @param array<string, mixed> $result
     */
    public function remember(string $kind, int $disasterId, string $candidate, string $fingerprint, array $result): void
    {
        $ttl = max(60, (int) config('disaster-intelligence.status_check.cache_ttl', 604800));

        Cache::put(
            $this->cacheKey($kind, $disasterId, $candidate),
            ['fingerprint' => $fingerprint, 'result' => $result],
            $ttl
        );
    }

    private function cacheKey(string $kind, int $disasterId, string $candidate): string
    {
        return self::CACHE_PREFIX . $kind . ':' . $disasterId . ':' . sha1($candidate);
    }

    /**
     * @param array<int, array{0: int, 1: int}> $ranges
     * @return array<int, array{0: int, 1: int}>
     */
    private function mergeRanges(array $ranges): array
    {
        usort($ranges, fn (array $a, array $b) => $a[0] <=> $b[0]);
        $merged = [];

        foreach ($ranges as $range) {
            $last = count($merged) - 1;

            if ($last >= 0 && $range[0] <= $merged[$last][1]) {
                $merged[$last][1] = max($merged[$last][1], $range[1]);
                continue;
            }

            $merged[] = $range;
        }

        return $merged;
    }
}
