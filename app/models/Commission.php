<?php

class Commission extends BaseModel {
    private $table = 'sales_commissions';

    public function __construct() {
        parent::__construct();
    }

    /**
     * Get all commissions with optional filtering
     */
    public function getAll($filters = [], $page = 1, $limit = 10) {
        try {
            $sql = "SELECT sc.*, u.username as sales_person,
                   o.order_number, oi.quantity, p.name as product_name,
                   cr.rate_percent, cr.type as rate_type,
                   COALESCE(SUM(cpi.amount), 0) as paid_amount
                   FROM {$this->table} sc
                   LEFT JOIN users u ON sc.user_id = u.id
                   LEFT JOIN orders o ON sc.order_id = o.id
                   LEFT JOIN order_items oi ON sc.order_item_id = oi.id
                   LEFT JOIN products p ON oi.product_id = p.id
                   LEFT JOIN commission_rates cr ON sc.commission_rate_id = cr.id
                   LEFT JOIN commission_payment_items cpi ON sc.id = cpi.commission_id
                   WHERE 1=1";
            
            $params = [];

            if (!empty($filters['user_id'])) {
                $sql .= " AND sc.user_id = ?";
                $params[] = $filters['user_id'];
            }

            if (!empty($filters['status'])) {
                $sql .= " AND sc.status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['date_from'])) {
                $sql .= " AND DATE(sc.created_at) >= ?";
                $params[] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $sql .= " AND DATE(sc.created_at) <= ?";
                $params[] = $filters['date_to'];
            }

            $sql .= " GROUP BY sc.id ORDER BY sc.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = ($page - 1) * $limit;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            Logger::log("Error fetching commissions: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Get commission details by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT sc.*, u.username as sales_person,
                   o.order_number, oi.quantity, p.name as product_name,
                   cr.rate_percent, cr.type as rate_type,
                   COALESCE(SUM(cpi.amount), 0) as paid_amount
                   FROM {$this->table} sc
                   LEFT JOIN users u ON sc.user_id = u.id
                   LEFT JOIN orders o ON sc.order_id = o.id
                   LEFT JOIN order_items oi ON sc.order_item_id = oi.id
                   LEFT JOIN products p ON oi.product_id = p.id
                   LEFT JOIN commission_rates cr ON sc.commission_rate_id = cr.id
                   LEFT JOIN commission_payment_items cpi ON sc.id = cpi.commission_id
                   WHERE sc.id = ?
                   GROUP BY sc.id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $commission = $stmt->fetch();

            if ($commission) {
                $commission['adjustments'] = $this->getCommissionAdjustments($id);
                $commission['payments'] = $this->getCommissionPayments($id);
            }

            return $commission;
        } catch (Exception $e) {
            Logger::log("Error fetching commission {$id}: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Calculate and create commission for an order item
     */
    public function createForOrderItem($orderId, $orderItemId, $userId) {
        try {
            $this->db->beginTransaction();

            // Get order item details
            $sql = "SELECT oi.*, p.category_id, p.id as product_id
                   FROM order_items oi
                   LEFT JOIN products p ON oi.product_id = p.id
                   WHERE oi.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderItemId]);
            $item = $stmt->fetch();

            if (!$item) {
                throw new Exception("Order item not found");
            }

            // Get applicable commission rate
            $rate = $this->getApplicableRate($item['product_id'], $item['category_id']);
            if (!$rate) {
                throw new Exception("No applicable commission rate found");
            }

            // Calculate commission amount
            $commissionAmount = $this->calculateCommissionAmount($item['unit_price'], $rate['rate_percent'], $rate['min_amount'], $rate['max_amount']);

            // Insert commission record
            $sql = "INSERT INTO {$this->table} (order_id, order_item_id, user_id, 
                    commission_rate_id, original_price, commission_amount, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $orderId,
                $orderItemId,
                $userId,
                $rate['id'],
                $item['unit_price'],
                $commissionAmount
            ]);

            $this->db->commit();
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error creating commission: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Update commission status
     */
    public function updateStatus($id, $status, $notes = null) {
        try {
            $sql = "UPDATE {$this->table} SET status = ?, notes = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$status, $notes, $id]);
        } catch (Exception $e) {
            Logger::log("Error updating commission status: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Add commission adjustment
     */
    public function addAdjustment($commissionId, $type, $amount, $reason, $userId) {
        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO commission_adjustments (commission_id, adjustment_type, 
                    amount, reason, created_by)
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$commissionId, $type, $amount, $reason, $userId]);

            // Update commission amount
            $adjustment = $type === 'increase' ? $amount : -$amount;
            $sql = "UPDATE {$this->table} 
                    SET commission_amount = commission_amount + ?
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$adjustment, $commissionId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error adding commission adjustment: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Process commission payment
     */
    public function processPayment($userId, $commissions, $paymentData) {
        try {
            $this->db->beginTransaction();

            // Create payment record
            $sql = "INSERT INTO commission_payments (user_id, amount, payment_date,
                    payment_method, reference_number, notes, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $userId,
                $paymentData['amount'],
                $paymentData['payment_date'],
                $paymentData['payment_method'],
                $paymentData['reference_number'] ?? null,
                $paymentData['notes'] ?? null,
                $_SESSION['user_id']
            ]);

            $paymentId = $this->db->lastInsertId();

            // Create payment items
            $sql = "INSERT INTO commission_payment_items (payment_id, commission_id, amount)
                    VALUES (?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            foreach ($commissions as $commission) {
                $stmt->execute([$paymentId, $commission['id'], $commission['amount']]);

                // Update commission status if fully paid
                $this->updateCommissionPaymentStatus($commission['id']);
            }

            $this->db->commit();
            return $paymentId;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error processing commission payment: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Get commission rates
     */
    public function getRates($type = null, $referenceId = null) {
        try {
            $sql = "SELECT * FROM commission_rates WHERE 1=1";
            $params = [];

            if ($type) {
                $sql .= " AND type = ?";
                $params[] = $type;
            }

            if ($referenceId) {
                $sql .= " AND reference_id = ?";
                $params[] = $referenceId;
            }

            $sql .= " ORDER BY effective_from DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            Logger::log("Error fetching commission rates: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Create or update commission rate
     */
    public function saveRate($data) {
        try {
            if (!empty($data['id'])) {
                $sql = "UPDATE commission_rates SET 
                        type = ?, reference_id = ?, rate_percent = ?,
                        min_amount = ?, max_amount = ?, effective_from = ?,
                        effective_to = ?, created_by = ?
                        WHERE id = ?";
                
                $params = [
                    $data['type'],
                    $data['reference_id'],
                    $data['rate_percent'],
                    $data['min_amount'] ?? 0,
                    $data['max_amount'] ?? null,
                    $data['effective_from'],
                    $data['effective_to'] ?? null,
                    $_SESSION['user_id'],
                    $data['id']
                ];
            } else {
                $sql = "INSERT INTO commission_rates (type, reference_id, rate_percent,
                        min_amount, max_amount, effective_from, effective_to, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $params = [
                    $data['type'],
                    $data['reference_id'],
                    $data['rate_percent'],
                    $data['min_amount'] ?? 0,
                    $data['max_amount'] ?? null,
                    $data['effective_from'],
                    $data['effective_to'] ?? null,
                    $_SESSION['user_id']
                ];
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return !empty($data['id']) ? $data['id'] : $this->db->lastInsertId();
        } catch (Exception $e) {
            Logger::log("Error saving commission rate: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Get commission adjustments
     */
    private function getCommissionAdjustments($commissionId) {
        $sql = "SELECT ca.*, u.username as created_by_name
               FROM commission_adjustments ca
               LEFT JOIN users u ON ca.created_by = u.id
               WHERE ca.commission_id = ?
               ORDER BY ca.created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$commissionId]);
        return $stmt->fetchAll();
    }

    /**
     * Get commission payments
     */
    private function getCommissionPayments($commissionId) {
        $sql = "SELECT cp.*, cpi.amount as payment_amount,
               u.username as created_by_name
               FROM commission_payment_items cpi
               LEFT JOIN commission_payments cp ON cpi.payment_id = cp.id
               LEFT JOIN users u ON cp.created_by = u.id
               WHERE cpi.commission_id = ?
               ORDER BY cp.payment_date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$commissionId]);
        return $stmt->fetchAll();
    }

    /**
     * Get applicable commission rate
     */
    private function getApplicableRate($productId, $categoryId) {
        // Try product-specific rate first
        $sql = "SELECT * FROM commission_rates
               WHERE type = 'product'
               AND reference_id = ?
               AND effective_from <= CURRENT_DATE
               AND (effective_to IS NULL OR effective_to >= CURRENT_DATE)
               ORDER BY effective_from DESC
               LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        $rate = $stmt->fetch();

        if ($rate) {
            return $rate;
        }

        // Try category rate
        $sql = "SELECT * FROM commission_rates
               WHERE type = 'category'
               AND reference_id = ?
               AND effective_from <= CURRENT_DATE
               AND (effective_to IS NULL OR effective_to >= CURRENT_DATE)
               ORDER BY effective_from DESC
               LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$categoryId]);
        $rate = $stmt->fetch();

        if ($rate) {
            return $rate;
        }

        // Fall back to global rate
        $sql = "SELECT * FROM commission_rates
               WHERE type = 'global'
               AND reference_id IS NULL
               AND effective_from <= CURRENT_DATE
               AND (effective_to IS NULL OR effective_to >= CURRENT_DATE)
               ORDER BY effective_from DESC
               LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Calculate commission amount
     */
    private function calculateCommissionAmount($price, $ratePercent, $minAmount, $maxAmount) {
        $amount = $price * ($ratePercent / 100);

        if ($minAmount && $amount < $minAmount) {
            $amount = $minAmount;
        }

        if ($maxAmount && $amount > $maxAmount) {
            $amount = $maxAmount;
        }

        return $amount;
    }

    /**
     * Update commission payment status
     */
    private function updateCommissionPaymentStatus($commissionId) {
        $sql = "SELECT sc.commission_amount,
               COALESCE(SUM(cpi.amount), 0) as total_paid
               FROM {$this->table} sc
               LEFT JOIN commission_payment_items cpi ON sc.id = cpi.commission_id
               WHERE sc.id = ?
               GROUP BY sc.id, sc.commission_amount";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$commissionId]);
        $result = $stmt->fetch();

        $status = 'pending';
        if ($result['total_paid'] >= $result['commission_amount']) {
            $status = 'paid';
        } elseif ($result['total_paid'] > 0) {
            $status = 'partial';
        }

        $this->updateStatus($commissionId, $status);
    }
}
