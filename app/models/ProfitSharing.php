<?php

class ProfitSharing extends BaseModel {
    private $table = 'monthly_profits';

    public function __construct() {
        parent::__construct();
    }

    /**
     * Calculate monthly profit
     */
    public function calculateMonthlyProfit($year, $month) {
        try {
            $this->db->beginTransaction();

            // Check if calculation already exists
            $existing = $this->getMonthlyProfit($year, $month);
            if ($existing && $existing['status'] === 'finalized') {
                throw new Exception("Profit calculation for {$year}-{$month} is already finalized");
            }

            // Calculate total sales
            $totalSales = $this->calculateTotalSales($year, $month);
            
            // Calculate total costs
            $totalCosts = $this->calculateTotalCosts($year, $month);
            
            // Calculate total expenses
            $totalExpenses = $this->calculateTotalExpenses($year, $month);
            
            // Calculate total commissions
            $totalCommissions = $this->calculateTotalCommissions($year, $month);
            
            // Calculate net profit
            $netProfit = $totalSales - $totalCosts - $totalExpenses - $totalCommissions;

            // Save or update calculation
            if ($existing) {
                $sql = "UPDATE {$this->table} SET 
                        total_sales = ?, total_costs = ?, total_expenses = ?,
                        total_commissions = ?, net_profit = ?
                        WHERE id = ?";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $totalSales,
                    $totalCosts,
                    $totalExpenses,
                    $totalCommissions,
                    $netProfit,
                    $existing['id']
                ]);

                $profitId = $existing['id'];
            } else {
                $sql = "INSERT INTO {$this->table} 
                        (year, month, total_sales, total_costs, total_expenses,
                         total_commissions, net_profit)
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $year,
                    $month,
                    $totalSales,
                    $totalCosts,
                    $totalExpenses,
                    $totalCommissions,
                    $netProfit
                ]);

                $profitId = $this->db->lastInsertId();
            }

            // Log calculation details
            $this->logCalculation($profitId, "Calculated profit for {$year}-{$month}:
                Total Sales: {$totalSales}
                Total Costs: {$totalCosts}
                Total Expenses: {$totalExpenses}
                Total Commissions: {$totalCommissions}
                Net Profit: {$netProfit}");

            $this->db->commit();
            return $profitId;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error calculating monthly profit: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Finalize monthly profit
     */
    public function finalizeMonthlyProfit($id) {
        try {
            $this->db->beginTransaction();

            $profit = $this->getMonthlyProfitById($id);
            if (!$profit) {
                throw new Exception("Profit calculation not found");
            }

            if ($profit['status'] === 'finalized') {
                throw new Exception("Profit calculation is already finalized");
            }

            // Calculate distributions
            $investors = $this->getActiveInvestors();
            $totalPercentage = 0;
            $distributions = [];

            foreach ($investors as $investor) {
                $totalPercentage += $investor['percentage'];
                $amount = ($profit['net_profit'] * $investor['percentage']) / 100;
                $distributions[] = [
                    'investor_id' => $investor['id'],
                    'amount' => $amount
                ];
            }

            if (abs($totalPercentage - 100) > 0.01) {
                throw new Exception("Total investor percentages must equal 100%");
            }

            // Save distributions
            $sql = "INSERT INTO profit_distribution 
                    (monthly_profit_id, investor_id, distribution_amount)
                    VALUES (?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            foreach ($distributions as $dist) {
                $stmt->execute([$id, $dist['investor_id'], $dist['amount']]);
            }

            // Update profit status
            $sql = "UPDATE {$this->table} SET status = 'finalized' WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);

            $this->logCalculation($id, "Finalized profit calculation and created distributions");

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error finalizing monthly profit: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Process profit distribution payment
     */
    public function processDistributionPayment($distributionId, $paymentData) {
        try {
            $sql = "UPDATE profit_distribution 
                    SET status = 'paid', payment_date = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND status = 'pending'";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$paymentData['payment_date'], $distributionId]);
        } catch (Exception $e) {
            Logger::log("Error processing distribution payment: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Get monthly profit by ID
     */
    public function getMonthlyProfitById($id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            Logger::log("Error fetching monthly profit: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Get monthly profit by year and month
     */
    public function getMonthlyProfit($year, $month) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE year = ? AND month = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$year, $month]);
            return $stmt->fetch();
        } catch (Exception $e) {
            Logger::log("Error fetching monthly profit: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Get profit distributions
     */
    public function getProfitDistributions($monthlyProfitId) {
        try {
            $sql = "SELECT pd.*, i.name as investor_name, i.percentage
                   FROM profit_distribution pd
                   LEFT JOIN investors i ON pd.investor_id = i.id
                   WHERE pd.monthly_profit_id = ?
                   ORDER BY i.name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$monthlyProfitId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            Logger::log("Error fetching profit distributions: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Get profit calculation logs
     */
    public function getCalculationLogs($monthlyProfitId) {
        try {
            $sql = "SELECT * FROM profit_calculation_logs 
                   WHERE monthly_profit_id = ?
                   ORDER BY created_at ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$monthlyProfitId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            Logger::log("Error fetching calculation logs: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Get all monthly profits with optional filtering
     */
    public function getAllMonthlyProfits($filters = [], $page = 1, $limit = 10) {
        try {
            $sql = "SELECT mp.*, 
                   COUNT(pd.id) as distribution_count,
                   SUM(CASE WHEN pd.status = 'paid' THEN 1 ELSE 0 END) as paid_count
                   FROM {$this->table} mp
                   LEFT JOIN profit_distribution pd ON mp.id = pd.monthly_profit_id
                   WHERE 1=1";
            
            $params = [];

            if (!empty($filters['year'])) {
                $sql .= " AND mp.year = ?";
                $params[] = $filters['year'];
            }

            if (!empty($filters['month'])) {
                $sql .= " AND mp.month = ?";
                $params[] = $filters['month'];
            }

            if (!empty($filters['status'])) {
                $sql .= " AND mp.status = ?";
                $params[] = $filters['status'];
            }

            $sql .= " GROUP BY mp.id ORDER BY mp.year DESC, mp.month DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = ($page - 1) * $limit;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            Logger::log("Error fetching monthly profits: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Calculate total sales for a month
     */
    private function calculateTotalSales($year, $month) {
        $sql = "SELECT COALESCE(SUM(total_amount), 0) as total
               FROM orders
               WHERE YEAR(created_at) = ?
               AND MONTH(created_at) = ?
               AND status = 'completed'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$year, $month]);
        return $stmt->fetch()['total'];
    }

    /**
     * Calculate total costs for a month
     */
    private function calculateTotalCosts($year, $month) {
        $sql = "SELECT COALESCE(SUM(oi.quantity * oi.unit_cost), 0) as total
               FROM order_items oi
               LEFT JOIN orders o ON oi.order_id = o.id
               WHERE YEAR(o.created_at) = ?
               AND MONTH(o.created_at) = ?
               AND o.status = 'completed'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$year, $month]);
        return $stmt->fetch()['total'];
    }

    /**
     * Calculate total expenses for a month
     */
    private function calculateTotalExpenses($year, $month) {
        // This would typically connect to an expenses tracking system
        // For now, returning 0 as placeholder
        return 0;
    }

    /**
     * Calculate total commissions for a month
     */
    private function calculateTotalCommissions($year, $month) {
        $sql = "SELECT COALESCE(SUM(commission_amount), 0) as total
               FROM sales_commissions sc
               WHERE YEAR(created_at) = ?
               AND MONTH(created_at) = ?
               AND status != 'cancelled'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$year, $month]);
        return $stmt->fetch()['total'];
    }

    /**
     * Get active investors
     */
    private function getActiveInvestors() {
        $sql = "SELECT * FROM investors WHERE status = 'active' ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Log calculation details
     */
    private function logCalculation($profitId, $message) {
        $sql = "INSERT INTO profit_calculation_logs (monthly_profit_id, log_message)
                VALUES (?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$profitId, $message]);
    }
}
