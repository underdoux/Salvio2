<?php

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    private static $instance = null;

    public function __construct() {
        $config = require __DIR__ . '/app.php';
        $this->host = $config['db']['host'];
        $this->db_name = $config['db']['database'];
        $this->username = $config['db']['username'];
        $this->password = $config['db']['password'];
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ];
                
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            } catch(PDOException $e) {
                error_log("Database Connection Error: " . $e->getMessage());
                throw new Exception("Database connection failed. Please check your configuration.");
            }
        }
        return $this->conn;
    }

    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }

    public function commit() {
        return $this->getConnection()->commit();
    }

    public function rollback() {
        return $this->getConnection()->rollBack();
    }

    public function prepare($sql) {
        return $this->getConnection()->prepare($sql);
    }

    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }

    public function quote($value) {
        return $this->getConnection()->quote($value);
    }

    public function close() {
        $this->conn = null;
    }

    public function __destruct() {
        $this->close();
    }

    // Helper methods for common queries
    public function fetchAll($sql, $params = []) {
        try {
            $stmt = $this->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            throw new Exception("Failed to execute query.");
        }
    }

    public function fetchOne($sql, $params = []) {
        try {
            $stmt = $this->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            throw new Exception("Failed to execute query.");
        }
    }

    public function execute($sql, $params = []) {
        try {
            $stmt = $this->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            throw new Exception("Failed to execute query.");
        }
    }

    public function count($sql, $params = []) {
        try {
            $stmt = $this->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            throw new Exception("Failed to execute query.");
        }
    }
}
