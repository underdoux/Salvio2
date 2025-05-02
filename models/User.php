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
                 LEFT JOIN roles r ON u.role_id = r.id
                 WHERE u.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByUsername($username) {
        $query = "SELECT u.*, r.name as role_name 
                 FROM " . $this->table_name . " u
                 LEFT JOIN roles r ON u.role_id = r.id
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
        
        // Bind values
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':role_id', $data['role_id']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    public function update($id, $data) {
        $fields = [];
        $values = [];
        
        // Build update fields
        foreach ($data as $key => $value) {
            if ($key !== 'id' && $key !== 'password') {
                $fields[] = "$key = :$key";
                $values[":$key"] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $query = "UPDATE " . $this->table_name . "
                 SET " . implode(', ', $fields) . "
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $values[':id'] = $id;
        
        return $stmt->execute($values);
    }

    public function updatePassword($id, $password_hash) {
        $query = "UPDATE " . $this->table_name . "
                 SET password = :password
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute([
            ':password' => $password_hash,
            ':id' => $id
        ]);
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':id' => $id]);
    }

    public function getRoleName($role_id) {
        $query = "SELECT name FROM roles WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $role_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['name'] : null;
    }

    public function getPermissions($role_id) {
        $query = "SELECT p.name
                 FROM permissions p
                 JOIN role_permissions rp ON p.id = rp.permission_id
                 WHERE rp.role_id = :role_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':role_id' => $role_id]);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function hasPermission($user_id, $permission) {
        $query = "SELECT COUNT(*) FROM users u
                 JOIN roles r ON u.role_id = r.id
                 JOIN role_permissions rp ON r.id = rp.role_id
                 JOIN permissions p ON rp.permission_id = p.id
                 WHERE u.id = :user_id AND p.name = :permission";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id' => $user_id,
            ':permission' => $permission
        ]);
        
        return (bool) $stmt->fetchColumn();
    }

    public function getAll($page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        $where = '';
        $params = [];
        
        if ($search) {
            $where = "WHERE u.username LIKE :search";
            $params[':search'] = "%$search%";
        }
        
        $query = "SELECT u.*, r.name as role_name
                 FROM " . $this->table_name . " u
                 LEFT JOIN roles r ON u.role_id = r.id
                 $where
                 ORDER BY u.created_at DESC
                 LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        if ($search) {
            $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        }
        
        $stmt->execute();
        
        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $this->getTotal($search)
        ];
    }

    private function getTotal($search = '') {
        $where = '';
        $params = [];
        
        if ($search) {
            $where = "WHERE username LIKE :search";
            $params[':search'] = "%$search%";
        }
        
        $query = "SELECT COUNT(*) FROM " . $this->table_name . " $where";
        $stmt = $this->conn->prepare($query);
        
        if ($search) {
            $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        }
        
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}
