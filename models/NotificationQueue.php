<?php
require_once __DIR__ . '/../config/database.php';

class NotificationQueue {
    private $conn;
    private $table_name = "notification_queue";
    private $max_attempts = 3;
    private $retry_delay = 300; // 5 minutes in seconds

    public $id;
    public $type;
    public $channel;
    public $recipient;
    public $content;
    public $scheduled_for;
    public $attempts;
    public $last_attempt;
    public $error_message;
    public $status;
    public $created_at;
    public $updated_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function enqueue($data) {
        $query = "INSERT INTO " . $this->table_name . "
            (type, channel, recipient, content, scheduled_for, status) 
            VALUES 
            (:type, :channel, :recipient, :content, :scheduled_for, :status)";

        $stmt = $this->conn->prepare($query);

        // Clean and sanitize data
        $data['content'] = strip_tags($data['content']);
        $data['scheduled_for'] = $data['scheduled_for'] ?? date('Y-m-d H:i:s');
        $data['status'] = 'pending';

        // Bind values
        $stmt->bindParam(':type', $data['type']);
        $stmt->bindParam(':channel', $data['channel']);
        $stmt->bindParam(':recipient', $data['recipient']);
        $stmt->bindParam(':content', $data['content']);
        $stmt->bindParam(':scheduled_for', $data['scheduled_for']);
        $stmt->bindParam(':status', $data['status']);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function getPendingNotifications() {
        $query = "SELECT * FROM " . $this->table_name . "
                WHERE status = 'pending'
                AND scheduled_for <= NOW()
                AND (attempts < :max_attempts OR attempts IS NULL)
                ORDER BY scheduled_for ASC
                LIMIT 50";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':max_attempts', $this->max_attempts);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAsProcessing($id) {
        $query = "UPDATE " . $this->table_name . "
                SET status = 'processing',
                    attempts = COALESCE(attempts, 0) + 1,
                    last_attempt = NOW()
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function markAsCompleted($id) {
        $query = "UPDATE " . $this->table_name . "
                SET status = 'completed',
                    updated_at = NOW()
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function markAsFailed($id, $error) {
        $query = "UPDATE " . $this->table_name . "
                SET status = :status,
                    error_message = :error_message,
                    scheduled_for = :next_attempt,
                    updated_at = NOW()
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $currentAttempts = $this->getAttempts($id);
        $status = $currentAttempts >= $this->max_attempts ? 'failed' : 'pending';
        $nextAttempt = date('Y-m-d H:i:s', time() + $this->retry_delay);

        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':error_message', $error);
        $stmt->bindParam(':next_attempt', $nextAttempt);

        return $stmt->execute();
    }

    private function getAttempts($id) {
        $query = "SELECT attempts FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['attempts'] ?? 0;
    }

    public function cleanupOldRecords($days = 30) {
        $query = "DELETE FROM " . $this->table_name . "
                WHERE (status = 'completed' OR status = 'failed')
                AND created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days);
        return $stmt->execute();
    }

    public function getQueueStats() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    AVG(CASE WHEN status = 'completed' 
                        THEN TIMESTAMPDIFF(SECOND, created_at, updated_at) 
                        END) as avg_processing_time
                FROM " . $this->table_name;

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getFailureStats() {
        $query = "SELECT 
                    error_message,
                    COUNT(*) as count,
                    MAX(updated_at) as last_occurrence
                FROM " . $this->table_name . "
                WHERE status = 'failed'
                GROUP BY error_message
                ORDER BY count DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getQueueByType() {
        $query = "SELECT 
                    type,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                FROM " . $this->table_name . "
                GROUP BY type
                ORDER BY total DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getQueueByChannel() {
        $query = "SELECT 
                    channel,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    AVG(CASE WHEN status = 'completed' 
                        THEN TIMESTAMPDIFF(SECOND, created_at, updated_at) 
                        END) as avg_processing_time
                FROM " . $this->table_name . "
                GROUP BY channel
                ORDER BY total DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function retryFailed() {
        $query = "UPDATE " . $this->table_name . "
                SET status = 'pending',
                    attempts = 0,
                    error_message = NULL,
                    scheduled_for = NOW(),
                    updated_at = NOW()
                WHERE status = 'failed'
                AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }

    public function setMaxAttempts($attempts) {
        $this->max_attempts = $attempts;
    }

    public function setRetryDelay($seconds) {
        $this->retry_delay = $seconds;
    }
}
?>
