<?php

return [
    'setting' => [
        'max_csv_size' => 5120, // アップロードするCSVファイルサイズ
    ],
    'LATLNG' => [
        'LAT' => env('MAP_LAT', 37.10395738048938),
        'LNG' => env('MAP_LNG', 136.84652382789386),
    ],
    'MAP_PARAM' => [
        'ZOOM' => env('MAP_ZOOM', 10),
    ],
];

