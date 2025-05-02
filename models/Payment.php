<?php
require_once __DIR__ . '/../config/database.php';

class Payment {
    private $conn;
    private $table_name = "payments";

    public $id;
    public $order_id;
    public $amount;
    public $payment_date;
    public $payment_method;
    public $notes;
    public $created_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
            (order_id, amount, payment_date, payment_method, notes) 
            VALUES (:order_id, :amount, :payment_date, :payment_method, :notes)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':order_id', $this->order_id);
        $stmt->bindParam(':amount', $this->amount);
        $stmt->bindParam(':payment_date', $this->payment_date);
        $stmt->bindParam(':payment_method', $this->payment_method);
        $stmt->bindParam(':notes', $this->notes);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            $this->updateOrderPaymentStatus();
            return true;
        }
        return false;
    }

    private function updateOrderPaymentStatus() {
        // Calculate total paid amount for this order
        $query = "SELECT SUM(amount) as total_paid FROM " . $this->table_name . " 
                  WHERE order_id = :order_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $this->order_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_paid = $row['total_paid'];

        // Get order total
        $query = "SELECT total FROM orders WHERE id = :order_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $this->order_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $order_total = $row['total'];

        // Determine payment status
        $status = 'pending';
        if ($total_paid >= $order_total) {
            $status = 'completed';
        } else if ($total_paid > 0) {
            $status = 'partial';
        }

        // Update order payment status
        $query = "UPDATE orders SET payment_status = :status 
                  WHERE id = :order_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':order_id', $this->order_id);
        return $stmt->execute();
    }

    public function readByOrder($order_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE order_id = :order_id 
                  ORDER BY payment_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        
        return $stmt;
    }

    public function getTotalPaidAmount($order_id) {
        $query = "SELECT SUM(amount) as total_paid FROM " . $this->table_name . " 
                  WHERE order_id = :order_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_paid'] ?? 0;
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            // After deleting a payment, update the order's payment status
            $this->updateOrderPaymentStatus();
            return true;
        }
        return false;
    }
}
?>
