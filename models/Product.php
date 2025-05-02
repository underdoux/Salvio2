<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/StockMovement.php';
require_once __DIR__ . '/Setting.php';
require_once __DIR__ . '/AuditLog.php';
require_once __DIR__ . '/../services/BPOMService.php';

class Product {
    private $conn;
    private $table_name = "products";

    public $id;
    public $name;
    public $category_id;
    public $stock;
    public $by_order;
    public $price;
    public $cost_price;
    public $min_stock;
    public $bpom_code;
    public $created_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create() {
        // Try to auto-categorize using BPOM service
        if (empty($this->category_id)) {
            $bpomService = new BPOMService();
            $category = $bpomService->matchProductCategory($this->name);
            if ($category) {
                $this->category_id = $category;
            }
        }

        $query = "INSERT INTO " . $this->table_name . " 
            (name, category_id, stock, by_order, price, cost_price, min_stock, bpom_code) 
            VALUES (:name, :category_id, :stock, :by_order, :price, :cost_price, :min_stock, :bpom_code)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':stock', $this->stock);
        $stmt->bindParam(':by_order', $this->by_order, PDO::PARAM_BOOL);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':cost_price', $this->cost_price);
        $stmt->bindParam(':min_stock', $this->min_stock);
        $stmt->bindParam(':bpom_code', $this->bpom_code);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();

            // Create initial stock movement if stock > 0
            if ($this->stock > 0) {
                $stockMovement = new StockMovement();
                $stockMovement->product_id = $this->id;
                $stockMovement->type = 'in';
                $stockMovement->quantity = $this->stock;
                $stockMovement->reference_type = 'initial';
                $stockMovement->reference_id = $this->id;
                $stockMovement->notes = 'Initial stock';
                $stockMovement->create();
            }

            // Log the creation
            $auditLog = new AuditLog();
            $auditLog->log(
                $_SESSION['user_id'] ?? null,
                'create',
                'products',
                $this->id,
                null,
                [
                    'name' => $this->name,
                    'category_id' => $this->category_id,
                    'stock' => $this->stock,
                    'price' => $this->price
                ]
            );

            return true;
        }
        return false;
    }

    public function readAll($include_stock_info = false) {
        $query = "SELECT p.*, c.name as category_name" . 
                ($include_stock_info ? ", 
                    (SELECT COUNT(*) FROM stock_movements 
                     WHERE product_id = p.id 
                     AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)) as movement_count,
                    (SELECT SUM(CASE WHEN type = 'out' THEN quantity ELSE 0 END) 
                     FROM stock_movements 
                     WHERE product_id = p.id 
                     AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)) as monthly_sales" 
                : "") .
                " FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne($id) {
        $query = "SELECT p.*, c.name as category_name,
                    (SELECT SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END) 
                     FROM stock_movements 
                     WHERE product_id = p.id) as current_stock
                 FROM " . $this->table_name . " p
                 LEFT JOIN categories c ON p.category_id = c.id
                 WHERE p.id = :id 
                 LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update() {
        // Get current values for audit log
        $current = $this->readOne($this->id);

        $query = "UPDATE " . $this->table_name . " SET 
            name = :name, 
            category_id = :category_id, 
            by_order = :by_order, 
            price = :price,
            cost_price = :cost_price,
            min_stock = :min_stock,
            bpom_code = :bpom_code
            WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':by_order', $this->by_order, PDO::PARAM_BOOL);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':cost_price', $this->cost_price);
        $stmt->bindParam(':min_stock', $this->min_stock);
        $stmt->bindParam(':bpom_code', $this->bpom_code);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            // Log the update
            $auditLog = new AuditLog();
            $auditLog->log(
                $_SESSION['user_id'] ?? null,
                'update',
                'products',
                $this->id,
                $current,
                [
                    'name' => $this->name,
                    'category_id' => $this->category_id,
                    'price' => $this->price,
                    'cost_price' => $this->cost_price
                ]
            );

            return true;
        }
        return false;
    }

    public function delete($id) {
        // Check if product has any stock movements
        $query = "SELECT COUNT(*) as count FROM stock_movements WHERE product_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row['count'] > 0) {
            return false; // Cannot delete product with stock movements
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            // Log the deletion
            $auditLog = new AuditLog();
            $auditLog->log(
                $_SESSION['user_id'] ?? null,
                'delete',
                'products',
                $id,
                ['id' => $id],
                null
            );
            return true;
        }
        return false;
    }

    public function adjustStock($quantity, $notes = '') {
        $stockMovement = new StockMovement();
        $stockMovement->product_id = $this->id;
        $stockMovement->type = $quantity > 0 ? 'in' : 'out';
        $stockMovement->quantity = abs($quantity);
        $stockMovement->reference_type = 'adjustment';
        $stockMovement->reference_id = $this->id;
        $stockMovement->notes = $notes;
        
        return $stockMovement->create();
    }

    public function checkLowStock() {
        $setting = new Setting();
        $threshold = $setting->get('inventory', 'low_stock_threshold', 10);
        
        $query = "SELECT p.*, c.name as category_name 
                 FROM " . $this->table_name . " p
                 LEFT JOIN categories c ON p.category_id = c.id
                 WHERE p.stock <= :threshold
                 AND p.stock <= p.min_stock";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':threshold', $threshold);
        $stmt->execute();
        
        return $stmt;
    }

    public function getTopSelling($limit = 10, $days = 30) {
        $query = "SELECT p.*, c.name as category_name,
                    SUM(CASE WHEN sm.type = 'out' THEN sm.quantity ELSE 0 END) as total_sold,
                    COUNT(DISTINCT o.id) as order_count
                 FROM " . $this->table_name . " p
                 LEFT JOIN categories c ON p.category_id = c.id
                 LEFT JOIN stock_movements sm ON p.id = sm.product_id
                 LEFT JOIN orders o ON sm.reference_type = 'order' AND sm.reference_id = o.id
                 WHERE sm.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL :days DAY)
                 GROUP BY p.id
                 ORDER BY total_sold DESC
                 LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }
}
?>
