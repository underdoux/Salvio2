<?php
require_once __DIR__ . '/../config/database.php';

class Investor {
    private $conn;
    private $table_name = "investors";

    public $id;
    public $name;
    public $percentage;
    public $active;
    public $created_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create() {
        // Validate total percentage doesn't exceed 100%
        if (!$this->validateTotalPercentage()) {
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . " 
            (name, percentage, active) 
            VALUES (:name, :percentage, :active)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':percentage', $this->percentage);
        $stmt->bindParam(':active', $this->active, PDO::PARAM_BOOL);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    private function validateTotalPercentage() {
        // Get sum of existing active investors' percentages
        $query = "SELECT SUM(percentage) as total FROM " . $this->table_name . " 
                 WHERE active = true" . 
                 ($this->id ? " AND id != :id" : "");
        
        $stmt = $this->conn->prepare($query);
        if ($this->id) {
            $stmt->bindParam(':id', $this->id);
        }
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $current_total = $row['total'] ?? 0;
        
        // Check if adding new percentage would exceed 100%
        return ($current_total + $this->percentage) <= 100;
    }

    public function readAll($active_only = false) {
        $query = "SELECT * FROM " . $this->table_name;
        if ($active_only) {
            $query .= " WHERE active = true";
        }
        $query .= " ORDER BY percentage DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE id = :id LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update() {
        // Validate total percentage doesn't exceed 100%
        if (!$this->validateTotalPercentage()) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . " 
                 SET name = :name, 
                     percentage = :percentage, 
                     active = :active 
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':percentage', $this->percentage);
        $stmt->bindParam(':active', $this->active, PDO::PARAM_BOOL);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    public function delete($id) {
        // Check if investor has any profit shares
        $query = "SELECT COUNT(*) as count FROM profit_shares WHERE investor_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row['count'] > 0) {
            // If investor has profit shares, just deactivate instead of delete
            $query = "UPDATE " . $this->table_name . " 
                     SET active = false 
                     WHERE id = :id";
        } else {
            // If no profit shares, can safely delete
            $query = "DELETE FROM " . $this->table_name . " 
                     WHERE id = :id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getTotalActivePercentage() {
        $query = "SELECT SUM(percentage) as total FROM " . $this->table_name . " 
                 WHERE active = true";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    public function getInvestorShares($period_start, $period_end) {
        $query = "SELECT 
                    i.name,
                    i.percentage,
                    COUNT(ps.id) as total_distributions,
                    SUM(ps.amount) as total_received,
                    MIN(ps.paid_date) as first_payment,
                    MAX(ps.paid_date) as last_payment
                 FROM " . $this->table_name . " i
                 LEFT JOIN profit_shares ps ON i.id = ps.investor_id
                 LEFT JOIN profit_distributions pd ON ps.distribution_id = pd.id
                 WHERE pd.period_start >= :period_start 
                 AND pd.period_end <= :period_end
                 GROUP BY i.id
                 ORDER BY i.percentage DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':period_start', $period_start);
        $stmt->bindParam(':period_end', $period_end);
        
        $stmt->execute();
        return $stmt;
    }
}
?>
