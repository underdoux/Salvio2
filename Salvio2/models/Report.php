<?php
require_once __DIR__ . '/../config/database.php';

class Report {
    private $conn;
    private $table_name = "reports";

    public $id;
    public $type;
    public $data;
    public $generated_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (type, data) VALUES (:type, :data)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':type', $this->type);
        $stmt->bindParam(':data', $this->data);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY generated_at DESC";
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
        $query = "UPDATE " . $this->table_name . " SET type = :type, data = :data WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':type', $this->type);
        $stmt->bindParam(':data', $this->data);
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
