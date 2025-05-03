<?php

require_once __DIR__ . '/../app/models/ProfitSharing.php';
require_once __DIR__ . '/../app/helpers/Logger.php';

class MonthlyProfitCalculator {
    private $profitSharing;
    private $logger;

    public function __construct() {
        global $db;
        $this->profitSharing = new ProfitSharing();
        $this->logger = new Logger();
    }

    public function calculate() {
        try {
            $this->logger->log("Starting monthly profit calculation...");

            // Get last month's date range
            $lastMonth = date('Y-m', strtotime('-1 month'));
            $startDate = date('Y-m-01', strtotime('-1 month'));
            $endDate = date('Y-m-t', strtotime('-1 month'));

            // Check if calculation already exists
            if ($this->profitSharing->isProfitCalculated($lastMonth)) {
                $this->logger->log("Profit already calculated for {$lastMonth}");
                return;
            }

            // Calculate total sales
            $sales = $this->calculateTotalSales($startDate, $endDate);
            
            // Calculate total costs
            $costs = $this->calculateTotalCosts($startDate, $endDate);
            
            // Calculate total commissions
            $commissions = $this->calculateTotalCommissions($startDate, $endDate);
            
            // Calculate total expenses
            $expenses = $this->calculateTotalExpenses($startDate, $endDate);
            
            // Calculate net profit
            $netProfit = $sales - $costs - $commissions - $expenses;

            // Save profit calculation
            $profitId = $this->profitSharing->saveProfitCalculation([
                'month' => $lastMonth,
                'total_sales' => $sales,
                'total_costs' => $costs,
                'total_commissions' => $commissions,
                'total_expenses' => $expenses,
                'net_profit' => $netProfit,
                'status' => 'draft',
                'calculation_date' => date('Y-m-d H:i:s')
            ]);

            // Calculate investor distributions
            $this->calculateInvestorDistributions($profitId, $netProfit);

            $this->logger->log("Monthly profit calculation completed successfully for {$lastMonth}");
            $this->logger->log("Net Profit: " . number_format($netProfit, 2));

        } catch (Exception $e) {
            $this->logger->log("Error calculating monthly profits: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    private function calculateTotalSales($startDate, $endDate) {
        $sql = "SELECT COALESCE(SUM(total_amount), 0) as total
                FROM orders 
                WHERE order_date BETWEEN ? AND ?
                AND status = 'completed'";
        
        $result = $this->profitSharing->db->query($sql, [$startDate, $endDate])->fetch();
        return floatval($result['total']);
    }

    private function calculateTotalCosts($startDate, $endDate) {
        $sql = "SELECT COALESCE(SUM(oi.quantity * p.cost_price), 0) as total
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.order_date BETWEEN ? AND ?
                AND o.status = 'completed'";
        
        $result = $this->profitSharing->db->query($sql, [$startDate, $endDate])->fetch();
        return floatval($result['total']);
    }

    private function calculateTotalCommissions($startDate, $endDate) {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total
                FROM commissions
                WHERE created_at BETWEEN ? AND ?
                AND status = 'paid'";
        
        $result = $this->profitSharing->db->query($sql, [$startDate, $endDate])->fetch();
        return floatval($result['total']);
    }

    private function calculateTotalExpenses($startDate, $endDate) {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total
                FROM expenses
                WHERE expense_date BETWEEN ? AND ?
                AND status = 'approved'";
        
        $result = $this->profitSharing->db->query($sql, [$startDate, $endDate])->fetch();
        return floatval($result['total']);
    }

    private function calculateInvestorDistributions($profitId, $netProfit) {
        // Get active investors and their percentages
        $sql = "SELECT id, name, percentage 
                FROM investors 
                WHERE status = 'active'";
        
        $investors = $this->profitSharing->db->query($sql)->fetchAll();
        
        foreach ($investors as $investor) {
            $amount = ($netProfit * $investor['percentage']) / 100;
            
            // Save distribution record
            $this->profitSharing->saveDistribution([
                'profit_id' => $profitId,
                'investor_id' => $investor['id'],
                'percentage' => $investor['percentage'],
                'amount' => $amount,
                'status' => 'pending'
            ]);
        }
    }
}

// Run the calculation
try {
    $calculator = new MonthlyProfitCalculator();
    $calculator->calculate();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
