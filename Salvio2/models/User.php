<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function findById($id) {
        $query = "SELECT u.*, r.name as role_name 
                 FROM " . $this->table_name . " u
                 JOIN roles r ON u.role_id = r.id
                 WHERE u.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByUsername($username) {
        $query = "SELECT u.*, r.name as role_name 
                 FROM " . $this->table_name . " u
                 JOIN roles r ON u.role_id = r.id
                 WHERE u.username = :username";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                 (username, password, role_id) 
                 VALUES (:username, :password, :role_id)";
        
        $stmt = $this->conn->prepare($query);
        
        // Hash password
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Bind parameters
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':role_id', $data['role_id']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                 SET username = :username, role_id = :role_id 
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':role_id', $data['role_id']);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    public function updatePassword($id, $password_hash) {
        $query = "UPDATE " . $this->table_name . " 
                 SET password = :password 
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    public function delete($id) {
        // Check if user has any related records
        $tables = ['orders', 'audit_log'];
        foreach ($tables as $table) {
            $query = "SELECT COUNT(*) FROM {$table} WHERE user_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Cannot delete user: Has related records in {$table}");
            }
        }
        
        // Delete user
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    public function getAll($page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT u.*, r.name as role_name 
                 FROM " . $this->table_name . " u
                 JOIN roles r ON u.role_id = r.id";
        
        if ($search) {
            $query .= " WHERE u.username LIKE :search";
        }
        
        $query .= " ORDER BY u.username
                   LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        if ($search) {
            $search = "%{$search}%";
            $stmt->bindParam(':search', $search);
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count($search = '') {
        $query = "SELECT COUNT(*) FROM " . $this->table_name;
        
        if ($search) {
            $query .= " WHERE username LIKE :search";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if ($search) {
            $search = "%{$search}%";
            $stmt->bindParam(':search', $search);
        }
        
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }

    public function validateUsername($username, $exclude_id = null) {
        $query = "SELECT COUNT(*) FROM " . $this->table_name . " 
                 WHERE username = :username";
        
        if ($exclude_id) {
            $query .= " AND id != :id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        
        if ($exclude_id) {
            $stmt->bindParam(':id', $exclude_id);
        }
        
        $stmt->execute();
        
        return $stmt->fetchColumn() === 0;
    }
}
