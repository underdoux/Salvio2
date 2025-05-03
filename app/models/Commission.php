<?php

require_once __DIR__ . '/Notification.php';

class Commission extends BaseModel {
    private $notification;
    protected $table = 'sales_commissions';

    public function __construct() {
        parent::__construct();
        $this->notification = new Notification();
    }

    public function getCommissionRates() {
        try {
            $sql = "SELECT cr.*, 
                    CASE 
                        WHEN cr.product_id IS NOT NULL THEN p.name
                        WHEN cr.category_id IS NOT NULL THEN c.name
                        ELSE 'Global'
                    END as target_name,
                    CASE 
                        WHEN cr.product_id IS NOT NULL THEN 'Product'
                        WHEN cr.category_id IS NOT NULL THEN 'Category'
                        ELSE 'Global'
                    END as rate_type
                    FROM commission_rates cr
                    LEFT JOIN products p ON cr.product_id = p.id
                    LEFT JOIN categories c ON cr.category_id = c.id
                    WHERE cr.status = 'active'
                    ORDER BY cr.rate_type, cr.target_name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::log("Error fetching commission rates: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    public function getCategories() {
        try {
            $sql = "SELECT * FROM categories WHERE status = 'active' ORDER BY name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::log("Error fetching categories: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    public function getProducts() {
        try {
            $sql = "SELECT p.*, c.name as category_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.status = 'active' 
                    ORDER BY p.name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::log("Error fetching products: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    public function getAll($filters = []) {
        $sql = "SELECT c.*, 
                       u.username as user_name,
                       o.id as order_id,
                       o.total_amount as order_amount,
                       COALESCE(cp.amount, 0) as paid_amount,
                       cp.payment_date,
                       cp.payment_method
                FROM sales_commissions c
                JOIN users u ON c.user_id = u.id
                JOIN orders o ON c.order_id = o.id
                LEFT JOIN commission_payments cp ON c.id = cp.commission_id
                WHERE 1=1";
        
        $params = [];

        if (isset($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['user_id'])) {
            $sql .= " AND c.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (isset($filters['start_date'])) {
            $sql .= " AND c.created_at >= ?";
            $params[] = $filters['start_date'];
        }

        if (isset($filters['end_date'])) {
            $sql .= " AND c.created_at <= ?";
            $params[] = $filters['end_date'];
        }

        $sql .= " ORDER BY c.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateCommissionStatus($commissionId, $status) {
        try {
            $this->db->beginTransaction();

            // Get commission details before update
            $sql = "SELECT c.*, u.email, CONCAT(u.first_name, ' ', u.last_name) as recipient_name 
                    FROM sales_commissions c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.id = ?";
            $commission = $this->db->query($sql, [$commissionId])->fetch();

            if (!$commission) {
                throw new Exception("Commission not found");
            }

            // Update status
            $sql = "UPDATE sales_commissions SET status = ? WHERE id = ?";
            $this->db->query($sql, [$status, $commissionId]);

            // Create notification
            $notificationData = [
                'user_id' => $commission['user_id'],
                'type' => 'commission_status',
                'title' => 'Commission Status Updated',
                'message' => "Your commission of $" . number_format($commission['amount'], 2) . 
                           " has been marked as " . strtoupper($status),
                'reference_type' => 'commission',
                'reference_id' => $commissionId
            ];
            
            $this->notification->createNotification($notificationData);

            // Send email notification
            $emailSubject = "Commission Status Update";
            $emailMessage = "Dear {$commission['recipient_name']},\n\n" .
                          "Your commission of $" . number_format($commission['amount'], 2) . 
                          " has been marked as " . strtoupper($status) . ".\n\n" .
                          "Please log in to your account for more details.\n\n" .
                          "Best regards,\nSalvio POS Team";

            $this->notification->sendEmailNotification(
                $commission['user_id'],
                $emailSubject,
                $emailMessage
            );

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function recordPayment($commissionId, $data) {
        try {
            $this->db->beginTransaction();

            // Get commission details
            $sql = "SELECT c.*, u.email, CONCAT(u.first_name, ' ', u.last_name) as recipient_name 
                    FROM sales_commissions c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.id = ?";
            $commission = $this->db->query($sql, [$commissionId])->fetch();

            if (!$commission) {
                throw new Exception("Commission not found");
            }

            // Insert payment record
            $sql = "INSERT INTO commission_payments (
                        commission_id,
                        amount,
                        payment_date,
                        payment_method,
                        reference_number,
                        notes,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, NOW())";

            $params = [
                $commissionId,
                $data['amount'],
                $data['payment_date'],
                $data['payment_method'],
                $data['reference_number'],
                $data['notes'] ?? null
            ];

            $this->db->query($sql, $params);
            $paymentId = $this->db->lastInsertId();

            // Update commission status
            $this->updateCommissionStatus($commissionId, 'paid');

            // Create notification
            $notificationData = [
                'user_id' => $commission['user_id'],
                'type' => 'commission_payment',
                'title' => 'Commission Payment Recorded',
                'message' => "A payment of $" . number_format($data['amount'], 2) . 
                           " has been recorded for your commission",
                'reference_type' => 'commission_payment',
                'reference_id' => $paymentId
            ];
            
            $this->notification->createNotification($notificationData);

            // Send email notification
            $emailSubject = "Commission Payment Recorded";
            $emailMessage = "Dear {$commission['recipient_name']},\n\n" .
                          "A payment of $" . number_format($data['amount'], 2) . 
                          " has been recorded for your commission.\n\n" .
                          "Payment Details:\n" .
                          "- Method: {$data['payment_method']}\n" .
                          "- Reference: {$data['reference_number']}\n" .
                          "- Date: {$data['payment_date']}\n\n" .
                          "Please log in to your account for more details.\n\n" .
                          "Best regards,\nSalvio POS Team";

            $this->notification->sendEmailNotification(
                $commission['user_id'],
                $emailSubject,
                $emailMessage
            );

            $this->db->commit();
            return $paymentId;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getCommissionSummaryByPeriod($period, $year) {
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as period,
                    COUNT(*) as total_count,
                    SUM(amount) as total_amount,
                    MIN(amount) as min_amount,
                    MAX(amount) as max_amount,
                    AVG(amount) as avg_amount
                FROM sales_commissions
                WHERE YEAR(created_at) = ?
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY period DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCommissionPerformanceMetrics($userId = null, $days = 30) {
        $sql = "SELECT 
                    COUNT(*) as total_commissions,
                    SUM(amount) as total_amount,
                    AVG(amount) as avg_amount,
                    COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
                    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount
                FROM sales_commissions
                WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL ? DAY)";
        
        $params = [$days];
        
        if ($userId) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getCommissionReport($filters) {
        $sql = "SELECT c.*, 
                       u.username as user_name,
                       o.order_number,
                       o.total_amount as order_amount,
                       COALESCE(cp.amount, 0) as paid_amount,
                       cp.payment_date,
                       cp.payment_method
                FROM sales_commissions c
                JOIN users u ON c.user_id = u.id
                JOIN orders o ON c.order_id = o.id
                LEFT JOIN commission_payments cp ON c.id = cp.commission_id
                WHERE 1=1";
        
        $params = [];

        if (isset($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['user_id'])) {
            $sql .= " AND c.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (isset($filters['start_date'])) {
            $sql .= " AND c.created_at >= ?";
            $params[] = $filters['start_date'];
        }

        if (isset($filters['end_date'])) {
            $sql .= " AND c.created_at <= ?";
            $params[] = $filters['end_date'];
        }

        $sql .= " ORDER BY c.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCommissionTrendsByProduct($startDate, $endDate) {
        $sql = "SELECT 
                    p.name as product_name,
                    COUNT(*) as commission_count,
                    SUM(c.amount) as total_commission,
                    AVG(c.amount) as avg_commission
                FROM sales_commissions c
                JOIN orders o ON c.order_id = o.id
                JOIN order_items oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.id
                WHERE c.created_at BETWEEN ? AND ?
                GROUP BY p.id, p.name
                ORDER BY total_commission DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function exportCommissionReport($filters) {
        $sql = "SELECT 
                    c.id as commission_id,
                    u.username as user_name,
                    o.order_number,
                    c.amount as commission_amount,
                    c.status,
                    COALESCE(cp.amount, 0) as paid_amount,
                    cp.payment_date,
                    cp.payment_method,
                    c.created_at
                FROM sales_commissions c
                JOIN users u ON c.user_id = u.id
                JOIN orders o ON c.order_id = o.id
                LEFT JOIN commission_payments cp ON c.id = cp.commission_id
                WHERE 1=1";
        
        $params = [];

        if (isset($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['user_id'])) {
            $sql .= " AND c.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (isset($filters['start_date'])) {
            $sql .= " AND c.created_at >= ?";
            $params[] = $filters['start_date'];
        }

        if (isset($filters['end_date'])) {
            $sql .= " AND c.created_at <= ?";
            $params[] = $filters['end_date'];
        }

        $sql .= " ORDER BY c.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function voidPayment($paymentId, $reason) {
        try {
            $this->db->beginTransaction();

            // Get payment details
            $sql = "SELECT * FROM commission_payments WHERE id = ?";
            $payment = $this->db->query($sql, [$paymentId])->fetch();

            if (!$payment) {
                throw new Exception("Payment not found");
            }

            // Delete payment record
            $sql = "DELETE FROM commission_payments WHERE id = ?";
            $this->db->query($sql, [$paymentId]);

            // Update commission status back to approved
            $sql = "UPDATE sales_commissions SET status = 'approved' WHERE id = ?";
            $this->db->query($sql, [$payment['commission_id']]);

            // Log the void
            Logger::log("Commission payment #{$paymentId} voided. Reason: {$reason}");

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function saveCommissionRate($data) {
        try {
            $this->db->beginTransaction();

            // Prepare fields based on rate type
            $fields = ['rate'];
            $values = [$data['rate']];
            
            if ($data['rate_type'] === 'category' && isset($data['category_id'])) {
                $fields[] = 'category_id';
                $values[] = $data['category_id'];
            } elseif ($data['rate_type'] === 'product' && isset($data['product_id'])) {
                $fields[] = 'product_id';
                $values[] = $data['product_id'];
            }

            // Insert rate record
            $sql = "INSERT INTO commission_rates (" . implode(', ', $fields) . ") 
                    VALUES (" . implode(', ', array_fill(0, count($values), '?')) . ")";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
            $rateId = $this->db->lastInsertId();

            $this->db->commit();
            return $rateId;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getCommissionRate($id) {
        try {
            $sql = "SELECT cr.*, 
                    CASE 
                        WHEN cr.product_id IS NOT NULL THEN p.name
                        WHEN cr.category_id IS NOT NULL THEN c.name
                        ELSE 'Global'
                    END as target_name,
                    CASE 
                        WHEN cr.product_id IS NOT NULL THEN 'Product'
                        WHEN cr.category_id IS NOT NULL THEN 'Category'
                        ELSE 'Global'
                    END as rate_type
                    FROM commission_rates cr
                    LEFT JOIN products p ON cr.product_id = p.id
                    LEFT JOIN categories c ON cr.category_id = c.id
                    WHERE cr.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::log("Error fetching commission rate: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    public function deleteCommissionRate($id) {
        try {
            $this->db->beginTransaction();

            // Check if rate exists
            $sql = "SELECT * FROM commission_rates WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $rate = $stmt->fetch();

            if (!$rate) {
                throw new Exception("Commission rate not found");
            }

            // Delete rate
            $sql = "DELETE FROM commission_rates WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function calculateOrderCommission($orderId) {
        try {
            $result = parent::calculateOrderCommission($orderId);

            // Get commission details
            $sql = "SELECT c.*, u.email, CONCAT(u.first_name, ' ', u.last_name) as recipient_name 
                    FROM sales_commissions c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.id = ?";
            $commission = $this->db->query($sql, [$result['commission_id']])->fetch();

            // Create notification for new commission
            $notificationData = [
                'user_id' => $commission['user_id'],
                'type' => 'new_commission',
                'title' => 'New Commission Generated',
                'message' => "A new commission of $" . number_format($commission['amount'], 2) . 
                           " has been generated for Order #{$orderId}",
                'reference_type' => 'commission',
                'reference_id' => $result['commission_id']
            ];
            
            $this->notification->createNotification($notificationData);

            // Send email notification
            $emailSubject = "New Commission Generated";
            $emailMessage = "Dear {$commission['recipient_name']},\n\n" .
                          "A new commission of $" . number_format($commission['amount'], 2) . 
                          " has been generated for Order #{$orderId}.\n\n" .
                          "Please log in to your account to view the details.\n\n" .
                          "Best regards,\nSalvio POS Team";

            $this->notification->sendEmailNotification(
                $commission['user_id'],
                $emailSubject,
                $emailMessage
            );

            return $result;

        } catch (Exception $e) {
            throw $e;
        }
    }
}
