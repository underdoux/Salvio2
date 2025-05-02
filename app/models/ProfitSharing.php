<?php

class ProfitSharing extends BaseModel {
    protected $table = 'monthly_profits';

    public function calculateMonthlyProfit($period) {
        try {
            $this->db->beginTransaction();

            // Get start and end date of the period
            $startDate = date('Y-m-01', strtotime($period));
            $endDate = date('Y-m-t', strtotime($period));

            // Calculate total sales from paid orders
            $totalSales = $this->calculateTotalSales($startDate, $endDate);
            
            // Calculate total product costs
            $totalProductCost = $this->calculateTotalProductCost($startDate, $endDate);
            
            // Calculate total operational expenses
            $totalExpenses = $this->calculateTotalExpenses($startDate, $endDate);
            
            // Calculate total commissions
            $totalCommissions = $this->calculateTotalCommissions($startDate, $endDate);
            
            // Calculate net profit
            $netProfit = $totalSales - $totalProductCost - $totalExpenses - $totalCommissions;

            // Save or update monthly profit record
            $monthlyProfitId = $this->saveMonthlyProfit([
                'period' => $startDate,
                'total_sales' => $totalSales,
                'total_product_cost' => $totalProductCost,
                'total_expenses' => $totalExpenses,
                'total_commissions' => $totalCommissions,
                'net_profit' => $netProfit
            ]);

            // Log the calculation
            $this->logCalculation($monthlyProfitId, 'calculate', [
                'total_sales' => $totalSales,
                'total_product_cost' => $totalProductCost,
                'total_expenses' => $totalExpenses,
                'total_commissions' => $totalCommissions,
                'net_profit' => $netProfit
            ]);

            $this->db->commit();
            return $monthlyProfitId;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function distributeProfit($monthlyProfitId) {
        try {
            $this->db->beginTransaction();

            $monthlyProfit = $this->find($monthlyProfitId);
            if (!$monthlyProfit || $monthlyProfit['status'] !== 'final') {
                throw new Exception("Monthly profit record not found or not finalized");
            }

            // Get active investors
            $investor = new Investor();
            $investors = $investor->getAll();

            // Delete existing distributions for this profit record
            $sql = "DELETE FROM profit_distribution WHERE monthly_profit_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$monthlyProfitId]);

            // Create new distributions
            foreach ($investors as $investor) {
                $amount = ($monthlyProfit['net_profit'] * $investor['percentage']) / 100;

                $sql = "INSERT INTO profit_distribution 
                        (monthly_profit_id, investor_id, percentage, amount) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $monthlyProfitId,
                    $investor['id'],
                    $investor['percentage'],
                    $amount
                ]);
            }

            // Log the distribution
            $this->logCalculation($monthlyProfitId, 'distribute', [
                'total_distributed' => $monthlyProfit['net_profit']
            ]);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function finalizeProfit($monthlyProfitId) {
        $sql = "UPDATE {$this->table} SET status = 'final' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$monthlyProfitId]);
    }

    public function getMonthlyProfits($filters = []) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($filters['year'])) {
            $sql .= " WHERE YEAR(period) = ?";
            $params[] = $filters['year'];
        }

        $sql .= " ORDER BY period DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDistributionDetails($monthlyProfitId) {
        $sql = "SELECT 
                    pd.*,
                    i.name as investor_name
                FROM profit_distribution pd
                JOIN investors i ON pd.investor_id = i.id
                WHERE pd.monthly_profit_id = ?
                ORDER BY i.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$monthlyProfitId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function calculateTotalSales($startDate, $endDate) {
        $sql = "SELECT COALESCE(SUM(total_amount), 0) as total 
                FROM orders 
                WHERE status = 'paid' 
                AND DATE(created_at) BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    private function calculateTotalProductCost($startDate, $endDate) {
        $sql = "SELECT COALESCE(SUM(oi.quantity * p.purchase_price), 0) as total
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN products p ON oi.product_id = p.id
                WHERE o.status = 'paid'
                AND DATE(o.created_at) BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    private function calculateTotalExpenses($startDate, $endDate) {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                FROM operational_expenses 
                WHERE DATE(expense_date) BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    private function calculateTotalCommissions($startDate, $endDate) {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                FROM sales_commissions sc
                JOIN orders o ON sc.order_id = o.id
                WHERE sc.status = 'paid'
                AND DATE(o.created_at) BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    private function saveMonthlyProfit($data) {
        // Check if record exists for this period
        $sql = "SELECT id FROM {$this->table} WHERE period = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$data['period']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Update existing record
            $sql = "UPDATE {$this->table} 
                    SET total_sales = ?,
                        total_product_cost = ?,
                        total_expenses = ?,
                        total_commissions = ?,
                        net_profit = ?,
                        status = 'draft'
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['total_sales'],
                $data['total_product_cost'],
                $data['total_expenses'],
                $data['total_commissions'],
                $data['net_profit'],
                $existing['id']
            ]);
            return $existing['id'];
        } else {
            // Insert new record
            $sql = "INSERT INTO {$this->table} 
                    (period, total_sales, total_product_cost, total_expenses, 
                     total_commissions, net_profit) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['period'],
                $data['total_sales'],
                $data['total_product_cost'],
                $data['total_expenses'],
                $data['total_commissions'],
                $data['net_profit']
            ]);
            return $this->db->lastInsertId();
        }
    }

    private function logCalculation($monthlyProfitId, $action, $details) {
        $sql = "INSERT INTO profit_calculation_logs 
                (monthly_profit_id, action, details, created_by) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $monthlyProfitId,
            $action,
            json_encode($details),
            $_SESSION['user']['id']
        ]);
    }
}
