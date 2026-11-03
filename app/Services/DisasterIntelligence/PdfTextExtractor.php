<?php

namespace App\Services\DisasterIntelligence;

use RuntimeException;
use Smalot\PdfParser\Parser;

/**
 * PDF・テキスト・HTMLから本文テキストを抽出する。
 */
class PdfTextExtractor
{
    /**
     * ファイルからテキストを抽出する。
     *
     * @param string $absolutePath
     * @param string $extension
     * @return string
     */
    public function extract(string $absolutePath, string $extension): string
    {
        if (!is_file($absolutePath)) {
            throw new RuntimeException('ファイルが見つかりません: ' . $absolutePath);
        }

        $extension = strtolower($extension);

        if (in_array($extension, ['txt', 'md', 'csv'], true)) {
            return trim((string) file_get_contents($absolutePath));
        }

        if (in_array($extension, ['html', 'htm'], true)) {
            $html = (string) file_get_contents($absolutePath);
            $text = preg_replace('/<(script|style)\b.*?<\/\1>/is', ' ', $html) ?? $html;
            $text = strip_tags($text);
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return trim((string) preg_replace('/[ \t]+/', ' ', $text));
        }

        if ($extension === 'pdf') {
            $parser = new Parser();
            $document = $parser->parseFile($absolutePath);

            return trim($document->getText());
        }

        throw new RuntimeException('未対応のファイル形式です: ' . $extension);
    }
}
