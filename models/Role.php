<?php
class Role {
    private $conn;
    private $table_name = "roles";

    public $id;
    public $name;
    public $created_at;

    public function __construct() {
        $app = require_once __DIR__ . '/../config/bootstrap.php';
        $this->conn = $app['conn'];
    }

    public function create($name) {
        try {
            $query = "INSERT INTO " . $this->table_name . " (name) VALUES (:name)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $name);

            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function update($id, $name) {
        try {
            $query = "UPDATE " . $this->table_name . " SET name = :name WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            // Check if role is in use
            $checkQuery = "SELECT COUNT(*) FROM users WHERE role_id = :id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            
            if ($checkStmt->fetchColumn() > 0) {
                return false; // Role is in use
            }

            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function getById($id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function getByName($name) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE name = :name LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function getAll() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " ORDER BY name ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function getUserCount($roleId) {
        try {
            $query = "SELECT COUNT(*) as count FROM users WHERE role_id = :role_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':role_id', $roleId);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return 0;
        }
    }

    public function getRoleStats() {
        try {
            $query = "SELECT r.*, COUNT(u.id) as user_count 
                     FROM " . $this->table_name . " r 
                     LEFT JOIN users u ON r.id = u.role_id 
                     GROUP BY r.id 
                     ORDER BY r.name ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function ensureDefaultRoles() {
        try {
            $defaultRoles = ['admin', 'cashier', 'sales'];
            
            foreach ($defaultRoles as $roleName) {
                // Check if role exists
                $role = $this->getByName($roleName);
                
                // If role doesn't exist, create it
                if (!$role) {
                    $this->create($roleName);
                }
            }
            
            return true;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}
