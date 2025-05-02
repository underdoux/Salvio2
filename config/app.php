<?php
return [
    'app_name' => 'POS Pharma',
    'app_version' => '1.0.0',
    'app_url' => 'http://localhost/Salvio2',
    'timezone' => 'Asia/Jakarta',
    'locale' => 'id',
    'currency' => 'IDR',
    'date_format' => 'Y-m-d',
    'time_format' => 'H:i:s',
    'datetime_format' => 'Y-m-d H:i:s',
    'decimal_separator' => ',',
    'thousand_separator' => '.',
    'decimal_places' => 2,
    'tax_percentage' => 11,
    'max_discount_percentage' => 10,
    'default_commission_rate' => 5,
    'min_commission_amount' => 1000,
    'max_commission_amount' => 1000000,
    'notification' => [
        'email' => [
            'enabled' => true,
            'from_address' => 'noreply@pospharma.com',
            'from_name' => 'POS Pharma System'
        ],
        'whatsapp' => [
            'enabled' => true,
            'api_url' => 'https://api.whatsapp.com/v1',
            'api_key' => 'your-api-key-here'
        ]
    ],
    'bpom' => [
        'api_url' => 'https://cekbpom.pom.go.id/api',
        'scraper_interval' => '1 day',
        'cache_duration' => '7 days'
    ],
    'backup' => [
        'enabled' => true,
        'interval' => '1 day',
        'retention_days' => 30,
        'path' => '../storage/backups'
    ],
    'debug' => true,
    'log_level' => 'debug',
    'log_path' => '../storage/logs'
];
