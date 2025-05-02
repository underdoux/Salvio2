<?php

class Product extends BaseModel {
    protected $table = 'products';

    public function getAll() {
        $sql = "SELECT p.*, c.name as category_name, 
                (SELECT quantity FROM stock WHERE product_id = p.id ORDER BY id DESC LIMIT 1) as current_stock 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.status = TRUE";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $sql = "SELECT p.*, c.name as category_name,
                (SELECT quantity FROM stock WHERE product_id = p.id ORDER BY id DESC LIMIT 1) as current_stock 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        try {
            $this->db->beginTransaction();

            // Insert product
            $fields = implode(', ', array_keys($data));
            $values = implode(', ', array_fill(0, count($data), '?'));
            
            $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$values})";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($data));
            
            $productId = $this->db->lastInsertId();

            // Initialize stock if product is stocked
            if ($data['stock_type'] === 'stocked') {
                $sql = "INSERT INTO stock (product_id, quantity) VALUES (?, 0)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$productId]);
            }

            $this->db->commit();
            return $productId;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update($id, $data) {
        $fields = implode('=?, ', array_keys($data)) . '=?';
        
        $sql = "UPDATE {$this->table} SET {$fields} WHERE id = ?";
        $values = array_values($data);
        $values[] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function updateStock($id, $quantity, $type = 'adjustment', $reference = null) {
        try {
            $this->db->beginTransaction();

            // Get current stock
            $sql = "SELECT quantity FROM stock WHERE product_id = ? ORDER BY id DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $currentStock = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $newQuantity = isset($currentStock['quantity']) ? $currentStock['quantity'] + $quantity : $quantity;

            // Insert new stock record
            $sql = "INSERT INTO stock (product_id, quantity) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id, $newQuantity]);

            // Record stock history
            $sql = "INSERT INTO stock_history (product_id, quantity, type, reference_type, reference_id) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id, $quantity, $type, $reference ? $reference['type'] : null, $reference ? $reference['id'] : null]);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getCategories() {
        $sql = "SELECT * FROM categories ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
