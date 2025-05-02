<?php

$config = [
    'host' => 'localhost',
    'dbname' => 'salvio_pos',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];

try {
    // Create connection without database
    $pdo = new PDO(
        "mysql:host={$config['host']}", 
        $config['username'], 
        $config['password'], 
        $config['options']
    );

    // Create database if not exists
    $pdo->exec("DROP DATABASE IF EXISTS {$config['dbname']}");
    $pdo->exec("CREATE DATABASE {$config['dbname']} CHARACTER SET utf8 COLLATE utf8_unicode_ci");
    echo "Database '{$config['dbname']}' created successfully.\n";

    // Select the database
    $pdo->exec("USE {$config['dbname']}");

    // Run migrations in order
    $migrations = [
        '001_initial_schema.sql',
        '002_create_investors.sql',
        '003_create_products.sql',
        '004_create_orders.sql',
        '005_create_profit_sharing.sql',
        '006_create_notifications.sql'
    ];

    foreach ($migrations as $migration) {
        $sql = file_get_contents(__DIR__ . '/migrations/' . $migration);
        $pdo->exec($sql);
        echo "Migration {$migration} executed successfully.\n";
    }

    // Run seeders
    $seeders = [
        'initial_data.sql'
    ];

    foreach ($seeders as $seeder) {
        $sql = file_get_contents(__DIR__ . '/seeders/' . $seeder);
        $pdo->exec($sql);
        echo "Seeder {$seeder} executed successfully.\n";
    }

    echo "Database setup completed successfully.\n";

} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage() . "\n");
}
