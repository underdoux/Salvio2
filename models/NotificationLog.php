<?php
require_once __DIR__ . '/../config/database.php';

class NotificationLog {
    private $conn;
    private $table_name = "notification_logs";

    public $id;
    public $type;
    public $channel;
    public $recipient;
    public $content;
    public $status;
    public $error_message;
    public $sent_at;
    public $created_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . "
            (type, channel, recipient, content, status, error_message, sent_at) 
            VALUES 
            (:type, :channel, :recipient, :content, :status, :error_message, :sent_at)";

        $stmt = $this->conn->prepare($query);

        // Clean and sanitize data
        $data['content'] = strip_tags($data['content']);
        $data['status'] = $data['status'] ?? 'success';
        $data['error_message'] = $data['error_message'] ?? null;
        $data['sent_at'] = $data['sent_at'] ?? date('Y-m-d H:i:s');

        // Bind values
        $stmt->bindParam(':type', $data['type']);
        $stmt->bindParam(':channel', $data['channel']);
        $stmt->bindParam(':recipient', $data['recipient']);
        $stmt->bindParam(':content', $data['content']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':error_message', $data['error_message']);
        $stmt->bindParam(':sent_at', $data['sent_at']);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function read($params = []) {
        $query = "SELECT * FROM " . $this->table_name;
        $conditions = [];
        $bindings = [];

        if (!empty($params['type'])) {
            $conditions[] = "type = :type";
            $bindings[':type'] = $params['type'];
        }

        if (!empty($params['channel'])) {
            $conditions[] = "channel = :channel";
            $bindings[':channel'] = $params['channel'];
        }

        if (!empty($params['recipient'])) {
            $conditions[] = "recipient = :recipient";
            $bindings[':recipient'] = $params['recipient'];
        }

        if (!empty($params['status'])) {
            $conditions[] = "status = :status";
            $bindings[':status'] = $params['status'];
        }

        if (!empty($params['date_from'])) {
            $conditions[] = "sent_at >= :date_from";
            $bindings[':date_from'] = $params['date_from'];
        }

        if (!empty($params['date_to'])) {
            $conditions[] = "sent_at <= :date_to";
            $bindings[':date_to'] = $params['date_to'];
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        // Add sorting
        $query .= " ORDER BY sent_at DESC";

        // Add pagination
        if (!empty($params['page']) && !empty($params['per_page'])) {
            $offset = ($params['page'] - 1) * $params['per_page'];
            $query .= " LIMIT :offset, :limit";
            $bindings[':offset'] = $offset;
            $bindings[':limit'] = $params['per_page'];
        }

        $stmt = $this->conn->prepare($query);
        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt;
    }

    public function getStats($params = []) {
        $query = "SELECT 
                    COUNT(*) as total_notifications,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    COUNT(DISTINCT recipient) as unique_recipients
                FROM " . $this->table_name;

        $conditions = [];
        $bindings = [];

        if (!empty($params['type'])) {
            $conditions[] = "type = :type";
            $bindings[':type'] = $params['type'];
        }

        if (!empty($params['channel'])) {
            $conditions[] = "channel = :channel";
            $bindings[':channel'] = $params['channel'];
        }

        if (!empty($params['date_from'])) {
            $conditions[] = "sent_at >= :date_from";
            $bindings[':date_from'] = $params['date_from'];
        }

        if (!empty($params['date_to'])) {
            $conditions[] = "sent_at <= :date_to";
            $bindings[':date_to'] = $params['date_to'];
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $this->conn->prepare($query);
        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getDeliveryRate($params = []) {
        $stats = $this->getStats($params);
        if ($stats['total_notifications'] > 0) {
            return ($stats['successful'] / $stats['total_notifications']) * 100;
        }
        return 0;
    }

    public function getFailureReasons($params = []) {
        $query = "SELECT 
                    error_message,
                    COUNT(*) as count
                FROM " . $this->table_name . "
                WHERE status = 'failed'";

        $conditions = [];
        $bindings = [];

        if (!empty($params['type'])) {
            $conditions[] = "type = :type";
            $bindings[':type'] = $params['type'];
        }

        if (!empty($params['channel'])) {
            $conditions[] = "channel = :channel";
            $bindings[':channel'] = $params['channel'];
        }

        if (!empty($params['date_from'])) {
            $conditions[] = "sent_at >= :date_from";
            $bindings[':date_from'] = $params['date_from'];
        }

        if (!empty($params['date_to'])) {
            $conditions[] = "sent_at <= :date_to";
            $bindings[':date_to'] = $params['date_to'];
        }

        if (!empty($conditions)) {
            $query .= " AND " . implode(" AND ", $conditions);
        }

        $query .= " GROUP BY error_message ORDER BY count DESC";

        $stmt = $this->conn->prepare($query);
        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNotificationsByRecipient($params = []) {
        $query = "SELECT 
                    recipient,
                    COUNT(*) as total_notifications,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful,
                    MAX(sent_at) as last_notification
                FROM " . $this->table_name;

        $conditions = [];
        $bindings = [];

        if (!empty($params['type'])) {
            $conditions[] = "type = :type";
            $bindings[':type'] = $params['type'];
        }

        if (!empty($params['channel'])) {
            $conditions[] = "channel = :channel";
            $bindings[':channel'] = $params['channel'];
        }

        if (!empty($params['date_from'])) {
            $conditions[] = "sent_at >= :date_from";
            $bindings[':date_from'] = $params['date_from'];
        }

        if (!empty($params['date_to'])) {
            $conditions[] = "sent_at <= :date_to";
            $bindings[':date_to'] = $params['date_to'];
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " GROUP BY recipient ORDER BY total_notifications DESC";

        if (!empty($params['limit'])) {
            $query .= " LIMIT :limit";
            $bindings[':limit'] = $params['limit'];
        }

        $stmt = $this->conn->prepare($query);
        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNotificationVolume($params = []) {
        $query = "SELECT 
                    DATE(sent_at) as date,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                FROM " . $this->table_name;

        $conditions = [];
        $bindings = [];

        if (!empty($params['type'])) {
            $conditions[] = "type = :type";
            $bindings[':type'] = $params['type'];
        }

        if (!empty($params['channel'])) {
            $conditions[] = "channel = :channel";
            $bindings[':channel'] = $params['channel'];
        }

        if (!empty($params['date_from'])) {
            $conditions[] = "sent_at >= :date_from";
            $bindings[':date_from'] = $params['date_from'];
        }

        if (!empty($params['date_to'])) {
            $conditions[] = "sent_at <= :date_to";
            $bindings[':date_to'] = $params['date_to'];
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " GROUP BY DATE(sent_at) ORDER BY date DESC";

        $stmt = $this->conn->prepare($query);
        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
