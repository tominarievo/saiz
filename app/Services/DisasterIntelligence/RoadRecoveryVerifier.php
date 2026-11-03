<?php

namespace App\Services\DisasterIntelligence;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * 古い道路規制が復旧済みでないかを最新情報と照合する。
 */
class RoadRecoveryVerifier
{
    /**
     * @param PromptRepository $prompts
     * @param LlmJsonClient $llm
     */
    public function __construct(
        private readonly PromptRepository $prompts,
        private readonly LlmJsonClient $llm
    ) {
    }

    /**
     * 復旧・解除が確認できた候補のindexを返す。
     *
     * @param int $disasterId
     * @param array<int, array<string, mixed>> $roads
     * @return array<int, int>
     */
    public function recoveredIndexes(int $disasterId, array $roads): array
    {
        if ($roads === []) {
            return [];
        }

        $sources = config('disaster-intelligence.recovery_sources.' . $disasterId, []);

        if (!is_array($sources) || $sources === []) {
            return [];
        }

        $texts = [];

        foreach ($sources as $source) {
            $url = (string) ($source['url'] ?? '');

            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            try {
                $text = $this->fetchText($url);

                if ($text !== '') {
                    $texts[] = '### ' . ($source['name'] ?? $url) . "\n" . $text;
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

        $candidates = [];

        foreach ($roads as $index => $road) {
            $candidates[] = $index . '|'
                . ($road['road_name'] ?? '') . '|'
                . ($road['section'] ?? '') . '|'
                . ($road['reason'] ?? '');
        }

        $prompt = $this->prompts->render('road_recovery_check', [
            'CANDIDATES' => implode("\n", $candidates),
            'RECOVERY_TEXT' => mb_substr(implode("\n\n", $texts), 0, 120000),
        ]);

        try {
            $result = $this->llm->completeJson($prompt);
        } catch (Throwable $error) {
            Log::warning('道路の復旧確認AIを実行できませんでした。', [
                'disaster_id' => $disasterId,
                'message' => $error->getMessage(),
            ]);

            return [];
        }

        $recovered = $result['recovered'] ?? [];

        if (!is_array($recovered)) {
            return [];
        }

        $indexes = [];

        foreach ($recovered as $item) {
            if (!is_array($item)) {
                continue;
            }

            $index = filter_var($item['index'] ?? null, FILTER_VALIDATE_INT);

            if ($index !== false && isset($roads[$index])) {
                $indexes[$index] = $index;
            }
        }

        foreach ($this->keywordRecoveredIndexes($roads, $texts) as $index) {
            $indexes[$index] = $index;
        }

        $indexes = array_filter($indexes, function ($index) use ($roads) {
            return ($roads[$index]['regulation_type'] ?? '') === 'full_closure';
        });

        return array_values($indexes);
    }

    /**
     * 「解除」「開通」などの復旧情報と路線名の一致から復旧済みを判定する。
     *
     * @param array<int, array<string, mixed>> $roads
     * @param array<int, string> $texts
     * @return array<int, int>
     */
    private function keywordRecoveredIndexes(array $roads, array $texts): array
    {
        $text = implode("\n", $texts);

        if (trim($text) === '') {
            return [];
        }

        $indexes = [];

        foreach ($roads as $index => $road) {
            if (($road['regulation_type'] ?? '') !== 'full_closure') {
                continue;
            }

            $key = $this->roadKey((string) ($road['road_name'] ?? ''));

            if (mb_strlen($key) < 4) {
                continue;
            }

            $offset = 0;
            $length = mb_strlen($key);

            while (($position = mb_strpos($text, $key, $offset)) !== false) {
                $start = max(0, $position - 250);
                $window = mb_substr($text, $start, $length + 1000);

                if (str_contains($window, '解除') || str_contains($window, '開通') || str_contains($window, '復旧')) {
                    $indexes[] = $index;
                    break;
                }

                $offset = $position + $length;
            }
        }

        return array_values(array_unique($indexes));
    }

    /**
     * 路線名の比較用キーを作る。
     */
    private function roadKey(string $value): string
    {
        $value = mb_convert_kana($value, 'asKV');
        $value = preg_replace('/（旧[^）]*）|\(旧[^)]*\)/u', '', $value) ?? $value;
        $value = preg_replace('/[0-9０-９]/u', '', $value) ?? $value;
        $value = str_replace(
            ['（主）', '(主)', '（一）', '(一)', '一般県道', '主要地方道', '県道', '国道', '第', '号', '・', ' ', '　'],
            '',
            $value
        );

        return trim($value);
    }

    /**
     * URLからテキストを取得する。
     */
    private function fetchText(string $url): string
    {
        $response = Http::timeout(60)->get($url);

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
