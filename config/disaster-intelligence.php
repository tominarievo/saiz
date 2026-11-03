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
        1 => [
            [
                'type' => 'roads',
                'url' => 'https://www.pref.toyama.jp/1501/kendodukuri/dourokouwan/douro/kj00014191.html',
                'source_org' => '富山県 土木部道路課',
                'published_at' => '2026-10-14T00:00:00+09:00',
            ],
            [
                'type' => 'roads',
                'url' => 'https://www.pref.toyama.jp/documents/56945/toyama-saigaitaisakuhonbuinkaigisiryo6.pdf',
                'source_org' => '富山県災害対策本部',
                'published_at' => '2026-09-01T07:00:00+09:00',
            ],
            [
                'type' => 'roads',
                'url' => 'https://www.hrr.mlit.go.jp/press/2026/8/260831hokuriku3.pdf',
                'source_org' => '国土交通省 北陸地方整備局',
                'published_at' => '2026-08-31T17:00:00+09:00',
            ],
            [
                'type' => 'roads',
                'url' => 'https://www.city.takaoka.toyama.jp/soshiki/kohohasshinka/2/3/2/14242.html',
                'source_org' => '高岡市',
                'published_at' => '2026-10-27T00:00:00+09:00',
            ],
            [
                'type' => 'roads',
                'url' => 'https://www.city.himi.toyama.jp/gyosei/soshiki/doroka/1/1/15189.html',
                'source_org' => '氷見市',
                'published_at' => '2026-09-04T18:00:00+09:00',
            ],
        ],
        5 => [
            [
                'type' => 'roads',
                'url' => 'https://www.pref.chiba.lg.jp/bousai/bousai/documents/202608216.pdf',
                'source_org' => '千葉県災害対策本部',
                'published_at' => '2026-08-21T17:00:00+09:00',
            ],
            [
                'type' => 'roads',
                'url' => 'https://www.city.chiba.jp/kensetsu/doboku/dobokukanri/saigaifultukyuu.html',
                'source_org' => '千葉市',
                'published_at' => '2026-10-29T00:00:00+09:00',
            ],
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
