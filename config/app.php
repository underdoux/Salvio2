<?php

return [
    // Application Settings
    'app_name' => 'POS Pharma',
    'app_version' => '1.0.0',
    'base_url' => 'http://localhost/Salvio2',
    'timezone' => 'Asia/Jakarta',
    'locale' => 'id_ID',
    'currency' => 'IDR',

    // Security Settings
    'session_lifetime' => 7200, // 2 hours
    'password_min_length' => 6,
    'max_login_attempts' => 5,
    'lockout_time' => 900, // 15 minutes

    // Email Settings
    'mail' => [
        'from_address' => 'noreply@example.com',
        'from_name' => 'POS Pharma',
        'smtp_host' => 'smtp.mailtrap.io',
        'smtp_port' => 2525,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'tls'
    ],

    // WhatsApp Settings
    'whatsapp' => [
        'enabled' => false,
        'api_url' => '',
        'api_key' => ''
    ],

    // BPOM Settings
    'bpom' => [
        'api_url' => 'https://cekbpom.pom.go.id',
        'scraper_interval' => 86400, // 24 hours
        'cache_lifetime' => 604800 // 7 days
    ],

    // Order Settings
    'order' => [
        'tax_percentage' => 11,
        'max_discount_percentage' => 20,
        'auto_cancel_after' => 86400, // 24 hours
        'notification_intervals' => [1, 24, 48] // hours
    ],

    // Commission Settings
    'commission' => [
        'default_rate' => 5, // percentage
        'min_amount' => 1000, // IDR
        'max_amount' => 1000000, // IDR
        'payment_schedule' => 'monthly' // daily, weekly, monthly
    ],

    // Profit Sharing Settings
    'profit_sharing' => [
        'calculation_period' => 'monthly', // daily, weekly, monthly
        'distribution_day' => 1, // 1-31 for monthly, 1-7 for weekly
        'minimum_profit' => 1000000 // IDR
    ],

    // Stock Settings
    'stock' => [
        'low_stock_threshold' => 10,
        'notification_threshold' => 20,
        'expiry_warning_days' => 90
    ],

    // Report Settings
    'report' => [
        'export_formats' => ['pdf', 'excel', 'csv'],
        'auto_generate' => true,
        'retention_days' => 90
    ],

    // Backup Settings
    'backup' => [
        'enabled' => true,
        'schedule' => '0 0 * * *', // Daily at midnight
        'retention_copies' => 7,
        'storage_path' => 'storage/backups'
    ],

    // Cache Settings
    'cache' => [
        'driver' => 'file', // file, redis, memcached
        'lifetime' => 3600, // 1 hour
        'prefix' => 'pos_pharma_'
    ],

    // Debug Settings
    'debug' => true,
    'log_level' => 'debug', // debug, info, warning, error
    'log_path' => 'storage/logs'
];
