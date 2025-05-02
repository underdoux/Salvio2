<?php

$config = [
    'host' => 'localhost',
    'dbname' => 'salvio_pos',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',  // Changed from utf8mb4 to utf8
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", 
        $config['username'], 
        $config['password'], 
        $config['options']
    );

    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_BCRYPT);

    $sql = "UPDATE users SET password = ? WHERE username = 'admin'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$hash]);

    echo "Admin password updated successfully.\n";

} catch (PDOException $e) {
    die("Failed to update password: " . $e->getMessage() . "\n");
}
