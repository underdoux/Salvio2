<?php
require_once __DIR__ . '/../config/database.php';

class ProfitShare {
    private $conn;
    private $table_name = "profit_shares";

    public $id;
    public $investor_id;
    public $capital_percentage;
    public $created_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (investor_id, capital_percentage) VALUES (:investor_id, :capital_percentage)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':investor_id', $this->investor_id);
        $stmt->bindParam(':capital_percentage', $this->capital_percentage);

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
        $query = "UPDATE " . $this->table_name . " SET investor_id = :investor_id, capital_percentage = :capital_percentage WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':investor_id', $this->investor_id);
        $stmt->bindParam(':capital_percentage', $this->capital_percentage);
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
