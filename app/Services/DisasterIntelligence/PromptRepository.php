<?php

namespace App\Services\DisasterIntelligence;

use RuntimeException;

/**
 * resources/prompts 配下のプロンプトを読み込む。
 */
class PromptRepository
{
    /**
     * プレースホルダーを置換したプロンプトを返す。
     *
     * @param string $name
     * @param array<string, string|int|float|null> $variables
     * @return string
     */
    public function render(string $name, array $variables = []): string
    {
        $path = resource_path('prompts/' . $name . '.txt');

        if (!is_file($path)) {
            throw new RuntimeException('プロンプトが見つかりません: ' . $path);
        }

        $template = file_get_contents($path);

        if ($template === false) {
            throw new RuntimeException('プロンプトを読み込めません: ' . $path);
        }

        $replacements = [];

        foreach ($variables as $key => $value) {
            $replacements['{{' . $key . '}}'] = (string) ($value ?? '');
        }

        return strtr($template, $replacements);
    }
}
