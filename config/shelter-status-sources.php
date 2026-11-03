<?php

$chibaPortal = [
    [
        'name' => '千葉県防災ポータルサイト',
        'url' => 'https://www.bousai.pref.chiba.lg.jp/',
    ],
];

return [
    '162027' => [
        'name' => '高岡市',
        'sources' => [
            [
                'name' => '高岡市 避難所開設状況',
                'url' => 'https://www.city.takaoka.toyama.jp/soshiki/kohohasshinka/2/3/2/10811.html',
            ],
            [
                'name' => '高岡市 災害情報',
                'url' => 'https://www.city.takaoka.toyama.jp/soshiki/kohohasshinka/2/3/2/index.html',
            ],
            [
                'name' => '富山防災WEB 避難所情報',
                'url' => 'https://d2800000147bueaq.my.salesforce-sites.com/bousai2/TBW_VF_Refuge',
            ],
        ],
    ],
    '162051' => [
        'name' => '氷見市',
        'aliases' => [
            'ふれスポ' => 'ふれあいスポーツセンター',
        ],
        'sources' => [
            [
                'name' => '氷見市 避難所情報',
                'url' => 'https://www.city.himi.toyama.jp/emergency/9236.html',
            ],
            [
                'name' => '氷見市 緊急情報',
                'url' => 'https://www.city.himi.toyama.jp/emergency/index.html',
            ],
            [
                'name' => '富山防災WEB 避難所情報',
                'url' => 'https://d2800000147bueaq.my.salesforce-sites.com/bousai2/TBW_VF_Refuge',
            ],
        ],
    ],
    '12100' => ['name' => '千葉市', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12203' => ['name' => '市川市', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12204' => ['name' => '船橋市', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12205' => ['name' => '館山市', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12207' => ['name' => '松戸市', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12210' => ['name' => '茂原市', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12212' => ['name' => '佐倉市', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12213' => ['name' => '東金市', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12216' => ['name' => '習志野市', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12217' => ['name' => '柏市', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12219' => ['name' => '市原市', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12221' => ['name' => '八千代市', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12222' => ['name' => '我孫子市', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12224' => ['name' => '鎌ケ谷市', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12228' => ['name' => '四街道市', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12230' => ['name' => '八街市', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12231' => ['name' => '印西市', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12232' => ['name' => '白井市', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12233' => ['name' => '富里市', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12237' => ['name' => '山武市', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12239' => ['name' => '大網白里市', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12322' => ['name' => '酒々井町', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12403' => ['name' => '九十九里町', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12424' => ['name' => '白子町', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
    '12426' => ['name' => '長柄町', 'collector' => 'chiba_portal', 'sources' => $chibaPortal],
];
