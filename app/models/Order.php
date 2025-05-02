<?php

class Order extends BaseModel {
    protected $table = 'orders';

    public function create($data) {
        try {
            $this->db->beginTransaction();

            // Create order
            $orderId = parent::create([
                'customer_id' => $data['customer_id'],
                'total_amount' => $data['total_amount'],
                'discount' => $data['discount'] ?? 0,
                'status' => 'new',
                'payment_type' => $data['payment_type'],
                'created_by' => $_SESSION['user']['id']
            ]);

            // Create order items
            foreach ($data['items'] as $item) {
                $this->createOrderItem($orderId, $item);
            }

            $this->db->commit();
            Logger::log("Order #{$orderId} created successfully by user '{$_SESSION['user']['username']}'");
            return $orderId;

        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error creating order: " . $e->getMessage());
            throw $e;
        }
    }

    private function createOrderItem($orderId, $item) {
        $sql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, discount) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $orderId,
            $item['product_id'],
            $item['quantity'],
            $item['unit_price'],
            $item['discount'] ?? 0
        ]);

        // Update stock if product is stocked type
        $this->updateProductStock($item['product_id'], $item['quantity']);
    }

    private function updateProductStock($productId, $quantity) {
        $sql = "UPDATE stock SET quantity = quantity - ? WHERE product_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$quantity, $productId]);
        
        Logger::log("Stock updated for product #{$productId}: -{$quantity} units");
    }

    public function getById($id) {
        $sql = "SELECT o.*, u.username as created_by_name 
                FROM orders o 
                JOIN users u ON o.created_by = u.id 
                WHERE o.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $order['items'] = $this->getOrderItems($id);
        }

        return $order;
    }

    private function getOrderItems($orderId) {
        $sql = "SELECT oi.*, p.name as product_name 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status) {
        $validStatuses = ['new', 'in_progress', 'completed', 'paid'];
        
        if (!in_array($status, $validStatuses)) {
            throw new Exception("Invalid status: {$status}");
        }

        $sql = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status, $id]);

        Logger::log("Order #{$id} status updated to '{$status}' by user '{$_SESSION['user']['username']}'");
    }

    public function getAll($filters = []) {
        $sql = "SELECT o.*, u.username as created_by_name 
                FROM orders o 
                JOIN users u ON o.created_by = u.id 
                WHERE 1=1";
        
        $params = [];

        if (isset($filters['status'])) {
            $sql .= " AND o.status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['date_from'])) {
            $sql .= " AND o.created_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (isset($filters['date_to'])) {
            $sql .= " AND o.created_at <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " ORDER BY o.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
