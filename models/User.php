<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $password;
    public $role_id;
    public $created_at;

    public function __construct() {
        $app = require_once __DIR__ . '/../config/bootstrap.php';
        $this->conn = $app['conn'];
    }

    public function create($data) {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                    (username, password, role_id) 
                    VALUES (:username, :password, :role_id)";

            $stmt = $this->conn->prepare($query);

            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            // Bind values
            $stmt->bindParam(':username', $data['username']);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':role_id', $data['role_id']);

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

    public function update($id, $data) {
        try {
            $sets = [];
            $params = [':id' => $id];

            if (isset($data['username'])) {
                $sets[] = "username = :username";
                $params[':username'] = $data['username'];
            }

            if (isset($data['password'])) {
                $sets[] = "password = :password";
                $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            if (isset($data['role_id'])) {
                $sets[] = "role_id = :role_id";
                $params[':role_id'] = $data['role_id'];
            }

            if (empty($sets)) {
                return false;
            }

            $query = "UPDATE " . $this->table_name . " 
                     SET " . implode(", ", $sets) . " 
                     WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            return $stmt->execute($params);

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function getById($id) {
        try {
            $query = "SELECT u.*, r.name as role_name 
                     FROM " . $this->table_name . " u
                     JOIN roles r ON u.role_id = r.id 
                     WHERE u.id = :id 
                     LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function getByUsername($username) {
        try {
            $query = "SELECT u.*, r.name as role_name 
                     FROM " . $this->table_name . " u
                     JOIN roles r ON u.role_id = r.id 
                     WHERE u.username = :username 
                     LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function getAll($limit = 100, $offset = 0) {
        try {
            $query = "SELECT u.*, r.name as role_name 
                     FROM " . $this->table_name . " u
                     JOIN roles r ON u.role_id = r.id 
                     ORDER BY u.created_at DESC 
                     LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function delete($id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function validatePassword($password) {
        // Password must be at least 8 characters long and contain:
        // - At least one uppercase letter
        // - At least one lowercase letter
        // - At least one number
        // - At least one special character
        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
        return preg_match($pattern, $password);
    }

    public function changePassword($id, $currentPassword, $newPassword) {
        try {
            // Get current user data
            $user = $this->getById($id);
            if (!$user) {
                return false;
            }

            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                return false;
            }

            // Validate new password
            if (!$this->validatePassword($newPassword)) {
                return false;
            }

            // Update password
            return $this->update($id, ['password' => $newPassword]);

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function search($params = [], $limit = 100, $offset = 0) {
        try {
            $conditions = [];
            $parameters = [
                ':limit' => $limit,
                ':offset' => $offset
            ];

            if (!empty($params['username'])) {
                $conditions[] = "u.username LIKE :username";
                $parameters[':username'] = '%' . $params['username'] . '%';
            }

            if (!empty($params['role_id'])) {
                $conditions[] = "u.role_id = :role_id";
                $parameters[':role_id'] = $params['role_id'];
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            $query = "SELECT u.*, r.name as role_name 
                     FROM " . $this->table_name . " u
                     JOIN roles r ON u.role_id = r.id 
                     {$whereClause}
                     ORDER BY u.created_at DESC 
                     LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($query);
            $stmt->execute($parameters);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }
}
