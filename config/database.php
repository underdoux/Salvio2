<?php
class Database {
    private $host = "localhost";
    private $db_name = "pos_pharma";
    private $username = "root";
    private $password = "";
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
        } catch(PDOException $e) {
            error_log("Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }

        return $this->conn;
    }

    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    public function commit() {
        return $this->conn->commit();
    }

    public function rollback() {
        return $this->conn->rollBack();
    }

    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }

    public function execute($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            throw new Exception("Database query failed.");
        }
    }

    public function query($sql) {
        try {
            return $this->conn->query($sql);
        } catch(PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            throw new Exception("Database query failed.");
        }
    }

    public function fetchAll($sql, $params = []) {
        try {
            $stmt = $this->execute($sql, $params);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            throw new Exception("Database query failed.");
        }
    }

    public function fetch($sql, $params = []) {
        try {
            $stmt = $this->execute($sql, $params);
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            throw new Exception("Database query failed.");
        }
    }

    public function fetchColumn($sql, $params = []) {
        try {
            $stmt = $this->execute($sql, $params);
            return $stmt->fetchColumn();
        } catch(PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            throw new Exception("Database query failed.");
        }
    }

    public function count($sql, $params = []) {
        try {
            $stmt = $this->execute($sql, $params);
            return $stmt->rowCount();
        } catch(PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            throw new Exception("Database query failed.");
        }
    }

    public function insert($table, $data) {
        try {
            $fields = array_keys($data);
            $values = array_values($data);
            $placeholders = str_repeat('?,', count($fields) - 1) . '?';
            
            $sql = "INSERT INTO {$table} (" . implode(',', $fields) . ") VALUES ({$placeholders})";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($values);
            
            return $this->conn->lastInsertId();
        } catch(PDOException $e) {
            error_log("Insert Error: " . $e->getMessage());
            throw new Exception("Database insert failed.");
        }
    }

    public function update($table, $data, $where) {
        try {
            $fields = array_keys($data);
            $set = implode('=?,', $fields) . '=?';
            $values = array_values($data);
            
            $whereFields = array_keys($where);
            $whereClause = implode('=? AND ', $whereFields) . '=?';
            $whereValues = array_values($where);
            
            $sql = "UPDATE {$table} SET {$set} WHERE {$whereClause}";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(array_merge($values, $whereValues));
            
            return $stmt->rowCount();
        } catch(PDOException $e) {
            error_log("Update Error: " . $e->getMessage());
            throw new Exception("Database update failed.");
        }
    }

    public function delete($table, $where) {
        try {
            $whereFields = array_keys($where);
            $whereClause = implode('=? AND ', $whereFields) . '=?';
            $whereValues = array_values($where);
            
            $sql = "DELETE FROM {$table} WHERE {$whereClause}";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($whereValues);
            
            return $stmt->rowCount();
        } catch(PDOException $e) {
            error_log("Delete Error: " . $e->getMessage());
            throw new Exception("Database delete failed.");
        }
    }
}
