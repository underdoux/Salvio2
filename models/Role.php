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

    public function findByName($name) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE name = :name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($name) {
        $query = "INSERT INTO " . $this->table_name . " (name) VALUES (:name)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function update($id, $name) {
        $query = "UPDATE " . $this->table_name . " SET name = :name WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    public function delete($id) {
        // First check if any users are using this role
        $query = "SELECT COUNT(*) FROM users WHERE role_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Cannot delete role: It is assigned to one or more users.");
        }

        // Delete role permissions first
        $query = "DELETE FROM role_permissions WHERE role_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        // Then delete the role
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    public function getPermissions($role_id) {
        $query = "SELECT p.* 
                 FROM permissions p
                 JOIN role_permissions rp ON p.id = rp.permission_id
                 WHERE rp.role_id = :role_id
                 ORDER BY p.name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role_id', $role_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setPermissions($role_id, $permission_ids) {
        try {
            $this->conn->beginTransaction();

            // Remove existing permissions
            $query = "DELETE FROM role_permissions WHERE role_id = :role_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':role_id', $role_id);
            $stmt->execute();

            // Add new permissions
            if (!empty($permission_ids)) {
                $query = "INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)";
                $stmt = $this->conn->prepare($query);
                
                foreach ($permission_ids as $permission_id) {
                    $stmt->bindParam(':role_id', $role_id);
                    $stmt->bindParam(':permission_id', $permission_id);
                    $stmt->execute();
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function getAllPermissions() {
        $query = "SELECT * FROM permissions ORDER BY name";
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
        
        return (bool) $stmt->fetchColumn();
    }

    public function getRoleUsers($role_id) {
        $query = "SELECT id, username, created_at 
                 FROM users 
                 WHERE role_id = :role_id 
                 ORDER BY username";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role_id', $role_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
