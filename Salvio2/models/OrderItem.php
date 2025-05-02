<?php
require_once __DIR__ . '/../config/database.php';

class OrderItem {
    private $conn;
    private $table_name = "order_items";

    public $id;
    public $order_id;
    public $product_id;
    public $quantity;
    public $original_price;
    public $adjusted_price;
    public $adjustment_reason;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
            (order_id, product_id, quantity, original_price, adjusted_price, adjustment_reason) 
            VALUES (:order_id, :product_id, :quantity, :original_price, :adjusted_price, :adjustment_reason)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':order_id', $this->order_id);
        $stmt->bindParam(':product_id', $this->product_id);
        $stmt->bindParam(':quantity', $this->quantity);
        $stmt->bindParam(':original_price', $this->original_price);
        $stmt->bindParam(':adjusted_price', $this->adjusted_price);
        $stmt->bindParam(':adjustment_reason', $this->adjustment_reason);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function readAllByOrder($order_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE order_id = :order_id ORDER BY id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
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
            order_id = :order_id, 
            product_id = :product_id, 
            quantity = :quantity, 
            original_price = :original_price, 
            adjusted_price = :adjusted_price, 
            adjustment_reason = :adjustment_reason 
            WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':order_id', $this->order_id);
        $stmt->bindParam(':product_id', $this->product_id);
        $stmt->bindParam(':quantity', $this->quantity);
        $stmt->bindParam(':original_price', $this->original_price);
        $stmt->bindParam(':adjusted_price', $this->adjusted_price);
        $stmt->bindParam(':adjustment_reason', $this->adjustment_reason);
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
