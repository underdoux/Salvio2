<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Investor.php';

class ProfitDistribution {
    private $conn;
    private $table_name = "profit_distributions";

    public $id;
    public $period_start;
    public $period_end;
    public $total_revenue;
    public $total_cost;
    public $net_profit;
    public $status;
    public $created_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
            (period_start, period_end, total_revenue, total_cost, net_profit, status) 
            VALUES (:period_start, :period_end, :total_revenue, :total_cost, :net_profit, :status)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':period_start', $this->period_start);
        $stmt->bindParam(':period_end', $this->period_end);
        $stmt->bindParam(':total_revenue', $this->total_revenue);
        $stmt->bindParam(':total_cost', $this->total_cost);
        $stmt->bindParam(':net_profit', $this->net_profit);
        $stmt->bindParam(':status', $this->status);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            
            // If distribution is approved, create profit shares
            if ($this->status === 'approved') {
                $this->createProfitShares();
            }
            
            return true;
        }
        return false;
    }

    private function createProfitShares() {
        // Get active investors
        $investor = new Investor();
        $stmt = $investor->readAll(true); // true for active_only
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $share_amount = $this->net_profit * ($row['percentage'] / 100);
            
            $query = "INSERT INTO profit_shares 
                     (distribution_id, investor_id, amount, status) 
                     VALUES (:distribution_id, :investor_id, :amount, 'pending')";
            
            $share_stmt = $this->conn->prepare($query);
            $share_stmt->bindParam(':distribution_id', $this->id);
            $share_stmt->bindParam(':investor_id', $row['id']);
            $share_stmt->bindParam(':amount', $share_amount);
            $share_stmt->execute();
        }
    }

    public function calculatePeriodMetrics($start_date, $end_date) {
        // Calculate total revenue from completed orders
        $query = "SELECT SUM(total) as revenue 
                 FROM orders 
                 WHERE status = 'completed' 
                 AND payment_status = 'completed'
                 AND created_at BETWEEN :start_date AND :end_date";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->total_revenue = $row['revenue'] ?? 0;

        // Calculate total costs (product costs + operational expenses)
        // This is a simplified version - you might want to add more cost factors
        $query = "SELECT SUM(oi.quantity * p.cost_price) as total_cost
                 FROM order_items oi 
                 JOIN products p ON oi.product_id = p.id
                 JOIN orders o ON oi.order_id = o.id
                 WHERE o.status = 'completed' 
                 AND o.payment_status = 'completed'
                 AND o.created_at BETWEEN :start_date AND :end_date";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->total_cost = $row['total_cost'] ?? 0;

        // Calculate net profit
        $this->net_profit = $this->total_revenue - $this->total_cost;
        
        return [
            'revenue' => $this->total_revenue,
            'cost' => $this->total_cost,
            'net_profit' => $this->net_profit
        ];
    }

    public function readAll() {
        $query = "SELECT pd.*, 
                    COUNT(ps.id) as total_shares,
                    SUM(CASE WHEN ps.status = 'paid' THEN ps.amount ELSE 0 END) as total_paid
                 FROM " . $this->table_name . " pd
                 LEFT JOIN profit_shares ps ON pd.id = ps.distribution_id
                 GROUP BY pd.id
                 ORDER BY pd.period_end DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE id = :id LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $new_status) {
        if ($new_status === 'approved') {
            $this->id = $id;
            $this->createProfitShares();
        }

        $query = "UPDATE " . $this->table_name . " 
                 SET status = :status 
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    public function getDistributionDetails($id) {
        $query = "SELECT 
                    pd.*,
                    i.name as investor_name,
                    i.percentage as investor_percentage,
                    ps.amount as share_amount,
                    ps.status as share_status,
                    ps.paid_date
                 FROM " . $this->table_name . " pd
                 JOIN profit_shares ps ON pd.id = ps.distribution_id
                 JOIN investors i ON ps.investor_id = i.id
                 WHERE pd.id = :id
                 ORDER BY i.percentage DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt;
    }

    public function getProfitTrends($months = 12) {
        $query = "SELECT 
                    DATE_FORMAT(period_end, '%Y-%m') as month,
                    SUM(total_revenue) as revenue,
                    SUM(total_cost) as cost,
                    SUM(net_profit) as profit
                 FROM " . $this->table_name . "
                 WHERE period_end >= DATE_SUB(CURRENT_DATE, INTERVAL :months MONTH)
                 GROUP BY DATE_FORMAT(period_end, '%Y-%m')
                 ORDER BY month ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':months', $months);
        $stmt->execute();
        
        return $stmt;
    }
}
?>
