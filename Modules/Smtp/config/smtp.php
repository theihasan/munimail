<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMTP Server Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the SMTP server and email delivery settings.
    |
    */

    'dns' => [
        'server' => env('SMTP_DNS_SERVER', '8.8.8.8'),
    ],

    'client' => [
        'timeout' => env('SMTP_CLIENT_TIMEOUT', 10),
        'tls' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ],

    'delivery' => [
        'enabled' => env('SMTP_ENABLE_DELIVERY', false),
        'internal_domains' => array_map('trim', explode(',', env('SMTP_INTERNAL_DOMAINS', 'localhost,127.0.0.1'))),
    ],
]; 