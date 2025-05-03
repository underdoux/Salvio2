<?php

require_once __DIR__ . '/Notification.php';

class ProfitSharing extends BaseModel {
    private $notification;

    public function __construct() {
        parent::__construct();
        $this->notification = new Notification();
    }

    public function getMonthlyProfits($filters = []) {
        $sql = "SELECT 
                    mp.*,
                    (mp.total_sales - mp.total_costs) as gross_profit,
                    ((mp.total_sales - mp.total_costs) / mp.total_sales * 100) as gross_margin,
                    (mp.net_profit / mp.total_sales * 100) as net_margin,
                    COUNT(pd.id) as total_distributions,
                    SUM(pd.amount) as total_distributed,
                    CASE 
                        WHEN mp.status = 'draft' THEN 'warning'
                        WHEN mp.status = 'final' THEN 'success'
                        ELSE 'secondary'
                    END as status_class
                FROM monthly_profits mp
                LEFT JOIN profit_distributions pd ON mp.id = pd.profit_id";

        $conditions = ["1=1"];
        $params = [];

        if (isset($filters['status'])) {
            $conditions[] = "mp.status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['year'])) {
            $conditions[] = "YEAR(mp.month) = ?";
            $params[] = $filters['year'];
        }

        $sql .= " WHERE " . implode(" AND ", $conditions);
        $sql .= " GROUP BY mp.id ORDER BY mp.month DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function calculateMonthlyProfit($month) {
        try {
            $this->db->beginTransaction();

            // Check if profit record already exists
            $sql = "SELECT id FROM monthly_profits WHERE month = ?";
            $existing = $this->db->query($sql, [$month])->fetch();
            if ($existing) {
                throw new Exception("Profit record already exists for this month");
            }

            // Get total sales
            $sql = "SELECT COALESCE(SUM(total_amount), 0) as total_sales 
                    FROM orders 
                    WHERE DATE_FORMAT(created_at, '%Y-%m') = ? 
                    AND status = 'completed'";
            $totalSales = $this->db->query($sql, [$month])->fetch()['total_sales'];

            // Get total costs (from order items)
            $sql = "SELECT COALESCE(SUM(oi.quantity * p.cost_price), 0) as total_costs
                    FROM orders o
                    JOIN order_items oi ON o.id = oi.order_id
                    JOIN products p ON oi.product_id = p.id
                    WHERE DATE_FORMAT(o.created_at, '%Y-%m') = ?
                    AND o.status = 'completed'";
            $totalCosts = $this->db->query($sql, [$month])->fetch()['total_costs'];

            // Get total commissions
            $sql = "SELECT COALESCE(SUM(amount), 0) as total_commissions
                    FROM sales_commissions
                    WHERE DATE_FORMAT(created_at, '%Y-%m') = ?";
            $totalCommissions = $this->db->query($sql, [$month])->fetch()['total_commissions'];

            // Get total expenses
            $sql = "SELECT COALESCE(SUM(amount), 0) as total_expenses
                    FROM expenses
                    WHERE DATE_FORMAT(date, '%Y-%m') = ?";
            $totalExpenses = $this->db->query($sql, [$month])->fetch()['total_expenses'];

            // Calculate net profit
            $netProfit = $totalSales - $totalCosts - $totalCommissions - $totalExpenses;

            // Insert profit record
            $sql = "INSERT INTO monthly_profits (
                        month,
                        total_sales,
                        total_costs,
                        total_commissions,
                        total_expenses,
                        net_profit,
                        status,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, 'draft', NOW())";

            $this->db->query($sql, [
                $month,
                $totalSales,
                $totalCosts,
                $totalCommissions,
                $totalExpenses,
                $netProfit
            ]);

            $profitId = $this->db->lastInsertId();

            // Calculate investor distributions
            $this->calculateInvestorDistributions($profitId, $netProfit);

            $this->db->commit();
            return $profitId;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function calculateInvestorDistributions($profitId, $netProfit) {
        // Get active investors
        $sql = "SELECT id, percentage FROM investors WHERE status = 'active'";
        $investors = $this->db->query($sql)->fetchAll();

        foreach ($investors as $investor) {
            $amount = ($netProfit * $investor['percentage']) / 100;

            $sql = "INSERT INTO profit_distributions (
                        profit_id,
                        investor_id,
                        percentage,
                        amount,
                        status,
                        created_at
                    ) VALUES (?, ?, ?, ?, 'pending', NOW())";

            $this->db->query($sql, [
                $profitId,
                $investor['id'],
                $investor['percentage'],
                $amount
            ]);
        }
    }

    public function finalizeProfitRecord($profitId) {
        try {
            $this->db->beginTransaction();

            // Get profit record
            $sql = "SELECT * FROM monthly_profits WHERE id = ?";
            $profit = $this->db->query($sql, [$profitId])->fetch();

            if (!$profit) {
                throw new Exception("Profit record not found");
            }

            if ($profit['status'] === 'final') {
                throw new Exception("Profit record is already finalized");
            }

            // Update profit status
            $sql = "UPDATE monthly_profits SET status = 'final' WHERE id = ?";
            $this->db->query($sql, [$profitId]);

            // Update distribution status to approved
            $sql = "UPDATE profit_distributions SET status = 'approved' WHERE profit_id = ?";
            $this->db->query($sql, [$profitId]);

            // Get distributions for notifications
            $sql = "SELECT pd.*, i.name, i.email 
                    FROM profit_distributions pd
                    JOIN investors i ON pd.investor_id = i.id
                    WHERE pd.profit_id = ?";
            $distributions = $this->db->query($sql, [$profitId])->fetchAll();

            // Send notifications
            foreach ($distributions as $dist) {
                $notificationData = [
                    'user_id' => $dist['investor_id'],
                    'type' => 'profit_distribution',
                    'title' => 'Profit Distribution Approved',
                    'message' => "Your profit share of ₱" . number_format($dist['amount'], 2) . 
                               " (" . $dist['percentage'] . "%) for " . 
                               date('F Y', strtotime($profit['month'])) . " has been approved.",
                    'reference_type' => 'profit_distribution',
                    'reference_id' => $dist['id']
                ];
                
                $this->notification->createNotification($notificationData);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getProfitDetails($id) {
        $sql = "SELECT mp.*, 
                       COUNT(pd.id) as total_distributions,
                       SUM(pd.amount) as total_distributed,
                       CASE 
                           WHEN mp.status = 'draft' THEN 'warning'
                           WHEN mp.status = 'final' THEN 'success'
                           ELSE 'secondary'
                       END as status_class
                FROM monthly_profits mp
                LEFT JOIN profit_distributions pd ON mp.id = pd.profit_id
                WHERE mp.id = ?
                GROUP BY mp.id";

        $profit = $this->db->query($sql, [$id])->fetch();

        if (!$profit) {
            throw new Exception("Profit record not found");
        }

        return $profit;
    }

    public function getProfitDistributions($profitId) {
        $sql = "SELECT pd.*,
                       i.name as investor_name,
                       i.email as investor_email
                FROM profit_distributions pd
                JOIN investors i ON pd.investor_id = i.id
                WHERE pd.profit_id = ?
                ORDER BY pd.percentage DESC";

        return $this->db->query($sql, [$profitId])->fetchAll();
    }
}
