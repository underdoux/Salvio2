<?php
class Database {
    private $db_path;
    public $conn;

    public function getConnection() {
        $this->conn = null;
        $this->db_path = __DIR__ . '/../database/pos_pharma.sqlite';
        
        // Create database directory if it doesn't exist
        if (!file_exists(dirname($this->db_path))) {
            mkdir(dirname($this->db_path), 0777, true);
        }
        
        try {
            $this->conn = new PDO("sqlite:" . $this->db_path);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>
