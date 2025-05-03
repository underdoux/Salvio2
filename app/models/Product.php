<?php

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../helpers/Logger.php';

class Product extends BaseModel {
    protected $table = 'products';

    public function getAll() {
        try {
            Logger::log("Fetching all products");
            $sql = "SELECT p.*, c.name as category_name, 
                    (SELECT quantity FROM stock WHERE product_id = p.id ORDER BY id DESC LIMIT 1) as current_stock 
                    FROM {$this->table} p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.status = TRUE";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            Logger::log("Successfully fetched " . count($products) . " products");
            return $products;
        } catch (Exception $e) {
            Logger::log("Error fetching products: " . $e->getMessage());
            throw $e;
        }
    }

    public function getById($id) {
        try {
            Logger::log("Fetching product with ID: " . $id);
            $sql = "SELECT p.*, c.name as category_name,
                    (SELECT quantity FROM stock WHERE product_id = p.id ORDER BY id DESC LIMIT 1) as current_stock 
                    FROM {$this->table} p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            Logger::log("Product fetch result: " . ($product ? "Found" : "Not found"));
            return $product;
        } catch (Exception $e) {
            Logger::log("Error fetching product by ID: " . $e->getMessage());
            throw $e;
        }
    }

    public function create($data) {
        try {
            Logger::log("Starting product creation process");
            Logger::log("Product data: " . print_r($data, true));
            
            $this->db->beginTransaction();
            Logger::log("Transaction started");

            // Insert product
            $fields = implode(', ', array_keys($data));
            $values = implode(', ', array_fill(0, count($data), '?'));
            
            $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$values})";
            Logger::log("Executing SQL: " . $sql);
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($data));
            
            $productId = $this->db->lastInsertId();
            Logger::log("Product created with ID: " . $productId);

            // Initialize stock if product is stocked
            if ($data['stock_type'] === 'stocked') {
                Logger::log("Initializing stock for stocked product");
                $sql = "INSERT INTO stock (product_id, quantity) VALUES (?, 0)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$productId]);
                Logger::log("Stock initialized for product ID: " . $productId);
            }

            $this->db->commit();
            Logger::log("Transaction committed successfully");
            return $productId;

        } catch (Exception $e) {
            Logger::log("Error in product creation: " . $e->getMessage());
            $this->db->rollBack();
            Logger::log("Transaction rolled back");
            throw $e;
        }
    }

    public function update($id, $data) {
        try {
            Logger::log("Updating product ID: " . $id);
            Logger::log("Update data: " . print_r($data, true));
            
            $fields = implode('=?, ', array_keys($data)) . '=?';
            $sql = "UPDATE {$this->table} SET {$fields} WHERE id = ?";
            
            $values = array_values($data);
            $values[] = $id;
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($values);
            
            Logger::log("Product update " . ($result ? "successful" : "failed"));
            return $result;
        } catch (Exception $e) {
            Logger::log("Error updating product: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateStock($id, $quantity, $type = 'adjustment', $reference = null) {
        try {
            Logger::log("Starting stock update for product ID: " . $id);
            Logger::log("Quantity: " . $quantity . ", Type: " . $type);
            
            $this->db->beginTransaction();

            // Get current stock
            $sql = "SELECT quantity FROM stock WHERE product_id = ? ORDER BY id DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $currentStock = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $newQuantity = isset($currentStock['quantity']) ? $currentStock['quantity'] + $quantity : $quantity;
            Logger::log("Current stock: " . ($currentStock['quantity'] ?? 0) . ", New stock will be: " . $newQuantity);

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
            Logger::log("Stock update completed successfully");
            return true;

        } catch (Exception $e) {
            Logger::log("Error updating stock: " . $e->getMessage());
            $this->db->rollBack();
            Logger::log("Stock update transaction rolled back");
            throw $e;
        }
    }

    public function getCategories() {
        try {
            Logger::log("Attempting to fetch categories from database");
            $sql = "SELECT * FROM categories ORDER BY name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            Logger::log("Successfully fetched " . count($categories) . " categories");
            return $categories;
        } catch (PDOException $e) {
            Logger::log("Database error when fetching categories: " . $e->getMessage());
            throw new Exception("Failed to load product categories");
        } catch (Exception $e) {
            Logger::log("Error when fetching categories: " . $e->getMessage());
            throw $e;
        }
    }
}
