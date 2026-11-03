<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 保存先
    |--------------------------------------------------------------------------
    */

    'storage_disk' => env('DISASTER_SOURCE_DISK', 'local'),
    'source_root' => 'disaster-sources',

    /*
    |--------------------------------------------------------------------------
    | LLM
    |--------------------------------------------------------------------------
    |
    | OpenAI互換のChat Completions APIを利用する。APIキーが未設定の場合は
    | パース結果を空配列としてフォールバックし、ジョブ自体は失敗させない。
    |
    */

    'llm' => [
        'base_url' => rtrim(env('DISASTER_LLM_BASE_URL', 'https://api.openai.com/v1'), '/'),
        'api_key' => env('DISASTER_LLM_API_KEY'),
        'model' => env('DISASTER_LLM_MODEL', 'gpt-4o-mini'),
        'timeout' => (int) env('DISASTER_LLM_TIMEOUT', 120),
        'retries' => (int) env('DISASTER_LLM_RETRIES', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | 同期対象
    |--------------------------------------------------------------------------
    |
    | 災害IDごとに、type（roads / waste_sites / meeting_insights）、URL、
    | 発信組織、公表日時を登録する。Artisanコマンドの --url / --type で
    | 一時的な取り込みも可能。
    |
    */

    'sources' => [
        5 => [
            [
                'type' => 'meeting_insights',
                'url' => 'https://www.pref.chiba.lg.jp/bousai/bousai/documents/202608154.pdf',
                'source_org' => '千葉県災害対策本部',
                'published_at' => '2026-08-15T17:00:00+09:00',
            ],
            [
                'type' => 'meeting_insights',
                'url' => 'https://www.pref.chiba.lg.jp/bousai/bousai/documents/202608257.pdf',
                'source_org' => '千葉県災害対策本部',
                'published_at' => '2026-08-25T15:30:00+09:00',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 車両区分
    |--------------------------------------------------------------------------
    */

    'vehicles' => [
        'light_truck' => '軽トラック',
        '2t' => '2t車',
        '4t' => '4t車',
        'large' => '大型回送車',
    ],

    'disclaimer' => '本データにはAI抽出（信頼度C）が含まれます。必ず出典の一次情報を確認してください。',
];
