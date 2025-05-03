<?php

$config = require_once __DIR__ . '/../config/database.php';

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $db = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    // Check investors table structure
    $sql = "SHOW COLUMNS FROM investors";
    $stmt = $db->query($sql);
    echo "Investors table structure:\n";
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
    die("Failed to check table structure: " . $e->getMessage() . "\n");
}
