<?php
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Read and execute migrations
    $migrations = file_get_contents(__DIR__ . '/../migrations/create_tables_sqlite.sql');
    $conn->exec($migrations);
    
    // Create admin role
    $stmt = $conn->prepare("INSERT INTO roles (name) VALUES (:name)");
    $roleName = "admin";
    $stmt->bindParam(':name', $roleName);
    $stmt->execute();
    
    // Create admin user
    $stmt = $conn->prepare("INSERT INTO users (username, password, role_id) VALUES (:username, :password, :role_id)");
    $username = "admin";
    $password = password_hash("admin123", PASSWORD_DEFAULT);
    $roleId = 1; // admin role id
    
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':role_id', $roleId);
    $stmt->execute();
    
    echo "Database initialized successfully!\n";
    echo "Admin user created with:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
