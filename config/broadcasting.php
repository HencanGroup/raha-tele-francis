<?php

return [
    'pusher' => [
        'driver' => 'pusher',
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'app_id' => env('REVERB_APP_ID'),
        'options' => [
            'cluster' => env('REVERB_APP_CLUSTER', 'mt1'),
            'host' => env('REVERB_HOST', '127.0.0.1'),
            'port' => env('REVERB_PORT', 8080),
            'scheme' => 'http',
            'useTLS' => false,
            'encrypted' => false,
        ],
    ],

];