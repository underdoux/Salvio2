<?php

// Load database configuration
$config = require_once __DIR__ . '/../config/database.php';

try {
    // Create database connection
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $db = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    // Check commission_rates table
    $sql = "SHOW COLUMNS FROM commission_rates";
    $stmt = $db->query($sql);
    echo "commission_rates table structure:\n";
    echo str_repeat('-', 50) . "\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Field: {$row['Field']}\n";
        echo "Type: {$row['Type']}\n";
        echo "Null: {$row['Null']}\n";
        echo "Key: {$row['Key']}\n";
        echo "Default: {$row['Default']}\n";
        echo "Extra: {$row['Extra']}\n";
        echo str_repeat('-', 50) . "\n";
    }

    // Check if there are any records
    $sql = "SELECT * FROM commission_rates";
    $stmt = $db->query($sql);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\ncommission_rates records:\n";
    echo str_repeat('-', 50) . "\n";
    foreach ($records as $record) {
        print_r($record);
        echo str_repeat('-', 50) . "\n";
    }
    
} catch (PDOException $e) {
    die("Failed to check tables: " . $e->getMessage() . "\n");
}
