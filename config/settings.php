<?php

return [
    'currency' => [
        'code' => 'IDR',
        'symbol' => 'Rp',
        'decimal_separator' => ',',
        'thousand_separator' => '.',
        'decimal_places' => 0, // IDR typically doesn't use decimal places
    ],
    
    'locale' => [
        'timezone' => 'Asia/Jakarta',
        'date_format' => 'Y-m-d',
        'datetime_format' => 'Y-m-d H:i:s'
    ],
    
    'system' => [
        'name' => 'POS & Pharmaceutical Distribution Management System',
        'version' => '1.0.0'
    ],

    'mail' => [
        'from_address' => 'noreply@example.com',
        'reply_to' => 'support@example.com',
        'smtp' => [
            'host' => 'smtp.example.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'your-smtp-username',
            'password' => 'your-smtp-password'
        ]
    ]
];
