<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/CommissionRule.php';

class Commission {
    private $conn;
    private $table_name = "commissions";

    public $id;
    public $user_id;
    public $order_id;
    public $amount;
    public $rule_id;
    public $status;
    public $created_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
            (user_id, order_id, amount, rule_id, status) 
            VALUES (:user_id, :order_id, :amount, :rule_id, :status)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':order_id', $this->order_id);
        $stmt->bindParam(':amount', $this->amount);
        $stmt->bindParam(':rule_id', $this->rule_id);
        $stmt->bindParam(':status', $this->status);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function calculateOrderCommissions($order_id) {
        // Get order items
        $query = "SELECT oi.*, p.category_id, p.price as original_price 
                 FROM order_items oi 
                 JOIN products p ON oi.product_id = p.id 
                 WHERE oi.order_id = :order_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        
        $total_commission = 0;
        $commissionRule = new CommissionRule();
        
        while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Get applicable commission rule
            $rule = $commissionRule->getApplicableRule($item['product_id'], $item['category_id']);
            if ($rule) {
                // Calculate commission based on original price only
                $item_total = $item['original_price'] * $item['quantity'];
                $commission_amount = $item_total * ($rule['rate'] / 100);
                
                // Apply min/max limits if set
                if ($rule['min_amount'] && $commission_amount < $rule['min_amount']) {
                    $commission_amount = $rule['min_amount'];
                }
                if ($rule['max_amount'] && $commission_amount > $rule['max_amount']) {
                    $commission_amount = $rule['max_amount'];
                }
                
                $total_commission += $commission_amount;
            }
        }
        
        return $total_commission;
    }

    public function readByUser($user_id, $status = null) {
        $query = "SELECT c.*, o.total as order_total, cr.rate as commission_rate 
                 FROM " . $this->table_name . " c
                 JOIN orders o ON c.order_id = o.id 
                 JOIN commission_rules cr ON c.rule_id = cr.id
                 WHERE c.user_id = :user_id";
        
        if ($status) {
            $query .= " AND c.status = :status";
        }
        
        $query .= " ORDER BY c.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        if ($status) {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->execute();
        return $stmt;
    }

    public function getUserTotalCommissions($user_id, $start_date = null, $end_date = null) {
        $query = "SELECT SUM(amount) as total FROM " . $this->table_name . " 
                 WHERE user_id = :user_id";
        
        if ($start_date) {
            $query .= " AND created_at >= :start_date";
        }
        if ($end_date) {
            $query .= " AND created_at <= :end_date";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        if ($start_date) {
            $stmt->bindParam(':start_date', $start_date);
        }
        if ($end_date) {
            $stmt->bindParam(':end_date', $end_date);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " 
                 SET status = :status 
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    public function getCommissionReport($start_date, $end_date) {
        $query = "SELECT 
                    u.username,
                    COUNT(c.id) as total_orders,
                    SUM(c.amount) as total_commission,
                    c.status,
                    MIN(c.created_at) as first_commission,
                    MAX(c.created_at) as last_commission
                 FROM " . $this->table_name . " c
                 JOIN users u ON c.user_id = u.id
                 WHERE c.created_at BETWEEN :start_date AND :end_date
                 GROUP BY u.id, c.status
                 ORDER BY total_commission DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        
        $stmt->execute();
        return $stmt;
    }
}
?>
