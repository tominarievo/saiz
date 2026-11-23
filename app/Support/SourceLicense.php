<?php

namespace App\Support;

/**
 * 出典URLから表示用のライセンス情報を判定する。
 */
class SourceLicense
{
    /**
     * @param string|null $url
     * @return array{name: string, url: string|null}
     */
    public static function forUrl(?string $url): array
    {
        $host = strtolower((string) parse_url((string) $url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host) ?? $host;
        $licenses = (array) config('disaster-intelligence.source_licenses', []);

        foreach ($licenses as $key => $license) {
            if (!is_array($license) || in_array($key, ['government', 'default'], true)) {
                continue;
            }

            $suffix = ltrim(strtolower((string) $key), '.');

            if ($host === $suffix || ($suffix !== '' && str_ends_with($host, '.' . $suffix))) {
                return [
                    'name' => (string) ($license['name'] ?? '出典元の利用規約を確認'),
                    'url' => isset($license['url']) ? (string) $license['url'] : null,
                ];
            }
        }

        if (str_ends_with($host, '.go.jp') || str_ends_with($host, '.lg.jp')) {
            return self::normalize($licenses['government'] ?? null);
        }

        return self::normalize($licenses['default'] ?? null);
    }

    /**
     * @param mixed $license
     * @return array{name: string, url: string|null}
     */
    private static function normalize(mixed $license): array
    {
        if (!is_array($license)) {
            return ['name' => '出典元の利用規約を確認', 'url' => null];
        }

        return [
            'name' => (string) ($license['name'] ?? '出典元の利用規約を確認'),
            'url' => isset($license['url']) ? (string) $license['url'] : null,
        ];
    }
}
