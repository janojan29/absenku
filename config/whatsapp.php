<?php

return [
    'driver' => env('WHATSAPP_DRIVER', 'null'),

    'fonnte' => [
        'token' => env('WHATSAPP_FONNTE_TOKEN'),
        'base_url' => env('WHATSAPP_FONNTE_BASE_URL', 'https://api.fonnte.com'),
        'timeout' => (int) env('WHATSAPP_FONNTE_TIMEOUT', 20),
        'connect_timeout' => (int) env('WHATSAPP_FONNTE_CONNECT_TIMEOUT', 10),
    ],
];
