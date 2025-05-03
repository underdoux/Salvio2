<?php

$config = require_once __DIR__ . '/../config/database.php';

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $db = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    // Insert a single profit record
    $sql = "INSERT INTO monthly_profits 
            (period, total_sales, total_product_cost, total_commissions, total_expenses, net_profit, status)
            VALUES 
            (:period, :sales, :cost, :comm, :exp, :profit, :status)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        'period' => '2024-01-01',
        'sales' => 100000.00,
        'cost' => 60000.00,
        'comm' => 5000.00,
        'exp' => 10000.00,
        'profit' => 25000.00,
        'status' => 'final'
    ]);
    
    $profitId = $db->lastInsertId();
    echo "Inserted profit record with ID: $profitId\n";

    // Insert distribution
    if ($profitId) {
        $sql = "INSERT INTO profit_distributions 
                (profit_id, investor_id, percentage, amount, status)
                VALUES 
                (:profit_id, :investor_id, :percentage, :amount, :status)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'profit_id' => $profitId,
            'investor_id' => 1,
            'percentage' => 60.00,
            'amount' => 15000.00,
            'status' => 'paid'
        ]);
        
        echo "Inserted distribution record\n";
    }
    
    echo "Sample data inserted successfully!\n";
    
} catch (PDOException $e) {
    die("Failed to insert sample data: " . $e->getMessage() . "\n");
}
