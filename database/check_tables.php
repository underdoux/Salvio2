<?php

// Load database configuration
$config = require_once __DIR__ . '/../config/database.php';

try {
    // Create database connection
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $db = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    // Check monthly_profits table
    $sql = "SHOW COLUMNS FROM monthly_profits";
    $stmt = $db->query($sql);
    echo "monthly_profits table structure:\n";
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

    // Check profit_distributions table
    $sql = "SHOW COLUMNS FROM profit_distributions";
    $stmt = $db->query($sql);
    echo "\nprofit_distributions table structure:\n";
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
    
} catch (PDOException $e) {
    die("Failed to check tables: " . $e->getMessage() . "\n");
}
