<?php

class Commission extends BaseModel {
    protected $table = 'sales_commissions';
    
    public function getSummary($filters = []) {
        $sql = "SELECT 
                    u.id as user_id,
                    u.username,
                    COUNT(DISTINCT sc.order_id) as total_orders,
                    SUM(sc.amount) as total_commission,
                    sc.status
                FROM users u
                LEFT JOIN sales_commissions sc ON u.id = sc.user_id
                WHERE u.role = 'sales'";

        $params = [];

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

        $sql .= " GROUP BY u.id, u.username, sc.status";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRates() {
        $sql = "SELECT 
                    cr.*,
                    CASE 
                        WHEN cr.type = 'global' THEN 'Global'
                        WHEN cr.type = 'category' THEN c.name
                        WHEN cr.type = 'product' THEN p.name
                    END as reference_name
                FROM commission_rates cr
                LEFT JOIN categories c ON cr.type = 'category' AND cr.reference_id = c.id
                LEFT JOIN products p ON cr.type = 'product' AND cr.reference_id = p.id
                ORDER BY cr.type, reference_name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDetailsByUser($userId) {
        $sql = "SELECT 
                    sc.*,
                    o.created_at as order_date
                FROM sales_commissions sc
                JOIN orders o ON sc.order_id = o.id
                WHERE sc.user_id = ?
                ORDER BY o.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateRate($data) {
        // For global rate, set reference_id to null
        if ($data['type'] === 'global') {
            $data['reference_id'] = null;
        }

        // Check if rate exists
        $sql = "SELECT id FROM commission_rates 
                WHERE type = ? AND COALESCE(reference_id, 0) = COALESCE(?, 0)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$data['type'], $data['reference_id']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Update existing rate
            $sql = "UPDATE commission_rates 
                    SET rate = ?, updated_at = NOW() 
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$data['rate'], $existing['id']]);
        } else {
            // Insert new rate
            $sql = "INSERT INTO commission_rates (type, reference_id, rate, created_at, updated_at) 
                    VALUES (?, ?, ?, NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$data['type'], $data['reference_id'], $data['rate']]);
        }
    }

    public function updateStatus($commissionId, $status) {
        $sql = "UPDATE sales_commissions 
                SET status = ?, updated_at = NOW() 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $commissionId]);
    }

    public function calculateCommission($orderId, $userId) {
        // Get order items
        $sql = "SELECT 
                    oi.*,
                    p.category_id
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalCommission = 0;

        foreach ($items as $item) {
            // Try to get product-specific rate
            $rate = $this->getCommissionRate('product', $item['product_id']);
            
            if (!$rate) {
                // Try category rate
                $rate = $this->getCommissionRate('category', $item['category_id']);
                
                if (!$rate) {
                    // Fall back to global rate
                    $rate = $this->getCommissionRate('global');
                }
            }

            if ($rate) {
                $itemCommission = ($item['unit_price'] * $item['quantity']) * ($rate / 100);
                $totalCommission += $itemCommission;
            }
        }

        // Insert commission record
        if ($totalCommission > 0) {
            $sql = "INSERT INTO sales_commissions 
                    (user_id, order_id, amount, status, created_at, updated_at)
                    VALUES (?, ?, ?, 'pending', NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $orderId, $totalCommission]);
        }

        return $totalCommission;
    }

    private function getCommissionRate($type, $referenceId = null) {
        $sql = "SELECT rate FROM commission_rates 
                WHERE type = ? AND COALESCE(reference_id, 0) = COALESCE(?, 0)
                ORDER BY updated_at DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$type, $referenceId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['rate'] : null;
    }
}
