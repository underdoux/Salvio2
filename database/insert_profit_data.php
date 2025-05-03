<?php

// Load database configuration
$config = require_once __DIR__ . '/../config/database.php';

try {
    // Create database connection
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $db = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    // Insert monthly profits
    $profits = [
        [
            'month' => '2024-01-01',
            'total_sales' => 100000.00,
            'total_costs' => 60000.00,
            'total_commissions' => 5000.00,
            'total_expenses' => 10000.00,
            'net_profit' => 25000.00,
            'status' => 'final'
        ],
        [
            'month' => '2024-02-01',
            'total_sales' => 120000.00,
            'total_costs' => 70000.00,
            'total_commissions' => 6000.00,
            'total_expenses' => 12000.00,
            'net_profit' => 32000.00,
            'status' => 'final'
        ],
        [
            'month' => '2024-03-01',
            'total_sales' => 110000.00,
            'total_costs' => 65000.00,
            'total_commissions' => 5500.00,
            'total_expenses' => 11000.00,
            'net_profit' => 28500.00,
            'status' => 'draft'
        ]
    ];

    foreach ($profits as $profit) {
        $sql = "INSERT INTO monthly_profits 
                (month, total_sales, total_costs, total_commissions, total_expenses, net_profit, status)
                SELECT :month, :total_sales, :total_costs, :total_commissions, :total_expenses, :net_profit, :status
                FROM dual
                WHERE NOT EXISTS (
                    SELECT 1 FROM monthly_profits WHERE month = :month
                )";
        $stmt = $db->prepare($sql);
        $stmt->execute($profit);
        if ($stmt->rowCount() > 0) {
            echo "Inserted profit record for {$profit['month']}\n";
        }
    }

    // Get January profit ID
    $sql = "SELECT id FROM monthly_profits WHERE month = '2024-01-01'";
    $januaryProfitId = $db->query($sql)->fetchColumn();

    if ($januaryProfitId) {
        // Insert distributions for investor 1 (60%)
        $sql = "INSERT INTO profit_distributions 
                (profit_id, investor_id, percentage, amount, status)
                SELECT :profit_id, :investor_id, :percentage, :amount, :status
                FROM dual
                WHERE EXISTS (SELECT 1 FROM investors WHERE id = :investor_id)
                AND NOT EXISTS (
                    SELECT 1 FROM profit_distributions 
                    WHERE profit_id = :profit_id AND investor_id = :investor_id
                )";
        
        $distribution = [
            'profit_id' => $januaryProfitId,
            'investor_id' => 1,
            'percentage' => 60.00,
            'amount' => 15000.00,
            'status' => 'paid'
        ];
        
        $stmt = $db->prepare($sql);
        $stmt->execute($distribution);
        if ($stmt->rowCount() > 0) {
            echo "Inserted distribution for investor 1\n";
        }

        // Insert distributions for investor 2 (40%)
        $distribution = [
            'profit_id' => $januaryProfitId,
            'investor_id' => 2,
            'percentage' => 40.00,
            'amount' => 10000.00,
            'status' => 'paid'
        ];
        
        $stmt = $db->prepare($sql);
        $stmt->execute($distribution);
        if ($stmt->rowCount() > 0) {
            echo "Inserted distribution for investor 2\n";
        }
    }

    echo "Sample data inserted successfully!\n";
    
} catch (PDOException $e) {
    die("Failed to insert sample data: " . $e->getMessage() . "\n");
}
