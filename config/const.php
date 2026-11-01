<?php

return [
    'ssl_use' => env('SSL_USE', false),
    'my_decay_minutes' => env('MY_DECAY_MINUTES', 10),
    'my_max_attempts' => env('MY_MAX_ATTEMPTS', 5),
    'dev_admin_password' => env('DEV_ADMIN_PASSWORD'),
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

