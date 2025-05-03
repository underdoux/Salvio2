<?php

try {
    // Default configuration if config file fails
    $defaultConfig = [
        'host' => 'localhost',
        'dbname' => 'salvio_pos',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ];

    // Try to load config file
    $configFile = __DIR__ . '/../config/database.php';
    $config = file_exists($configFile) ? require $configFile : $defaultConfig;
    
    // First connect without database name
    $pdo = new PDO(
        "mysql:host={$config['host']}",
        $config['username'],
        $config['password'],
        $config['options']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if not exists
    $sql = "CREATE DATABASE IF NOT EXISTS {$config['dbname']} DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "Database '{$config['dbname']}' created or already exists.\n";

    // Connect to the database
    $pdo->exec("USE {$config['dbname']}");

    // Disable foreign key checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    echo "Disabled foreign key checks.\n";

    // Drop existing tables
    $tables = [
        'notifications',
        'commission_payments',
        'sales_commissions',
        'profit_distributions',
        'monthly_profits',
        'order_items',
        'orders',
        'users'
    ];

    foreach ($tables as $table) {
        $sql = "DROP TABLE IF EXISTS {$table}";
        $pdo->exec($sql);
        echo "Dropped table {$table} if it existed.\n";
    }

    // Re-enable foreign key checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    echo "Re-enabled foreign key checks.\n";

    // Create users table
    $sql = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        role ENUM('admin', 'sales', 'cashier') NOT NULL,
        status BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (username),
        INDEX (email),
        INDEX (role)
    ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "Users table created.\n";

    // Create notifications table
    $sql = "CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        reference_type VARCHAR(50),
        reference_id INT,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (type),
        INDEX (reference_type, reference_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "Notifications table created.\n";

    // Create default admin user
    $sql = "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'admin',
        password_hash('admin123', PASSWORD_DEFAULT),
        'admin@example.com',
        'admin'
    ]);
    echo "Default admin user created.\n";

    echo "Database setup completed successfully.\n";

} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage() . "\n");
} finally {
    // Make sure foreign key checks are re-enabled even if an error occurs
    if (isset($pdo)) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}
