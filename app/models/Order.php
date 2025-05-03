<?php

class Order extends BaseModel {
    private $table = 'orders';
    private $fillable = [
        'order_number', 'customer_name', 'customer_type', 'customer_address',
        'customer_phone', 'total_amount', 'discount_amount', 'discount_reason',
        'final_amount', 'payment_type', 'payment_status', 'order_status',
        'notes', 'created_by'
    ];

    public function __construct() {
        parent::__construct();
    }

    public function getAll($filters = [], $page = 1, $limit = 10) {
        try {
            $sql = "SELECT o.*, u.username as created_by_name,
                   COUNT(oi.id) as total_items,
                   COALESCE(SUM(op.amount), 0) as paid_amount
                   FROM {$this->table} o
                   LEFT JOIN users u ON o.created_by = u.id
                   LEFT JOIN order_items oi ON o.id = oi.order_id
                   LEFT JOIN order_payments op ON o.id = op.order_id
                   WHERE 1=1";
            
            $params = [];

            if (!empty($filters['search'])) {
                $sql .= " AND (o.order_number LIKE ? OR o.customer_name LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params = array_merge($params, [$searchTerm, $searchTerm]);
            }

            if (!empty($filters['status'])) {
                $sql .= " AND o.order_status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['payment_status'])) {
                $sql .= " AND o.payment_status = ?";
                $params[] = $filters['payment_status'];
            }

            if (!empty($filters['customer_type'])) {
                $sql .= " AND o.customer_type = ?";
                $params[] = $filters['customer_type'];
            }

            if (!empty($filters['date_from'])) {
                $sql .= " AND DATE(o.created_at) >= ?";
                $params[] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $sql .= " AND DATE(o.created_at) <= ?";
                $params[] = $filters['date_to'];
            }

            $sql .= " GROUP BY o.id ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = ($page - 1) * $limit;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            Logger::log("Error fetching orders: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    public function getById($id) {
        try {
            $sql = "SELECT o.*, u.username as created_by_name
                   FROM {$this->table} o
                   LEFT JOIN users u ON o.created_by = u.id
                   WHERE o.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $order = $stmt->fetch();

            if ($order) {
                $order['items'] = $this->getOrderItems($id);
                $order['payments'] = $this->getOrderPayments($id);
                $order['history'] = $this->getOrderHistory($id);
                $order['shipping'] = $this->getOrderShipping($id);
            }

            return $order;
        } catch (Exception $e) {
            Logger::log("Error fetching order {$id}: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    public function create($data) {
        try {
            $this->db->beginTransaction();

            // Generate order number
            $data['order_number'] = $this->generateOrderNumber();

            // Validate order data
            $this->validateOrder($data);

            // Calculate totals
            $this->calculateOrderTotals($data);

            // Insert order
            $fields = array_intersect_key($data, array_flip($this->fillable));
            $columns = implode(', ', array_keys($fields));
            $values = implode(', ', array_fill(0, count($fields), '?'));
            
            $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$values})";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($fields));
            $orderId = $this->db->lastInsertId();

            // Insert order items
            if (!empty($data['items'])) {
                $this->insertOrderItems($orderId, $data['items']);
            }

            // Create initial order history
            $this->addOrderHistory($orderId, 'new', 'Order created');

            // Create shipping record if needed
            if (!empty($data['shipping'])) {
                $this->createOrderShipping($orderId, $data['shipping']);
            }

            $this->db->commit();
            return $orderId;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error creating order: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    public function update($id, $data) {
        try {
            $this->db->beginTransaction();

            // Get current order
            $currentOrder = $this->getById($id);
            if (!$currentOrder) {
                throw new Exception("Order not found");
            }

            // Validate order data
            $this->validateOrder($data, true);

            // Calculate totals if items changed
            if (!empty($data['items'])) {
                $this->calculateOrderTotals($data);
            }

            // Update order
            $fields = array_intersect_key($data, array_flip($this->fillable));
            $updates = [];
            foreach ($fields as $key => $value) {
                $updates[] = "{$key} = ?";
            }
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([...array_values($fields), $id]);

            // Update order items if provided
            if (!empty($data['items'])) {
                // Remove existing items
                $this->deleteOrderItems($id);
                // Insert new items
                $this->insertOrderItems($id, $data['items']);
            }

            // Add order history entry if status changed
            if (!empty($data['order_status']) && $data['order_status'] !== $currentOrder['order_status']) {
                $this->addOrderHistory($id, $data['order_status'], $data['status_notes'] ?? null);
            }

            // Update shipping info if provided
            if (!empty($data['shipping'])) {
                $this->updateOrderShipping($id, $data['shipping']);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error updating order {$id}: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    public function addPayment($orderId, $paymentData) {
        try {
            $this->db->beginTransaction();

            // Insert payment record
            $sql = "INSERT INTO order_payments (order_id, amount, payment_date, payment_method, 
                    reference_number, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $orderId,
                $paymentData['amount'],
                $paymentData['payment_date'],
                $paymentData['payment_method'],
                $paymentData['reference_number'] ?? null,
                $paymentData['notes'] ?? null,
                $_SESSION['user_id'] ?? null
            ]);

            // Update order payment status
            $this->updatePaymentStatus($orderId);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error adding payment for order {$orderId}: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    private function getOrderItems($orderId) {
        $sql = "SELECT oi.*, p.name as product_name, p.sku
               FROM order_items oi
               LEFT JOIN products p ON oi.product_id = p.id
               WHERE oi.order_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    private function getOrderPayments($orderId) {
        $sql = "SELECT op.*, u.username as created_by_name
               FROM order_payments op
               LEFT JOIN users u ON op.created_by = u.id
               WHERE op.order_id = ?
               ORDER BY op.payment_date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    private function getOrderHistory($orderId) {
        $sql = "SELECT oh.*, u.username as created_by_name
               FROM order_history oh
               LEFT JOIN users u ON oh.created_by = u.id
               WHERE oh.order_id = ?
               ORDER BY oh.created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    private function getOrderShipping($orderId) {
        $sql = "SELECT os.*, u.username as created_by_name
               FROM order_shipping os
               LEFT JOIN users u ON os.created_by = u.id
               WHERE os.order_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetch();
    }

    private function generateOrderNumber() {
        $prefix = date('Ymd');
        $sql = "SELECT MAX(CAST(SUBSTRING(order_number, 9) AS UNSIGNED)) as last_number
               FROM {$this->table}
               WHERE order_number LIKE ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["{$prefix}%"]);
        $result = $stmt->fetch();

        $nextNumber = ($result['last_number'] ?? 0) + 1;
        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    private function validateOrder($data, $isUpdate = false) {
        $required = ['customer_name', 'customer_type', 'payment_type'];
        if (!$isUpdate) {
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field {$field} is required");
                }
            }
        }

        if (!empty($data['items'])) {
            if (empty($data['items'])) {
                throw new Exception("Order must have at least one item");
            }

            foreach ($data['items'] as $item) {
                if (empty($item['product_id']) || empty($item['quantity']) || empty($item['unit_price'])) {
                    throw new Exception("Invalid order item data");
                }
            }
        }

        return true;
    }

    private function calculateOrderTotals(&$data) {
        $totalAmount = 0;
        $discountAmount = 0;

        foreach ($data['items'] as &$item) {
            $itemTotal = $item['quantity'] * $item['unit_price'];
            $itemDiscount = 0;

            if (!empty($item['discount_percent'])) {
                $itemDiscount = $itemTotal * ($item['discount_percent'] / 100);
            }

            $item['discount_amount'] = $itemDiscount;
            $item['final_price'] = $itemTotal - $itemDiscount;

            $totalAmount += $itemTotal;
            $discountAmount += $itemDiscount;
        }

        // Add order-level discount if any
        if (!empty($data['discount_amount'])) {
            $discountAmount += $data['discount_amount'];
        }

        $data['total_amount'] = $totalAmount;
        $data['discount_amount'] = $discountAmount;
        $data['final_amount'] = $totalAmount - $discountAmount;
    }

    private function insertOrderItems($orderId, $items) {
        $sql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price,
               discount_percent, discount_amount, discount_reason, final_price)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        foreach ($items as $item) {
            $stmt->execute([
                $orderId,
                $item['product_id'],
                $item['quantity'],
                $item['unit_price'],
                $item['discount_percent'] ?? 0,
                $item['discount_amount'] ?? 0,
                $item['discount_reason'] ?? null,
                $item['final_price']
            ]);
        }
    }

    private function deleteOrderItems($orderId) {
        $sql = "DELETE FROM order_items WHERE order_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
    }

    private function addOrderHistory($orderId, $status, $notes = null) {
        $sql = "INSERT INTO order_history (order_id, status, notes, created_by)
               VALUES (?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $orderId,
            $status,
            $notes,
            $_SESSION['user_id'] ?? null
        ]);
    }

    private function createOrderShipping($orderId, $shippingData) {
        $sql = "INSERT INTO order_shipping (order_id, shipping_date, tracking_number,
               shipping_method, shipping_cost, notes, created_by)
               VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $orderId,
            $shippingData['shipping_date'] ?? null,
            $shippingData['tracking_number'] ?? null,
            $shippingData['shipping_method'] ?? null,
            $shippingData['shipping_cost'] ?? 0,
            $shippingData['notes'] ?? null,
            $_SESSION['user_id'] ?? null
        ]);
    }

    private function updateOrderShipping($orderId, $shippingData) {
        $sql = "UPDATE order_shipping SET
               shipping_date = ?,
               tracking_number = ?,
               shipping_method = ?,
               shipping_cost = ?,
               notes = ?,
               created_by = ?
               WHERE order_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $shippingData['shipping_date'] ?? null,
            $shippingData['tracking_number'] ?? null,
            $shippingData['shipping_method'] ?? null,
            $shippingData['shipping_cost'] ?? 0,
            $shippingData['notes'] ?? null,
            $_SESSION['user_id'] ?? null,
            $orderId
        ]);
    }

    private function updatePaymentStatus($orderId) {
        // Get order total and paid amount
        $sql = "SELECT o.final_amount,
               COALESCE(SUM(op.amount), 0) as total_paid
               FROM orders o
               LEFT JOIN order_payments op ON o.id = op.order_id
               WHERE o.id = ?
               GROUP BY o.id, o.final_amount";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $result = $stmt->fetch();

        // Determine payment status
        $status = 'pending';
        if ($result['total_paid'] >= $result['final_amount']) {
            $status = 'paid';
        } elseif ($result['total_paid'] > 0) {
            $status = 'partial';
        }

        // Update order payment status
        $sql = "UPDATE orders SET payment_status = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status, $orderId]);
    }
}
