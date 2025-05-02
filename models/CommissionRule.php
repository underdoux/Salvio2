<?php
require_once __DIR__ . '/../config/database.php';

class CommissionRule {
    private $conn;
    private $table_name = "commission_rules";

    public $id;
    public $type;
    public $reference_id;
    public $rate;
    public $min_amount;
    public $max_amount;
    public $created_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
            (type, reference_id, rate, min_amount, max_amount) 
            VALUES (:type, :reference_id, :rate, :min_amount, :max_amount)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':type', $this->type);
        $stmt->bindParam(':reference_id', $this->reference_id);
        $stmt->bindParam(':rate', $this->rate);
        $stmt->bindParam(':min_amount', $this->min_amount);
        $stmt->bindParam(':max_amount', $this->max_amount);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY type, created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getApplicableRule($product_id, $category_id) {
        // First try to find product-specific rule
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE type = 'product' AND reference_id = :product_id 
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        $rule = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($rule) {
            return $rule;
        }

        // Then try category rule
        if ($category_id) {
            $query = "SELECT * FROM " . $this->table_name . " 
                      WHERE type = 'category' AND reference_id = :category_id 
                      LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->execute();
            $rule = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($rule) {
                return $rule;
            }
        }

        // Finally get global rule
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE type = 'global' 
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET 
            type = :type,
            reference_id = :reference_id,
            rate = :rate,
            min_amount = :min_amount,
            max_amount = :max_amount
            WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':type', $this->type);
        $stmt->bindParam(':reference_id', $this->reference_id);
        $stmt->bindParam(':rate', $this->rate);
        $stmt->bindParam(':min_amount', $this->min_amount);
        $stmt->bindParam(':max_amount', $this->max_amount);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    public function delete($id) {
        // Don't allow deletion if this rule is being used by any commission
        $query = "SELECT COUNT(*) as count FROM commissions WHERE rule_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row['count'] > 0) {
            return false;
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function calculateCommission($amount) {
        $commission = $amount * ($this->rate / 100);
        
        if ($this->min_amount && $commission < $this->min_amount) {
            return $this->min_amount;
        }
        
        if ($this->max_amount && $commission > $this->max_amount) {
            return $this->max_amount;
        }
        
        return $commission;
    }
}
?>
