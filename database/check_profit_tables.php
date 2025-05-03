<?php

$config = require_once __DIR__ . '/../config/database.php';

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $db = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    // Check monthly_profits table
    echo "Checking monthly_profits table:\n";
    echo str_repeat('-', 50) . "\n";
    
    $sql = "DESCRIBE monthly_profits";
    $result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    echo "Table structure:\n";
    foreach ($result as $column) {
        echo "{$column['Field']}: {$column['Type']} {$column['Null']} {$column['Key']}\n";
    }
    
    $sql = "SELECT COUNT(*) as count FROM monthly_profits";
    $count = $db->query($sql)->fetch(PDO::FETCH_ASSOC)['count'];
    echo "\nTotal records: $count\n";
    
    if ($count > 0) {
        $sql = "SELECT * FROM monthly_profits LIMIT 1";
        $record = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
        echo "\nSample record:\n";
        print_r($record);
    }
    
    echo "\n" . str_repeat('-', 50) . "\n";
    
    // Check profit_distributions table
    echo "\nChecking profit_distributions table:\n";
    echo str_repeat('-', 50) . "\n";
    
    $sql = "DESCRIBE profit_distributions";
    $result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    echo "Table structure:\n";
    foreach ($result as $column) {
        echo "{$column['Field']}: {$column['Type']} {$column['Null']} {$column['Key']}\n";
    }
    
    $sql = "SELECT COUNT(*) as count FROM profit_distributions";
    $count = $db->query($sql)->fetch(PDO::FETCH_ASSOC)['count'];
    echo "\nTotal records: $count\n";
    
    if ($count > 0) {
        $sql = "SELECT * FROM profit_distributions LIMIT 1";
        $record = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
        echo "\nSample record:\n";
        print_r($record);
    }
    
    echo "\n" . str_repeat('-', 50) . "\n";
    
    // Check investors table
    echo "\nChecking investors table:\n";
    echo str_repeat('-', 50) . "\n";
    
    $sql = "DESCRIBE investors";
    $result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    echo "Table structure:\n";
    foreach ($result as $column) {
        echo "{$column['Field']}: {$column['Type']} {$column['Null']} {$column['Key']}\n";
    }
    
    $sql = "SELECT COUNT(*) as count FROM investors";
    $count = $db->query($sql)->fetch(PDO::FETCH_ASSOC)['count'];
    echo "\nTotal records: $count\n";
    
    if ($count > 0) {
        $sql = "SELECT * FROM investors LIMIT 1";
        $record = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
        echo "\nSample record:\n";
        print_r($record);
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}
