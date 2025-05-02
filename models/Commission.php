<?php
require_once __DIR__ . '/../config/database.php';

class Commission {
    private $conn;
    private $table_name = "commissions";

    public $id;
    public $user_id;
    public $product_id;
    public $category_id;
    public $rate;
    public $min_cap;
    public $max_cap;
    public $created_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
            (user_id, product_id, category_id, rate, min_cap, max_cap) 
            VALUES (:user_id, :product_id, :category_id, :rate, :min_cap, :max_cap)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':product_id', $this->product_id);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':rate', $this->rate);
        $stmt->bindParam(':min_cap', $this->min_cap);
        $stmt->bindParam(':max_cap', $this->max_cap);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET 
            user_id = :user_id, 
            product_id = :product_id, 
            category_id = :category_id, 
            rate = :rate, 
            min_cap = :min_cap, 
            max_cap = :max_cap 
            WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':product_id', $this->product_id);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':rate', $this->rate);
        $stmt->bindParam(':min_cap', $this->min_cap);
        $stmt->bindParam(':max_cap', $this->max_cap);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>
