<?php
class AuditLog {
    private $conn;
    private $table_name = "audit_log";

    public $id;
    public $user_id;
    public $action;
    public $details;
    public $created_at;

    public function __construct() {
        $app = require_once __DIR__ . '/../config/bootstrap.php';
        $this->conn = $app['conn'];
    }

    public function log($userId, $action, $details = []) {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                    (user_id, action, details) 
                    VALUES (:user_id, :action, :details)";

            $stmt = $this->conn->prepare($query);

            // Convert details array to JSON string
            $detailsJson = json_encode($details);

            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':details', $detailsJson);

            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error logging audit: " . $e->getMessage());
            return false;
        }
    }

    public function getByUser($userId, $limit = 100) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                     WHERE user_id = :user_id 
                     ORDER BY created_at DESC 
                     LIMIT :limit";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user audit logs: " . $e->getMessage());
            return [];
        }
    }

    public function getByAction($action, $limit = 100) {
        try {
            $query = "SELECT al.*, u.username 
                     FROM " . $this->table_name . " al
                     JOIN users u ON al.user_id = u.id 
                     WHERE al.action = :action 
                     ORDER BY al.created_at DESC 
                     LIMIT :limit";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting action audit logs: " . $e->getMessage());
            return [];
        }
    }

    public function getRecent($limit = 100) {
        try {
            $query = "SELECT al.*, u.username 
                     FROM " . $this->table_name . " al
                     JOIN users u ON al.user_id = u.id 
                     ORDER BY al.created_at DESC 
                     LIMIT :limit";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting recent audit logs: " . $e->getMessage());
            return [];
        }
    }

    public function search($params = [], $limit = 100) {
        try {
            $conditions = [];
            $parameters = [];

            if (!empty($params['user_id'])) {
                $conditions[] = "al.user_id = :user_id";
                $parameters[':user_id'] = $params['user_id'];
            }

            if (!empty($params['action'])) {
                $conditions[] = "al.action = :action";
                $parameters[':action'] = $params['action'];
            }

            if (!empty($params['date_from'])) {
                $conditions[] = "al.created_at >= :date_from";
                $parameters[':date_from'] = $params['date_from'];
            }

            if (!empty($params['date_to'])) {
                $conditions[] = "al.created_at <= :date_to";
                $parameters[':date_to'] = $params['date_to'];
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            $query = "SELECT al.*, u.username 
                     FROM " . $this->table_name . " al
                     JOIN users u ON al.user_id = u.id 
                     {$whereClause}
                     ORDER BY al.created_at DESC 
                     LIMIT :limit";

            $stmt = $this->conn->prepare($query);
            
            foreach ($parameters as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching audit logs: " . $e->getMessage());
            return [];
        }
    }
}
