<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 保存先
    |--------------------------------------------------------------------------
    */

    'storage_disk' => env('DISASTER_SOURCE_DISK', 'local'),
    'source_root' => 'disaster-sources',
    'source_reparse_cooldown' => (int) env('DISASTER_SOURCE_REPARSE_COOLDOWN_SECONDS', 86400),

    /*
    |--------------------------------------------------------------------------
    | 取得ポリシー
    |--------------------------------------------------------------------------
    |
    | 自動収集は公式ドメインと承認済み団体に限定する。JVOAD / KVOAD / CVOAD は
    | 運用上必要な情報源として明示的に許可する。許可外のURLは取り込まない。
    |
    */

    'crawler' => [
        'enforce_allowlist' => (bool) env('DISASTER_CRAWLER_ENFORCE_ALLOWLIST', true),
        'resolve_dns' => (bool) env('DISASTER_CRAWLER_RESOLVE_DNS', true),
        'respect_robots' => (bool) env('DISASTER_CRAWLER_RESPECT_ROBOTS', true),
        'allow_http' => (bool) env('DISASTER_CRAWLER_ALLOW_HTTP', true),
        'user_agent' => env(
            'DISASTER_CRAWLER_USER_AGENT',
            'SaizBot/1.0 (disaster information research)'
        ),
        'contact' => env('DISASTER_CRAWLER_CONTACT', ''),
        'min_interval_ms' => (int) env('DISASTER_CRAWLER_MIN_INTERVAL_MS', 1000),
        'max_redirects' => (int) env('DISASTER_CRAWLER_MAX_REDIRECTS', 3),
        'max_response_bytes' => (int) env('DISASTER_CRAWLER_MAX_RESPONSE_BYTES', 20 * 1024 * 1024),
        'connect_timeout' => (int) env('DISASTER_CRAWLER_CONNECT_TIMEOUT', 10),
        'timeout' => (int) env('DISASTER_CRAWLER_TIMEOUT', 60),
        'allowed_ports' => [80, 443],
        'allowed_hosts' => [
            'jvoad.jp',
            'kvoad.com',
            'cvoad.jp',
            'portal.bousai.pref.kumamoto.jp',
            'd2800000147bueaq.my.salesforce-sites.com',
            'pref-chiba-bousai.my.salesforce-sites.com',
            'geoshape.ex.nii.ac.jp',
        ],
        'allowed_host_suffixes' => [
            '.go.jp',
            '.lg.jp',
            '.or.jp',
            '.jvoad.jp',
            '.kvoad.com',
            '.cvoad.jp',
        ],
        'allowed_host_patterns' => [
            '/^(?:www\.)?(?:city|town|vill|pref)\.[a-z0-9-]+(?:\.[a-z0-9-]+)*\.jp$/i',
        ],
    ],

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
        'session_header' => env('DISASTER_LLM_SESSION_HEADER', 'x-opencode-session'),
        'session_id' => env('DISASTER_LLM_SESSION_ID', 'saiz-disaster-intelligence'),
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
    | 復旧確認用の最新情報
    |--------------------------------------------------------------------------
    |
    | 古い災害資料から抽出した道路規制が、その後の復旧で解除されていないか
    | 確認するための情報源。ここにない災害は復旧確認を行わない。
    |
    */

    'traffic_feeds' => [
        6 => [
            'url' => 'https://portal.bousai.pref.kumamoto.jp/data/traffic/traffic.json',
            'source_page' => 'https://portal.bousai.pref.kumamoto.jp/?p=traffic',
            'source_org' => '熊本県 防災情報くまもと',
        ],
    ],

    /*
    | 道路の解除・仮置場の受入終了を確認する際のAI呼び出し抑制。
    | 確定表現のある候補・記載のない候補はAIを使わずに判定し、候補名周辺の
    | 記載が前回AI判定時と同じ場合は cache_ttl 秒の間その結果を再利用する。
    | AIへは各情報源の冒頭と、候補名の前 excerpt_before 文字・後 excerpt_after 文字だけを送る。
    */
    'status_check' => [
        'cache_ttl' => (int) env('DISASTER_STATUS_CHECK_CACHE_TTL', 604800),
        'excerpt_before' => 150,
        'excerpt_after' => 400,
        'max_prompt_chars' => (int) env('DISASTER_STATUS_CHECK_MAX_CHARS', 40000),
    ],

    'recovery_sources' => [
        10 => [
            [
                'name' => '国土交通省 北陸地方整備局 令和8年8月27日からの大雨 第11報',
                'url' => 'https://www.hrr.mlit.go.jp/press/2026/9/260911hokuriku.pdf',
                'source_org' => '国土交通省 北陸地方整備局',
            ],
            [
                'name' => '富山県 道路規制情報',
                'url' => 'https://www.pref.toyama.jp/1501/kendodukuri/dourokouwan/douro/kj00014191.html',
                'source_org' => '富山県',
            ],
            [
                'name' => '高岡市 災害情報',
                'url' => 'https://www.city.takaoka.toyama.jp/emergency/14249.html',
                'source_org' => '高岡市',
            ],
            [
                'name' => '氷見市 市道交通規制情報',
                'url' => 'https://www.city.himi.toyama.jp/gyosei/soshiki/doroka/1/1/15189.html',
                'source_org' => '氷見市',
            ],
        ],
        6 => [
            [
                'name' => '国土交通省 令和8年熊本地震 道路対応状況',
                'url' => 'https://www.mlit.go.jp/road/road_fr4_000201.html',
                'source_org' => '国土交通省',
            ],
            [
                'name' => '熊本県 防災情報くまもと 通行規制',
                'url' => 'https://portal.bousai.pref.kumamoto.jp/?p=traffic',
                'source_org' => '熊本県',
            ],
        ],
        5 => [
            [
                'name' => '国土交通省 関東地方整備局 千葉国道事務所 災害情報',
                'url' => 'https://www.ktr.mlit.go.jp/saigai/chiba_dis0031.html',
                'source_org' => '国土交通省 関東地方整備局 千葉国道事務所',
            ],
            [
                'name' => '千葉県 県管理道路の被災箇所の状況と復旧見込み',
                'url' => 'https://www.pref.chiba.lg.jp/doukan/douroiji/documents/fukkymitoshi31.pdf',
                'source_org' => '千葉県',
            ],
            [
                'name' => '千葉市 幹線道路の通行規制',
                'url' => 'https://www.city.chiba.jp/kensetsu/doboku/dobokukanri/saigaifultukyuu.html',
                'source_org' => '千葉市',
            ],
            [
                'name' => '国土交通省 令和8年8月千葉豪雨 対応状況',
                'url' => 'https://www.mlit.go.jp/road/road_fr4_000204.html',
                'source_org' => '国土交通省',
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

    /*
    |--------------------------------------------------------------------------
    | 出典ライセンス
    |--------------------------------------------------------------------------
    |
    | ホスト名の完全一致またはサフィックスで判定する。政府標準利用規約は
    | CC BY 4.0互換だが、自治体・第三者のコンテンツは個別条件を確認する。
    |
    */

    'source_licenses' => [
        'nlftp.mlit.go.jp' => [
            'name' => 'CC BY 4.0（一部自治体は利用条件あり）',
            'url' => 'https://nlftp.mlit.go.jp/ksj/other/agreement.html',
        ],
        'gsi.go.jp' => [
            'name' => '国土地理院コンテンツ利用規約',
            'url' => 'https://www.gsi.go.jp/kikakuchousei/kikakuchousei40182.html',
        ],
        'geoshape.ex.nii.ac.jp' => [
            'name' => 'CC BY-SA 4.0',
            'url' => 'https://geoshape.ex.nii.ac.jp/city/',
        ],
        'jvoad.jp' => [
            'name' => 'JVOADサイト利用規約',
            'url' => 'https://jvoad.jp/',
        ],
        'kvoad.com' => [
            'name' => 'KVOADサイト利用規約',
            'url' => 'https://www.kvoad.com/',
        ],
        'cvoad.jp' => [
            'name' => 'CVOADサイト利用規約',
            'url' => 'https://cvoad.jp/',
        ],
        'portal.bousai.pref.kumamoto.jp' => [
            'name' => '熊本県防災情報サイト利用規約',
            'url' => 'https://portal.bousai.pref.kumamoto.jp/',
        ],
        'government' => [
            'name' => '政府標準利用規約等（出典元の利用規約を確認）',
            'url' => 'https://www.digital.go.jp/resources/open_data/',
        ],
        'default' => [
            'name' => '出典元の利用規約を確認',
            'url' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 個人情報マスキング
    |--------------------------------------------------------------------------
    |
    | LLMへ送信する前に、高リスクの個人情報をローカルで置換する。strictを
    | 有効にすると住所・郵便番号も置換する。屋外施設の住所抽出が不要な場合に使う。
    |
    */

    'redaction' => [
        'enabled' => (bool) env('DISASTER_REDACT_PII', true),
        'strict' => (bool) env('DISASTER_REDACT_STRICT', false),
        'ner' => [
            'enabled' => (bool) env('DISASTER_REDACT_NER', false),
            'python' => env('DISASTER_REDACT_NER_PYTHON', '/usr/bin/python3'),
            'script' => env('DISASTER_REDACT_NER_SCRIPT', base_path('scripts/pii_redact.py')),
            'timeout' => (int) env('DISASTER_REDACT_NER_TIMEOUT', 60),
        ],
    ],

    'disclaimer' => '本データはAIによる抽出を含みます。必ず出典の一次情報を確認してください。',
];
