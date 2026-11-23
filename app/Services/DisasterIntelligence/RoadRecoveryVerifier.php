<?php

namespace App\Services\DisasterIntelligence;

use App\DisasterInformationLink;
use App\DisasterIntelligenceSource;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * 古い道路規制が復旧済みでないかを最新情報と照合する。
 */
class RoadRecoveryVerifier
{
    private const STATUSES = ['open', 'partial', 'closed', 'unknown'];

    /** 路線名の直後にあれば解除済みと確定できる表現 */
    private const RELEASE_PATTERN = '/解除|開通|通行止めなし|規制なし|通行可|通れる/u';

    /** 解除の予定・一部解除など、確定できない表現 */
    private const NEGATION_PATTERN = '/見込|見通し|予定|未定|目途|目処|めど|検討|一部|調整中|後日|不可|できません|できない|再度|再び/u';

    /** 同じ行に別の路線が続く場合はそこで打ち切る */
    private const NEXT_ROAD_PATTERN = '/国道|県道|都道|府道|道道|市道|町道|村道|主要地方道|林道|農道/u';

    private int $pdfFetches = 0;

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
     * AIへ送るのは、解除済みでなく、確定表現がなく、情報源に記載があり、
     * 前回AI判定時から候補周辺の記載が変わった候補だけに限る。
     *
     * @param int $disasterId
     * @param array<int, array<string, mixed>> $roads
     * @param bool $fresh 前回のAI判定結果を再利用しない
     * @return array<int, array{status: string, reason: string|null, evidence: string|null, source_url: string|null}>
     */
    public function verify(int $disasterId, array $roads, bool $fresh = false): array
    {
        if ($roads === []) {
            return [];
        }

        $this->pdfFetches = 0;
        $sources = $this->sourcesFor($disasterId);

        if ($sources === []) {
            return [];
        }

        $texts = [];

        foreach ($sources as $source) {
            $url = (string) ($source['url'] ?? '');

            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            try {
                $text = $this->gate->normalizeText($this->fetchTextWithPdfLinks($url));

                if ($text !== '') {
                    $label = 'S' . (count($texts) + 1);
                    $texts[] = [
                        'label' => $label,
                        'name' => (string) ($source['name'] ?? $url),
                        'url' => $url,
                        'text' => $text,
                    ];
                }
            } catch (Throwable $error) {
                Log::warning('道路の復旧確認情報を取得できませんでした。', [
                    'url' => $url,
                    'message' => $error->getMessage(),
                ]);
            }
        }

        if ($texts === []) {
            return [];
        }

        $stats = [
            'disaster_id' => $disasterId,
            'candidates' => count($roads),
            'resolved' => 0,
            'keyword' => 0,
            'no_mention' => 0,
            'cached' => 0,
            'ai_candidates' => 0,
            'ai_calls' => 0,
        ];
        $statuses = [];
        $pending = [];

        foreach ($roads as $index => $road) {
            if (($road['status'] ?? null) === 'open') {
                $stats['resolved']++;
                continue;
            }

            $keys = $this->roadKeys($road);
            $decisive = $this->gate->decisiveMatch(
                $texts,
                $keys,
                self::RELEASE_PATTERN,
                self::NEGATION_PATTERN,
                self::NEXT_ROAD_PATTERN
            );

            if ($decisive !== null) {
                $statuses[$index] = [
                    'status' => 'open',
                    'reason' => '解除・開通の記載',
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
                    'reason' => '最新情報に該当路線の記載がありません',
                    'evidence' => null,
                    'source_url' => null,
                ];
                $stats['no_mention']++;
                continue;
            }

            $candidate = $this->candidateLine($road);
            $fingerprint = $this->gate->fingerprint($candidate, $this->gate->excerptText($texts, $windows));
            $cached = $fresh ? null : $this->gate->cached('roads', $disasterId, $candidate, $fingerprint);

            if ($cached !== null) {
                $statuses[$index] = $cached;
                $stats['cached']++;
                continue;
            }

            $pending[$index] = [
                'candidate' => $candidate,
                'windows' => $windows,
                'fingerprint' => $fingerprint,
            ];
        }

        if ($pending !== []) {
            $stats['ai_candidates'] = count($pending);
            $stats['ai_calls'] = 1;
            $statuses += $this->verifyWithAi($disasterId, $roads, $pending, $texts);
        }

        Log::info('道路の復旧確認ゲート', $stats);
        ksort($statuses);

        return $statuses;
    }

    /**
     * 絞り込んだ候補だけをAIで判定する。AIが判定しなかった候補はキーワードで補う。
     *
     * @param array<int, array<string, mixed>> $roads
     * @param array<int, array{candidate: string, windows: array<string, array<int, array{0: int, 1: int}>>, fingerprint: string}> $pending
     * @param array<int, array{label: string, name: string, url: string, text: string}> $texts
     * @return array<int, array{status: string, reason: string|null, evidence: string|null, source_url: string|null}>
     */
    private function verifyWithAi(int $disasterId, array $roads, array $pending, array $texts): array
    {
        $candidates = [];

        foreach ($pending as $index => $item) {
            $candidates[] = $index . '|' . $item['candidate'];
        }

        $prompt = $this->prompts->render('road_recovery_check', [
            'CANDIDATES' => implode("\n", $candidates),
            'RECOVERY_TEXT' => $this->gate->promptText($texts, array_column($pending, 'windows')),
        ]);

        $targets = array_intersect_key($roads, $pending);
        $aiStatuses = [];
        $succeeded = false;

        try {
            $result = $this->llm->completeJson($prompt);
            $aiStatuses = $this->normalizeStatuses($result['roads'] ?? [], $targets, $texts);
            $succeeded = true;
        } catch (Throwable $error) {
            Log::warning('道路の復旧確認AIを実行できませんでした。', [
                'disaster_id' => $disasterId,
                'message' => $error->getMessage(),
            ]);
        }

        $keywordMatches = $this->keywordRecoveredIndexes($targets, $texts);
        $statuses = [];

        foreach ($pending as $index => $item) {
            $status = $aiStatuses[$index] ?? null;

            if ($status === null && isset($keywordMatches[$index])) {
                $status = [
                    'status' => 'open',
                    'reason' => '解除・開通の記載',
                    'evidence' => $keywordMatches[$index]['evidence'],
                    'source_url' => $keywordMatches[$index]['source_url'],
                ];
            }

            if ($status === null && $succeeded) {
                $status = [
                    'status' => 'unknown',
                    'reason' => 'AIが判定を返しませんでした',
                    'evidence' => null,
                    'source_url' => null,
                ];
            }

            if ($status === null) {
                continue;
            }

            $statuses[$index] = $status;

            // AIが失敗した場合は保存せず、次回に再判定する。
            if ($succeeded) {
                $this->gate->remember('roads', $disasterId, $item['candidate'], $item['fingerprint'], $status);
            }
        }

        return $statuses;
    }

    /**
     * 候補の内容を1行にする。変化検知の指紋にも使う。
     *
     * @param array<string, mixed> $road
     */
    private function candidateLine(array $road): string
    {
        return ($road['road_name'] ?? '') . '|'
            . ($road['section'] ?? '') . '|'
            . ($road['regulation_type'] ?? '') . '|'
            . ($road['reason'] ?? '');
    }

    /**
     * 復旧確認に使う情報源を組み立てる。
     *
     * @return array<int, array{name: string, url: string}>
     */
    private function sourcesFor(int $disasterId): array
    {
        $sources = [];
        $seen = [];

        $add = function (string $url, string $name) use (&$sources, &$seen): void {
            $url = trim($url);

            if (!filter_var($url, FILTER_VALIDATE_URL) || isset($seen[$url])) {
                return;
            }

            $sources[] = [
                'name' => $name !== '' ? $name : $url,
                'url' => $url,
            ];
            $seen[$url] = true;
        };

        $configured = config('disaster-intelligence.recovery_sources.' . $disasterId, []);

        if (is_array($configured)) {
            foreach ($configured as $source) {
                $add(
                    (string) ($source['url'] ?? ''),
                    (string) ($source['name'] ?? $source['source_org'] ?? '')
                );
            }
        }

        $discovered = DisasterIntelligenceSource::where('disaster_id', $disasterId)
            ->where('type', 'roads')
            ->where('status', 'active')
            ->orderByDesc('last_synced_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        foreach ($discovered as $source) {
            $add((string) $source->url, (string) ($source->source_org ?: 'AI収集元'));
        }

        $links = DisasterInformationLink::where('disaster_id', $disasterId)
            ->where(function ($query) {
                $query->where('description', 'like', '%道路%')
                    ->orWhere('description', 'like', '%通行止%')
                    ->orWhere('description', 'like', '%規制%')
                    ->orWhere('description', 'like', '%復旧%')
                    ->orWhere('description', 'like', '%交通%');
            })
            ->orderByDesc('fetched_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        foreach ($links as $link) {
            $add((string) $link->url, '最新情報');
        }

        return array_slice($sources, 0, 15);
    }

    /**
     * AIの出力を検証し、候補indexごとの状態に正規化する。
     *
     * @param mixed $items
     * @param array<int, array<string, mixed>> $roads
     * @param array<int, array{label: string, name: string, url: string, text: string}> $texts
     * @return array<int, array{status: string, reason: string|null, evidence: string|null, source_url: string|null}>
     */
    private function normalizeStatuses(mixed $items, array $roads, array $texts): array
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

            if ($index === false || !isset($roads[$index])) {
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
     * 「解除」「開通」などの復旧情報と路線名の一致から復旧済みを判定する。
     *
     * @param array<int, array<string, mixed>> $roads
     * @param array<int, array{label: string, name: string, url: string, text: string}> $texts
     * @return array<int, array{evidence: string, source_url: string}>
     */
    private function keywordRecoveredIndexes(array $roads, array $texts): array
    {
        $matches = [];

        foreach ($texts as $source) {
            $text = $source['text'];

            if (trim($text) === '') {
                continue;
            }

            foreach ($roads as $index => $road) {
                if (isset($matches[$index])) {
                    continue;
                }

                foreach ($this->roadKeys($road) as $key) {
                    if (mb_strlen($key) < 3) {
                        continue;
                    }

                    $offset = 0;
                    $length = mb_strlen($key);

                    while (($position = mb_strpos($text, $key, $offset)) !== false) {
                        $start = max(0, $position - 300);
                        $window = mb_substr($text, $start, $length + 1200);

                        if (preg_match('/解除|開通|通行止めなし|規制なし|通れる/u', $window) === 1) {
                            $matches[$index] = [
                                'evidence' => trim(mb_substr($window, 0, 300)),
                                'source_url' => $source['url'],
                            ];
                            break;
                        }

                        $offset = $position + $length;
                    }

                    if (isset($matches[$index])) {
                        break;
                    }
                }
            }
        }

        return $matches;
    }

    /**
     * 照合に使う路線名のキーを作る。区間名（市区町村名など）は別路線と誤一致しやすいため、
     * 路線名がない場合だけ使う。
     *
     * @param array<string, mixed> $road
     * @return array<int, string>
     */
    private function roadKeys(array $road): array
    {
        $values = trim((string) ($road['road_name'] ?? '')) !== ''
            ? [(string) $road['road_name']]
            : [(string) ($road['section'] ?? '')];
        $keys = [];

        foreach ($values as $value) {
            $value = $this->gate->normalizeKey($value);

            if ($value === '') {
                continue;
            }

            $keys[] = $value;

            $stripped = preg_replace(
                '/^(（[一二三四五六七八九十主]）|\([一二三四五六七八九十主]\)|一般県道|主要地方道|県道|国道)/u',
                '',
                $value
            ) ?? $value;

            if ($stripped !== '' && $stripped !== $value) {
                $keys[] = $stripped;
            }
        }

        return array_values(array_unique($keys));
    }

    private function nullableText(mixed $value, int $limit): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : mb_substr($value, 0, $limit);
    }

    /**
     * URLからテキストを取得し、HTML内の復旧関連PDFも取り込む。
     */
    private function fetchTextWithPdfLinks(string $url): string
    {
        $response = $this->http->get($url);

        if (!$response->successful()) {
            throw new \RuntimeException('HTTP ' . $response->status());
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        $text = $this->responseText($response, $url);

        $path = strtolower((string) (parse_url($url, PHP_URL_PATH) ?: ''));

        if (!str_contains($contentType, 'html') && !($contentType === '' && !str_ends_with($path, '.pdf'))) {
            return $text;
        }

        foreach ($this->pdfLinks($url, $response->body()) as $pdfUrl) {
            if ($this->pdfFetches >= 5) {
                break;
            }

            $this->pdfFetches++;

            try {
                $pdfText = $this->fetchText($pdfUrl);

                if ($pdfText !== '') {
                    $text .= "\n\n### PDF: " . $pdfUrl . "\n" . $pdfText;
                }
            } catch (Throwable $error) {
                Log::warning('復旧関連PDFを取得できませんでした。', [
                    'url' => $pdfUrl,
                    'message' => $error->getMessage(),
                ]);
            }
        }

        return $text;
    }

    /**
     * URLからテキストを取得する。
     */
    private function fetchText(string $url): string
    {
        $response = $this->http->get($url);

        if (!$response->successful()) {
            throw new \RuntimeException('HTTP ' . $response->status());
        }

        return $this->responseText($response, $url);
    }

    private function responseText(\Illuminate\Http\Client\Response $response, string $url): string
    {
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

    /**
     * HTML内から復旧・解除関連のPDFリンクを抽出する。
     *
     * @return array<int, string>
     */
    private function pdfLinks(string $baseUrl, string $html): array
    {
        $links = [];

        if (preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $href = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $label = trim(strip_tags(html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                $path = strtolower((string) (parse_url($href, PHP_URL_PATH) ?: ''));

                if (!str_ends_with($path, '.pdf') && preg_match('/復旧|通行止|解除|被災|見込み/u', $label) !== 1) {
                    continue;
                }

                $absolute = $this->absoluteUrl($baseUrl, $href);

                if (!filter_var($absolute, FILTER_VALIDATE_URL)) {
                    continue;
                }

                $links[$absolute] = true;

                if (count($links) >= 5) {
                    break;
                }
            }
        }

        return array_keys($links);
    }

    private function absoluteUrl(string $baseUrl, string $href): string
    {
        if (preg_match('#^https?://#i', $href) === 1) {
            return $href;
        }

        try {
            return (string) UriResolver::resolve(new Uri($baseUrl), new Uri($href));
        } catch (Throwable) {
            return '';
        }
    }
}
