<?php

// Load database configuration
$config = require_once __DIR__ . '/../config/database.php';

try {
    // Create database connection
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $db = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    // Create monthly_profits table
    $sql = "CREATE TABLE IF NOT EXISTS monthly_profits (
        id INT PRIMARY KEY AUTO_INCREMENT,
        month DATE NOT NULL,
        total_sales DECIMAL(10,2) NOT NULL DEFAULT 0,
        total_costs DECIMAL(10,2) NOT NULL DEFAULT 0,
        total_commissions DECIMAL(10,2) NOT NULL DEFAULT 0,
        total_expenses DECIMAL(10,2) NOT NULL DEFAULT 0,
        net_profit DECIMAL(10,2) NOT NULL DEFAULT 0,
        status ENUM('draft', 'final') NOT NULL DEFAULT 'draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_month (month)
    )";
    $db->exec($sql);
    echo "Created monthly_profits table\n";

    // Create profit_distributions table
    $sql = "CREATE TABLE IF NOT EXISTS profit_distributions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        profit_id INT NOT NULL,
        investor_id INT NOT NULL,
        percentage DECIMAL(5,2) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'approved', 'paid') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (profit_id) REFERENCES monthly_profits(id) ON DELETE CASCADE,
        FOREIGN KEY (investor_id) REFERENCES investors(id) ON DELETE CASCADE,
        CHECK (percentage >= 0 AND percentage <= 100)
    )";
    $db->exec($sql);
    echo "Created profit_distributions table\n";

    // Insert sample data
    $sql = "INSERT IGNORE INTO monthly_profits 
            (month, total_sales, total_costs, total_commissions, total_expenses, net_profit, status) 
            VALUES 
            ('2024-01-01', 100000.00, 60000.00, 5000.00, 10000.00, 25000.00, 'final'),
            ('2024-02-01', 120000.00, 70000.00, 6000.00, 12000.00, 32000.00, 'final'),
            ('2024-03-01', 110000.00, 65000.00, 5500.00, 11000.00, 28500.00, 'draft')";
    $db->exec($sql);
    echo "Inserted sample data\n";

    echo "Tables created successfully!\n";
    
} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage() . "\n");
}
