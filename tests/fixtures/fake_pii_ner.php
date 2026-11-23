<?php

$input = json_decode(stream_get_contents(STDIN), true);
$text = is_array($input) && isset($input['text']) ? (string) $input['text'] : '';
$count = substr_count($text, '山田太郎');
$text = str_replace('山田太郎', '[氏名]', $text);

echo json_encode([
    'text' => $text,
    'counts' => ['person' => $count],
], JSON_UNESCAPED_UNICODE);
