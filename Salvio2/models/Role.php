<?php
require_once __DIR__ . '/../config/database.php';

class Role {
    private $conn;
    private $table_name = "roles";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function findById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hasPermission($role_id, $permission_name) {
        $query = "SELECT COUNT(*) FROM role_permissions rp 
                 JOIN permissions p ON rp.permission_id = p.id 
                 WHERE rp.role_id = :role_id AND p.name = :permission_name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role_id', $role_id);
        $stmt->bindParam(':permission_name', $permission_name);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }

    public function getPermissions($role_id) {
        $query = "SELECT p.* FROM permissions p 
                 JOIN role_permissions rp ON p.id = rp.permission_id 
                 WHERE rp.role_id = :role_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role_id', $role_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updatePermissions($role_id, $permissions) {
        // Start transaction
        $this->conn->beginTransaction();
        
        try {
            // Delete existing permissions
            $query = "DELETE FROM role_permissions WHERE role_id = :role_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':role_id', $role_id);
            $stmt->execute();
            
            // Insert new permissions
            $query = "INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)";
            $stmt = $this->conn->prepare($query);
            
            foreach ($permissions as $permission_id) {
                $stmt->bindParam(':role_id', $role_id);
                $stmt->bindParam(':permission_id', $permission_id);
                $stmt->execute();
            }
            
            // Commit transaction
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            throw $e;
        }
    }
}
