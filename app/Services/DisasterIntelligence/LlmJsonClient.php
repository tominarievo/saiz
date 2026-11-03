<?php

namespace App\Services\DisasterIntelligence;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * OpenAI互換APIからJSONを取得するクライアント。
 */
class LlmJsonClient
{
    /**
     * JSONオブジェクトを返す。失敗時は規定回数リトライする。
     *
     * @param string $prompt
     * @return array<string, mixed>
     */
    public function completeJson(string $prompt): array
    {
        $config = config('disaster-intelligence.llm');
        $apiKey = (string) ($config['api_key'] ?? '');

        if ($apiKey === '') {
            throw new RuntimeException('DISASTER_LLM_API_KEY が設定されていません。');
        }

        $retries = max(1, (int) ($config['retries'] ?? 2));
        $lastError = null;

        $sessionHeader = trim((string) ($config['session_header'] ?? ''));
        $sessionId = trim((string) ($config['session_id'] ?? ''));
        $headers = $sessionHeader !== '' && $sessionId !== ''
            ? [$sessionHeader => $sessionId]
            : [];

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                $response = Http::withToken($apiKey)
                    ->withHeaders($headers)
                    ->acceptJson()
                    ->timeout((int) ($config['timeout'] ?? 120))
                    ->post(rtrim((string) $config['base_url'], '/') . '/chat/completions', [
                        'model' => $config['model'] ?? 'gpt-4o-mini',
                        'temperature' => 0.1,
                        'response_format' => ['type' => 'json_object'],
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'あなたは災害支援情報を厳密に構造化する。必ずJSONオブジェクトのみを返す。',
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt,
                            ],
                        ],
                    ]);

                if (!$response->successful()) {
                    throw new RuntimeException('LLM APIエラー: HTTP ' . $response->status());
                }

                $content = $response->json('choices.0.message.content');

                if (!is_string($content) || trim($content) === '') {
                    throw new RuntimeException('LLMの応答が空です。');
                }

                $decoded = json_decode($content, true);

                if (!is_array($decoded)) {
                    throw new RuntimeException('LLMの応答をJSONとして解釈できません。');
                }

                return $decoded;
            } catch (Throwable $error) {
                $lastError = $error;
                Log::warning('災害インテリジェンスLLMの呼び出しに失敗しました。', [
                    'attempt' => $attempt,
                    'message' => $error->getMessage(),
                ]);

                if ($attempt < $retries) {
                    usleep(300000 * $attempt);
                }
            }
        }

        throw new RuntimeException(
            'LLMの呼び出しに失敗しました: ' . ($lastError?->getMessage() ?? '不明なエラー'),
            0,
            $lastError
        );
    }
}
