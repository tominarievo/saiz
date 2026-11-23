<?php

namespace App\Services\DisasterIntelligence;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * LLMへ送信する前に、資料本文から高リスクの個人情報を除去する。
 */
class SourceTextRedactor
{
    private const HONORIFIC_STOP_WORDS = [
        'お客',
        '皆',
        'みな',
        '先生',
        '会社',
        '団体',
        '法人',
        '市',
        '町',
        '村',
        '県',
        '区',
        '駅',
        '公園',
        'センター',
        '病院',
        '学校',
        '避難所',
        '自治体',
        '担当',
    ];

    /**
     * @param string $text
     * @param bool $useNer
     * @return string
     */
    public function redact(string $text, bool $useNer = true): string
    {
        $config = (array) config('disaster-intelligence.redaction', []);

        if (!(bool) ($config['enabled'] ?? true) || $text === '') {
            return $text;
        }

        $counts = [
            'email' => 0,
            'phone' => 0,
            'mynumber' => 0,
            'labeled' => 0,
            'name' => 0,
            'address' => 0,
            'postal' => 0,
        ];

        $text = $this->replace(
            $text,
            '/[\p{L}\p{N}._%+\-]+@[\p{L}\p{N}.\-]+\.[\p{L}]{2,}/u',
            '[EMAIL]',
            $counts['email']
        );

        $text = $this->replace(
            $text,
            '/(?<!\d)(?:\+81[-\s]?)?(?:0\d{1,4}[-\s]?\d{1,4}[-\s]?\d{3,4})(?!\d)/u',
            '[PHONE]',
            $counts['phone']
        );

        $text = $this->replace(
            $text,
            '/(マイナンバー|個人番号|法人番号)([：:\s]+)(?:\d[-\s]?){12,16}/u',
            '$1$2[MYNUMBER]',
            $counts['mynumber']
        );

        $text = $this->replace(
            $text,
            '/(?<!\d)\d{4}[-\s]\d{4}[-\s]\d{4}(?!\d)/u',
            '[MYNUMBER]',
            $counts['mynumber']
        );

        $text = $this->redactLabeledInfo($text, $counts);
        $text = $this->redactHonorificNames($text, $counts);

        if ((bool) ($config['strict'] ?? false)) {
            $text = $this->replace(
                $text,
                '/(住所|所在地)([：:\s]+)([^\n\r|]{1,120})/u',
                '$1$2[ADDRESS]',
                $counts['address']
            );

            $text = $this->replace(
                $text,
                '/〒\s*\d{3}[-\s]?\d{4}/u',
                '[POSTAL]',
                $counts['postal']
            );
        }

        if ($useNer) {
            $text = $this->redactWithNer($text);
        }

        if (array_sum($counts) > 0) {
            Log::info('災害資料の個人情報をマスキングしました。', [
                'counts' => array_filter($counts),
                'length' => mb_strlen($text),
            ]);
        }

        return $text;
    }

    /**
     * 配列・JSON構造内の文字列を再帰的にマスキングする。NERは使わない。
     *
     * @param mixed $value
     * @return mixed
     */
    public function redactArray(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->redact($value, false);
        }

        if (is_array($value)) {
            $result = [];

            foreach ($value as $key => $item) {
                $result[is_string($key) ? $this->redact($key, false) : $key] = $this->redactArray($item);
            }

            return $result;
        }

        return $value;
    }

    /**
     * GiNZAによる人名検出を実行する。失敗時は正規表現のみの結果を返す。
     *
     * @param string $text
     * @return string
     */
    private function redactWithNer(string $text): string
    {
        $config = (array) config('disaster-intelligence.redaction.ner', []);

        if (!(bool) ($config['enabled'] ?? false) || trim($text) === '') {
            return $text;
        }

        $python = (string) ($config['python'] ?? '/usr/bin/python3');
        $script = (string) ($config['script'] ?? base_path('scripts/pii_redact.py'));
        $timeout = max(1, (int) ($config['timeout'] ?? 60));

        if (!is_file($script)) {
            Log::warning('個人情報NERスクリプトが見つかりません。', ['script' => $script]);

            return $text;
        }

        $input = json_encode([
            'text' => $text,
            'strict' => (bool) config('disaster-intelligence.redaction.strict', false),
        ], JSON_UNESCAPED_UNICODE);

        if ($input === false) {
            return $text;
        }

        try {
            $process = new Process([$python, $script]);
            $process->setInput($input);
            $process->setTimeout($timeout);
            $process->run();

            if (!$process->isSuccessful()) {
                Log::warning('個人情報NERの実行に失敗しました。', [
                    'exit_code' => $process->getExitCode(),
                    'error' => mb_substr(trim($process->getErrorOutput() ?: $process->getOutput()), 0, 500),
                ]);

                return $text;
            }

            $decoded = json_decode($process->getOutput(), true);

            if (!is_array($decoded) || !isset($decoded['text']) || !is_string($decoded['text'])) {
                Log::warning('個人情報NERの応答を解釈できませんでした。');

                return $text;
            }

            $nerCounts = is_array($decoded['counts'] ?? null) ? $decoded['counts'] : [];

            if (array_sum($nerCounts) > 0) {
                Log::info('GiNZAで個人情報をマスキングしました。', ['counts' => $nerCounts]);
            }

            return $decoded['text'];
        } catch (Throwable $error) {
            Log::warning('個人情報NERの呼び出しに失敗しました。', [
                'message' => $error->getMessage(),
            ]);

            return $text;
        }
    }

    /**
     * @param string $text
     * @param array<string, int> $counts
     * @return string
     */
    private function redactLabeledInfo(string $text, array &$counts): string
    {
        $labels = [
            '氏名',
            '名前',
            '担当者',
            '代表者',
            '連絡先',
            '電話番号',
            '電話',
            'FAX',
            'メールアドレス',
            'メール',
            '生年月日',
            '年齢',
            '性別',
            '要配慮',
            '障害',
            '障がい',
            '介護',
            '難病',
            'アレルギー',
            '避難行動要支援者',
        ];

        $pattern = '/(' . implode('|', array_map(
            fn (string $label) => preg_quote($label, '/'),
            $labels
        )) . ')([：:\s]+)([^\n\r|]{1,120})/u';

        return $this->replace($text, $pattern, '$1$2[REDACTED]', $counts['labeled']);
    }

    /**
     * @param string $text
     * @param array<string, int> $counts
     * @return string
     */
    private function redactHonorificNames(string $text, array &$counts): string
    {
        $pattern = '/([\p{L}\p{N}ー]{1,12})(さん|様|氏|殿|ちゃん)/u';

        $result = preg_replace_callback($pattern, function (array $matches) use (&$counts) {
            if (in_array($matches[1], self::HONORIFIC_STOP_WORDS, true)) {
                return $matches[0];
            }

            $counts['name']++;

            return '[氏名]' . $matches[2];
        }, $text);

        return $result ?? $text;
    }

    /**
     * @param string $text
     * @param string $pattern
     * @param string $replacement
     * @param int $count
     * @return string
     */
    private function replace(string $text, string $pattern, string $replacement, int &$count): string
    {
        $result = preg_replace($pattern, $replacement, $text, -1, $replaced);

        if ($result === null) {
            return $text;
        }

        $count += (int) $replaced;

        return $result;
    }
}
