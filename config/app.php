<?php
return [
    // Application settings
    'app_name' => 'Salvio Pharmacy POS',
    'app_version' => '1.0.0',
    'debug' => true,
    'timezone' => 'Asia/Jakarta',
    'base_path' => '/Salvio2',

    // Database configuration
    'db' => [
        'host' => 'localhost',
        'name' => 'pos_pharma',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4'
    ],

    // Email configuration
    'mail' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => '',
        'password' => '',
        'encryption' => 'tls',
        'from_address' => 'noreply@salviopharmacy.com',
        'from_name' => 'Salvio Pharmacy'
    ],

    // WhatsApp configuration
    'whatsapp' => [
        'enabled' => false,
        'api_url' => '',
        'api_key' => ''
    ],

    // BPOM configuration
    'bpom' => [
        'api_url' => 'https://cekbpom.pom.go.id',
        'scraper_interval' => '0 0 * * *', // Daily at midnight
        'data_path' => __DIR__ . '/../data/bpom_data.json'
    ],

    // Session configuration
    'session' => [
        'lifetime' => 7200, // 2 hours
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true
    ],

    // Security settings
    'security' => [
        'password_min_length' => 8,
        'password_requires_special' => true,
        'password_requires_number' => true,
        'password_requires_uppercase' => true,
        'max_login_attempts' => 5,
        'lockout_time' => 900 // 15 minutes
    ],

    // Backup configuration
    'backup' => [
        'enabled' => true,
        'schedule' => '0 0 * * *', // Daily at midnight
        'retention_days' => 30,
        'path' => __DIR__ . '/../storage/backups'
    ],

    // Notification settings
    'notifications' => [
        'email' => [
            'enabled' => false,
            'queue' => true
        ],
        'whatsapp' => [
            'enabled' => false,
            'queue' => true
        ],
        'system' => [
            'enabled' => true,
            'retention_days' => 30
        ]
    ],

    // Commission settings
    'commission' => [
        'default_rate' => 2.5, // 2.5%
        'min_amount' => 0,
        'max_amount' => null,
        'requires_approval' => true
    ],

    // Stock settings
    'stock' => [
        'low_stock_threshold' => 10,
        'enable_notifications' => true,
        'track_batch_numbers' => true,
        'track_expiry_dates' => true
    ],

    // Order settings
    'order' => [
        'tax_percentage' => 11, // 11% VAT
        'enable_discounts' => true,
        'max_discount_percentage' => 20,
        'requires_approval_above' => 1000000 // Rp 1,000,000
    ],

    // Report settings
    'reports' => [
        'default_date_range' => 30, // Last 30 days
        'cache_duration' => 3600, // 1 hour
        'export_formats' => ['pdf', 'excel', 'csv']
    ],

    // Logging settings
    'logging' => [
        'enabled' => true,
        'path' => __DIR__ . '/../storage/logs',
        'level' => 'debug',
        'days_to_keep' => 30
    ],

    // Cache settings
    'cache' => [
        'driver' => 'file',
        'path' => __DIR__ . '/../storage/cache',
        'default_ttl' => 3600 // 1 hour
    ]
];
