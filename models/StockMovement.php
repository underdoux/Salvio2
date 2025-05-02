<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/AuditLog.php';

class StockMovement {
    private $conn;
    private $table_name = "stock_movements";

    public $id;
    public $product_id;
    public $type;
    public $quantity;
    public $reference_type;
    public $reference_id;
    public $notes;
    public $created_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
            (product_id, type, quantity, reference_type, reference_id, notes) 
            VALUES (:product_id, :type, :quantity, :reference_type, :reference_id, :notes)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':product_id', $this->product_id);
        $stmt->bindParam(':type', $this->type);
        $stmt->bindParam(':quantity', $this->quantity);
        $stmt->bindParam(':reference_type', $this->reference_type);
        $stmt->bindParam(':reference_id', $this->reference_id);
        $stmt->bindParam(':notes', $this->notes);

        // Start transaction
        $this->conn->beginTransaction();

        try {
            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                
                // Update product stock
                if (!$this->updateProductStock()) {
                    $this->conn->rollBack();
                    return false;
                }

                // Log the stock movement
                $auditLog = new AuditLog();
                $auditLog->log(
                    $_SESSION['user_id'] ?? null,
                    $this->type,
                    'products',
                    $this->product_id,
                    ['movement_id' => $this->id, 'quantity' => $this->quantity]
                );

                $this->conn->commit();
                return true;
            }
            
            $this->conn->rollBack();
            return false;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    private function updateProductStock() {
        $query = "UPDATE products 
                 SET stock = stock " . ($this->type === 'in' ? '+' : '-') . " :quantity 
                 WHERE id = :product_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':quantity', $this->quantity);
        $stmt->bindParam(':product_id', $this->product_id);
        
        return $stmt->execute();
    }

    public function getProductMovements($product_id, $start_date = null, $end_date = null) {
        $query = "SELECT sm.*, u.username 
                 FROM " . $this->table_name . " sm
                 LEFT JOIN users u ON u.id = (
                    SELECT user_id FROM audit_logs 
                    WHERE table_name = 'stock_movements' 
                    AND record_id = sm.id 
                    LIMIT 1
                 )
                 WHERE sm.product_id = :product_id";
        
        if ($start_date) {
            $query .= " AND sm.created_at >= :start_date";
        }
        if ($end_date) {
            $query .= " AND sm.created_at <= :end_date";
        }
        
        $query .= " ORDER BY sm.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        if ($start_date) {
            $stmt->bindParam(':start_date', $start_date);
        }
        if ($end_date) {
            $stmt->bindParam(':end_date', $end_date);
        }
        
        $stmt->execute();
        return $stmt;
    }

    public function getLowStockProducts($threshold = null) {
        if ($threshold === null) {
            // Get threshold from settings
            require_once __DIR__ . '/Setting.php';
            $setting = new Setting();
            $threshold = $setting->get('inventory', 'low_stock_threshold', 10);
        }

        $query = "SELECT p.*, 
                    c.name as category_name,
                    (SELECT COUNT(*) FROM " . $this->table_name . " 
                     WHERE product_id = p.id 
                     AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)) as movement_count
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 WHERE p.stock <= :threshold
                 ORDER BY p.stock ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':threshold', $threshold);
        $stmt->execute();
        
        return $stmt;
    }

    public function getStockValueReport($date = null) {
        $query = "SELECT 
                    c.name as category_name,
                    COUNT(p.id) as product_count,
                    SUM(p.stock) as total_units,
                    SUM(p.stock * p.price) as total_value
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id";
        
        if ($date) {
            // If date provided, calculate stock as of that date
            $query = "WITH StockAsOf AS (
                        SELECT 
                            product_id,
                            SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END) as stock
                        FROM " . $this->table_name . "
                        WHERE created_at <= :date
                        GROUP BY product_id
                    )
                    SELECT 
                        c.name as category_name,
                        COUNT(p.id) as product_count,
                        SUM(COALESCE(sao.stock, 0)) as total_units,
                        SUM(COALESCE(sao.stock, 0) * p.price) as total_value
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN StockAsOf sao ON p.id = sao.product_id";
        }
        
        $query .= " GROUP BY c.id
                   ORDER BY total_value DESC";
        
        $stmt = $this->conn->prepare($query);
        if ($date) {
            $stmt->bindParam(':date', $date);
        }
        $stmt->execute();
        
        return $stmt;
    }

    public function getMovementTrends($days = 30) {
        $query = "SELECT 
                    DATE(created_at) as date,
                    type,
                    COUNT(*) as movement_count,
                    SUM(quantity) as total_quantity
                 FROM " . $this->table_name . "
                 WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL :days DAY)
                 GROUP BY DATE(created_at), type
                 ORDER BY date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days);
        $stmt->execute();
        
        return $stmt;
    }

    public function validateStock($product_id, $quantity) {
        $query = "SELECT stock FROM products WHERE id = :product_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && $row['stock'] >= $quantity;
    }
}
?>
