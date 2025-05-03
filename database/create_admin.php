<?php

$config = require_once __DIR__ . '/../config/database.php';

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $db = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    // Create admin user if it doesn't exist
    $sql = "INSERT IGNORE INTO users 
            (username, password, role, status) 
            VALUES 
            ('admin', :password, 'admin', 1)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        'password' => password_hash('admin123', PASSWORD_DEFAULT)
    ]);
    
    echo "Admin user created/updated successfully!\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    
} catch (PDOException $e) {
    die("Failed to create admin user: " . $e->getMessage() . "\n");
}
