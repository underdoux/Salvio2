<?php

$config = require_once __DIR__ . '/../config/database.php';

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $db = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    // First, create test investors if they don't exist
    $sql = "INSERT IGNORE INTO investors 
            (name, capital_amount, percentage, status) 
            VALUES 
            ('John Doe', 1000000.00, 60.00, 1),
            ('Jane Smith', 800000.00, 40.00, 1)";
    $db->exec($sql);
    echo "Created test investors\n";

    // Insert profit record for February
    $sql = "INSERT INTO monthly_profits 
            (period, total_sales, total_product_cost, total_commissions, total_expenses, net_profit, status)
            VALUES 
            (:period, :sales, :cost, :comm, :exp, :profit, :status)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        'period' => '2024-02-01',
        'sales' => 120000.00,
        'cost' => 70000.00,
        'comm' => 6000.00,
        'exp' => 12000.00,
        'profit' => 32000.00,
        'status' => 'final'
    ]);
    
    $profitId = $db->lastInsertId();
    echo "Inserted profit record with ID: $profitId\n";

    // Get investor IDs
    $sql = "SELECT id, percentage FROM investors WHERE status = 1 ORDER BY id LIMIT 2";
    $investors = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // Insert distributions
    foreach ($investors as $investor) {
        $amount = (32000.00 * $investor['percentage']) / 100;
        
        $sql = "INSERT INTO profit_distributions 
                (profit_id, investor_id, percentage, amount, status)
                VALUES 
                (:profit_id, :investor_id, :percentage, :amount, :status)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'profit_id' => $profitId,
            'investor_id' => $investor['id'],
            'percentage' => $investor['percentage'],
            'amount' => $amount,
            'status' => 'paid'
        ]);
        
        echo "Inserted distribution for investor {$investor['id']}\n";
    }
    
    echo "Sample data inserted successfully!\n";
    
} catch (PDOException $e) {
    die("Failed to insert sample data: " . $e->getMessage() . "\n");
}
