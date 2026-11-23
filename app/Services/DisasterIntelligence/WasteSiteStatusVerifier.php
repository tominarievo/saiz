<?php

namespace App\Services\DisasterIntelligence;

use App\DisasterIntelligenceSource;
use App\WasteSite;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * 仮置場の受入状況が最新情報と一致しているか確認する。
 */
class WasteSiteStatusVerifier
{
    private const STATUSES = ['open', 'closed', 'unknown'];

    /** 仮置場名の直後にあれば受入終了と確定できる完了形の表現 */
    private const CLOSED_PATTERN = '/(受入|受け入れ|受付|受け付け|搬入).{0,15}終了(しました|いたしました|しております|しています)|閉鎖(しました|いたしました|しております|しています)/u';

    /** 予定・一時閉鎖・再開など、確定できない表現 */
    private const NEGATION_PATTERN = '/予定|見込|見通し|一時|臨時|延長|再開|休止|まで受/u';

    /** 同じ行に別の仮置場が続く場合はそこで打ち切る */
    private const NEXT_SITE_PATTERN = '/仮置場|仮置き場|集積所|集積場|ストックヤード/u';

    /**
     * @param PromptRepository $prompts
     * @param LlmJsonClient $llm
     * @param SourceHttpClient $http
     * @param StatusCheckGate $gate
     */
    public function __construct(
        private readonly PromptRepository $prompts,
        private readonly LlmJsonClient $llm,
        private readonly SourceHttpClient $http,
        private readonly StatusCheckGate $gate
    ) {
    }

    /**
     * 候補ごとの最新状態を返す。
     *
     * AIへ送るのは、受入終了済みでなく、確定表現がなく、情報源に記載があり、
     * 前回AI判定時から候補周辺の記載が変わった候補だけに限る。
     *
     * @param int $disasterId
     * @param array<int, array<string, mixed>> $sites
     * @param bool $fresh 前回のAI判定結果を再利用しない
     * @return array<int, array{status: string, reason: string|null, evidence: string|null, source_url: string|null}>
     */
    public function verify(int $disasterId, array $sites, bool $fresh = false): array
    {
        if ($sites === []) {
            return [];
        }

        $sources = $this->sourcesFor($disasterId);

        if ($sources === []) {
            return [];
        }

        $texts = [];

        foreach ($sources as $source) {
            try {
                $text = $this->gate->normalizeText($this->fetchText($source['url']));

                if ($text !== '') {
                    $label = 'S' . (count($texts) + 1);
                    $texts[] = [
                        'label' => $label,
                        'name' => $source['name'],
                        'url' => $source['url'],
                        'text' => $text,
                    ];
                }
            } catch (Throwable $error) {
                Log::warning('仮置場の受入状況を取得できませんでした。', [
                    'url' => $source['url'],
                    'message' => $error->getMessage(),
                ]);
            }
        }

        if ($texts === []) {
            return [];
        }

        $stats = [
            'disaster_id' => $disasterId,
            'candidates' => count($sites),
            'resolved' => 0,
            'keyword' => 0,
            'no_mention' => 0,
            'cached' => 0,
            'ai_candidates' => 0,
            'ai_calls' => 0,
        ];
        $statuses = [];
        $pending = [];

        foreach ($sites as $index => $site) {
            if (($site['status'] ?? null) === 'closed') {
                $stats['resolved']++;
                continue;
            }

            $keys = [$this->siteKey((string) ($site['name'] ?? ''))];
            $decisive = $this->gate->decisiveMatch(
                $texts,
                $keys,
                self::CLOSED_PATTERN,
                self::NEGATION_PATTERN,
                self::NEXT_SITE_PATTERN
            );

            if ($decisive !== null) {
                $statuses[$index] = [
                    'status' => 'closed',
                    'reason' => '受入終了の記載',
                    'evidence' => mb_substr($decisive['evidence'], 0, 1000),
                    'source_url' => $decisive['source_url'],
                ];
                $stats['keyword']++;
                continue;
            }

            $windows = $this->gate->windows($texts, $keys);

            if ($windows === []) {
                $statuses[$index] = [
                    'status' => 'unknown',
                    'reason' => '最新情報に該当する仮置場の記載がありません',
                    'evidence' => null,
                    'source_url' => null,
                ];
                $stats['no_mention']++;
                continue;
            }

            // 現在の状態は判定のたびに変わるため、変化検知の対象から外す。
            $identity = $this->candidateIdentity($site);
            $fingerprint = $this->gate->fingerprint($identity, $this->gate->excerptText($texts, $windows));
            $cached = $fresh ? null : $this->gate->cached('waste_sites', $disasterId, $identity, $fingerprint);

            if ($cached !== null) {
                $statuses[$index] = $cached;
                $stats['cached']++;
                continue;
            }

            $pending[$index] = [
                'candidate' => $this->candidateLine($site),
                'identity' => $identity,
                'windows' => $windows,
                'fingerprint' => $fingerprint,
            ];
        }

        if ($pending !== []) {
            $stats['ai_candidates'] = count($pending);
            $stats['ai_calls'] = 1;
            $statuses += $this->verifyWithAi($disasterId, $sites, $pending, $texts);
        }

        Log::info('仮置場の受入確認ゲート', $stats);
        ksort($statuses);

        return $statuses;
    }

    /**
     * 絞り込んだ候補だけをAIで判定する。AIが判定しなかった候補はキーワードで補う。
     *
     * @param array<int, array<string, mixed>> $sites
     * @param array<int, array{candidate: string, identity: string, windows: array<string, array<int, array{0: int, 1: int}>>, fingerprint: string}> $pending
     * @param array<int, array{label: string, name: string, url: string, text: string}> $texts
     * @return array<int, array{status: string, reason: string|null, evidence: string|null, source_url: string|null}>
     */
    private function verifyWithAi(int $disasterId, array $sites, array $pending, array $texts): array
    {
        $candidates = [];

        foreach ($pending as $index => $item) {
            $candidates[] = $index . '|' . $item['candidate'];
        }

        $prompt = $this->prompts->render('waste_status_check', [
            'CANDIDATES' => implode("\n", $candidates),
            'STATUS_TEXT' => $this->gate->promptText($texts, array_column($pending, 'windows')),
        ]);

        $targets = array_intersect_key($sites, $pending);
        try {
            $result = $this->llm->completeJson($prompt);
            $aiStatuses = $this->normalizeStatuses($result['sites'] ?? [], $targets, $texts);
        } catch (Throwable $error) {
            Log::warning('仮置場の受入状況AIを実行できませんでした。', [
                'disaster_id' => $disasterId,
                'message' => $error->getMessage(),
            ]);

            // 従来どおり、AI失敗時は状態を変更しない。
            return [];
        }

        $keywordMatches = $this->keywordStatuses($targets, $texts);
        $statuses = [];

        foreach ($pending as $index => $item) {
            $status = $aiStatuses[$index] ?? null;

            if ($status === null && isset($keywordMatches[$index])) {
                $match = $keywordMatches[$index];
                $status = [
                    'status' => $match['status'],
                    'reason' => $match['status'] === 'closed' ? '受入終了の記載' : '受入継続の記載',
                    'evidence' => $match['evidence'],
                    'source_url' => $match['source_url'],
                ];
            }

            $status ??= [
                'status' => 'unknown',
                'reason' => 'AIが判定を返しませんでした',
                'evidence' => null,
                'source_url' => null,
            ];

            $statuses[$index] = $status;
            $this->gate->remember('waste_sites', $disasterId, $item['identity'], $item['fingerprint'], $status);
        }

        return $statuses;
    }

    /**
     * 変化検知に使う候補の識別子。
     *
     * @param array<string, mixed> $site
     */
    private function candidateIdentity(array $site): string
    {
        return ($site['name'] ?? '') . '|'
            . ($site['acceptance_end_at'] ?? '') . '|'
            . implode('、', (array) ($site['accepted_items'] ?? []));
    }

    /**
     * AIへ渡す候補の内容を1行にする。
     *
     * @param array<string, mixed> $site
     */
    private function candidateLine(array $site): string
    {
        return ($site['name'] ?? '') . '|'
            . ($site['status'] ?? '') . '|'
            . ($site['acceptance_end_at'] ?? '') . '|'
            . implode('、', (array) ($site['accepted_items'] ?? []));
    }

    /**
     * @return array<int, array{name: string, url: string}>
     */
    private function sourcesFor(int $disasterId): array
    {
        $sources = [];
        $seen = [];

        $configured = config('disaster-intelligence.waste_status_sources.' . $disasterId, []);

        if (is_array($configured)) {
            foreach ($configured as $source) {
                $url = trim((string) ($source['url'] ?? ''));

                if (!filter_var($url, FILTER_VALIDATE_URL) || isset($seen[$url])) {
                    continue;
                }

                $sources[] = [
                    'name' => (string) ($source['name'] ?? $source['source_org'] ?? $url),
                    'url' => $url,
                ];
                $seen[$url] = true;
            }
        }

        $discovered = DisasterIntelligenceSource::where('disaster_id', $disasterId)
            ->where('type', 'waste_sites')
            ->where('status', 'active')
            ->orderByDesc('last_synced_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        foreach ($discovered as $source) {
            $url = trim((string) $source->url);

            if (!filter_var($url, FILTER_VALIDATE_URL) || isset($seen[$url])) {
                continue;
            }

            $sources[] = [
                'name' => $source->source_org ?: 'AI収集元',
                'url' => $url,
            ];
            $seen[$url] = true;
        }

        $siteSources = WasteSite::where('disaster_id', $disasterId)
            ->orderByDesc('source_published_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        foreach ($siteSources as $site) {
            $url = trim((string) $site->source_url);

            if (!filter_var($url, FILTER_VALIDATE_URL) || isset($seen[$url])) {
                continue;
            }

            $sources[] = [
                'name' => $site->source_org ?: '仮置場情報',
                'url' => $url,
            ];
            $seen[$url] = true;
        }

        return array_slice($sources, 0, 12);
    }

    /**
     * @param mixed $items
     * @param array<int, array<string, mixed>> $sites
     * @param array<int, array{label: string, name: string, url: string, text: string}> $texts
     * @return array<int, array{status: string, reason: string|null, evidence: string|null, source_url: string|null}>
     */
    private function normalizeStatuses(mixed $items, array $sites, array $texts): array
    {
        if (!is_array($items)) {
            return [];
        }

        $statuses = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $index = filter_var($item['index'] ?? null, FILTER_VALIDATE_INT);

            if ($index === false || !isset($sites[$index])) {
                continue;
            }

            $status = strtolower(trim((string) ($item['status'] ?? 'unknown')));

            if (!in_array($status, self::STATUSES, true)) {
                $status = 'unknown';
            }

            $sourceUrl = null;
            $sourceLabel = strtoupper(trim((string) ($item['source'] ?? '')));

            if ($sourceLabel !== '') {
                foreach ($texts as $text) {
                    if ($text['label'] === $sourceLabel) {
                        $sourceUrl = $text['url'];
                        break;
                    }
                }
            }

            $statuses[$index] = [
                'status' => $status,
                'reason' => $this->nullableText($item['reason'] ?? null, 500),
                'evidence' => $this->nullableText($item['evidence'] ?? null, 1000),
                'source_url' => $sourceUrl,
            ];
        }

        return $statuses;
    }

    /**
     * @param array<int, array<string, mixed>> $sites
     * @param array<int, array{label: string, name: string, url: string, text: string}> $texts
     * @return array<int, array{status: string, evidence: string, source_url: string}>
     */
    private function keywordStatuses(array $sites, array $texts): array
    {
        $matches = [];

        foreach ($texts as $source) {
            $text = $source['text'];

            if (trim($text) === '') {
                continue;
            }

            foreach ($sites as $index => $site) {
                if (isset($matches[$index])) {
                    continue;
                }

                $key = $this->siteKey((string) ($site['name'] ?? ''));

                if (mb_strlen($key) < 3) {
                    continue;
                }

                $offset = 0;
                $length = mb_strlen($key);

                while (($position = mb_strpos($text, $key, $offset)) !== false) {
                    $start = max(0, $position - 300);
                    $window = mb_substr($text, $start, $length + 1200);

                    if (preg_match('/受入終了|受付終了|受入を終了|閉鎖|終了しました/u', $window) === 1) {
                        $matches[$index] = [
                            'status' => 'closed',
                            'evidence' => trim(mb_substr($window, 0, 300)),
                            'source_url' => $source['url'],
                        ];
                        break;
                    }

                    if (preg_match('/受入中|受付中|開設中|継続|受入しています/u', $window) === 1) {
                        $matches[$index] = [
                            'status' => 'open',
                            'evidence' => trim(mb_substr($window, 0, 300)),
                            'source_url' => $source['url'],
                        ];
                        break;
                    }

                    $offset = $position + $length;
                }
            }
        }

        return $matches;
    }

    private function siteKey(string $value): string
    {
        return $this->gate->normalizeKey($value);
    }

    private function nullableText(mixed $value, int $limit): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : mb_substr($value, 0, $limit);
    }

    private function fetchText(string $url): string
    {
        $response = $this->http->get($url);

        if (!$response->successful()) {
            throw new \RuntimeException('HTTP ' . $response->status());
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        $body = $response->body();

        if (str_contains($contentType, 'pdf') || str_contains(strtolower(parse_url($url, PHP_URL_PATH) ?: ''), '.pdf')) {
            $parser = new Parser();
            return trim($parser->parseContent($body)->getText());
        }

        $text = preg_replace('/<(script|style)\b.*?<\/\1>/is', ' ', $body) ?? $body;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/[ \t]+/', ' ', $text));
    }
}
