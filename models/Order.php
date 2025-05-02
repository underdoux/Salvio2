<?php
require_once __DIR__ . '/../config/database.php';

class Order {
    private $conn;
    private $table_name = "orders";

    public $id;
    public $user_id;
    public $status;
    public $total;
    public $tax_percentage;
    public $payment_type;
    public $created_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
            (user_id, status, total, tax_percentage, payment_type) 
            VALUES (:user_id, :status, :total, :tax_percentage, :payment_type)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':total', $this->total);
        $stmt->bindParam(':tax_percentage', $this->tax_percentage);
        $stmt->bindParam(':payment_type', $this->payment_type);

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
            status = :status, 
            total = :total, 
            tax_percentage = :tax_percentage, 
            payment_type = :payment_type 
            WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':total', $this->total);
        $stmt->bindParam(':tax_percentage', $this->tax_percentage);
        $stmt->bindParam(':payment_type', $this->payment_type);
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
