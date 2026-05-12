<?php

return [
    'shipping_origin_code' => env('SHIPPING_ORIGIN_CODE', '32.73.14.1002'),
    'api_kurir' => [
        'username' => env('API_KURIR_USERNAME'),
        'password' => env('API_KURIR_PASSWORD'),
    ],
    'biteship' => [
        'api_key' => env('BITESHIP_API_KEY_TEST'),
        // 'api_key' => env('BITESHIP_API_KEY_LIVE'),
    ]
];
