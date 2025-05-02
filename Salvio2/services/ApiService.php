<?php
require_once __DIR__ . '/../config/database.php';

class ApiService {
    private $conn;
    private $currentUser;

    public function __construct($currentUser = null) {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->currentUser = $currentUser;
    }

    public function generateApiKey($name, $scopes = [], $expiresAt = null) {
        if (!$this->currentUser) {
            throw new Exception('User not authenticated');
        }

        // Generate random API key
        $key = bin2hex(random_bytes(32));
        $keyHash = hash('sha256', $key);

        // Insert API key
        $query = "INSERT INTO api_keys 
                (user_id, name, key_hash, scopes, expires_at)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $this->currentUser->id,
            $name,
            $keyHash,
            json_encode($scopes),
            $expiresAt
        ]);

        // Log key generation
        $this->logAction('api_key_generated', [
            'key_id' => $this->conn->lastInsertId(),
            'name' => $name,
            'scopes' => $scopes
        ]);

        return [
            'id' => $this->conn->lastInsertId(),
            'key' => $key, // Return plain key only once
            'name' => $name,
            'scopes' => $scopes,
            'expires_at' => $expiresAt
        ];
    }

    public function listApiKeys($userId = null) {
        $userId = $userId ?? $this->currentUser->id;

        $query = "SELECT id, name, scopes, is_active, expires_at, 
                        last_used_at, total_requests, created_at
                 FROM api_keys
                 WHERE user_id = ?
                 ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function revokeApiKey($keyId) {
        $query = "UPDATE api_keys 
                 SET is_active = 0, 
                     updated_at = NOW()
                 WHERE id = ? AND user_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$keyId, $this->currentUser->id]);

        if ($stmt->rowCount() > 0) {
            $this->logAction('api_key_revoked', ['key_id' => $keyId]);
            return true;
        }
        return false;
    }

    public function createWebhook($data) {
        // Validate URL
        if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid webhook URL');
        }

        // Generate webhook secret
        $secret = bin2hex(random_bytes(32));
        $secretHash = hash('sha256', $secret);

        // Insert webhook
        $query = "INSERT INTO api_webhooks 
                (user_id, name, url, events, secret_hash)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $this->currentUser->id,
            $data['name'],
            $data['url'],
            json_encode($data['events']),
            $secretHash
        ]);

        $webhookId = $this->conn->lastInsertId();

        // Log webhook creation
        $this->logAction('webhook_created', [
            'webhook_id' => $webhookId,
            'name' => $data['name'],
            'events' => $data['events']
        ]);

        return [
            'id' => $webhookId,
            'secret' => $secret // Return secret only once
        ];
    }

    public function listWebhooks() {
        $query = "SELECT id, name, url, events, is_active, 
                        last_triggered_at, failure_count, created_at
                 FROM api_webhooks
                 WHERE user_id = ?
                 ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->currentUser->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function triggerWebhook($event, $payload) {
        // Find webhooks subscribed to this event
        $query = "SELECT id, url, secret_hash 
                 FROM api_webhooks
                 WHERE is_active = 1 
                 AND JSON_CONTAINS(events, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([json_encode($event)]);
        $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($webhooks as $webhook) {
            $this->deliverWebhook($webhook, $event, $payload);
        }
    }

    private function deliverWebhook($webhook, $event, $payload) {
        // Create delivery record
        $query = "INSERT INTO webhook_deliveries 
                (webhook_id, event_type, payload)
                VALUES (?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $webhook['id'],
            $event,
            json_encode($payload)
        ]);

        $deliveryId = $this->conn->lastInsertId();

        // Queue the delivery
        $this->queueWebhookDelivery($deliveryId, $webhook, $event, $payload);
    }

    private function queueWebhookDelivery($deliveryId, $webhook, $event, $payload) {
        // Add to notification queue for processing
        $notificationQueue = new NotificationQueue();
        $notificationQueue->add('webhook_delivery', [
            'delivery_id' => $deliveryId,
            'webhook_id' => $webhook['id'],
            'url' => $webhook['url'],
            'event' => $event,
            'payload' => $payload,
            'signature' => $this->generateSignature($payload, $webhook['secret_hash'])
        ]);
    }

    private function generateSignature($payload, $secretHash) {
        $timestamp = time();
        $toSign = $timestamp . '.' . json_encode($payload);
        return $timestamp . '.' . hash_hmac('sha256', $toSign, $secretHash);
    }

    public function getApiLogs($filters = []) {
        $query = "SELECT al.*, ak.name as key_name, u.username
                 FROM api_access_log al
                 JOIN api_keys ak ON al.api_key_id = ak.id
                 JOIN users u ON ak.user_id = u.id
                 WHERE 1=1";
        $params = [];

        if (isset($filters['api_key_id'])) {
            $query .= " AND al.api_key_id = ?";
            $params[] = $filters['api_key_id'];
        }

        if (isset($filters['start_date'])) {
            $query .= " AND al.created_at >= ?";
            $params[] = $filters['start_date'];
        }

        if (isset($filters['end_date'])) {
            $query .= " AND al.created_at <= ?";
            $params[] = $filters['end_date'];
        }

        if (isset($filters['response_code'])) {
            $query .= " AND al.response_code = ?";
            $params[] = $filters['response_code'];
        }

        $query .= " ORDER BY al.created_at DESC LIMIT ?";
        $params[] = $filters['limit'] ?? 100;

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getErrorLogs($filters = []) {
        $query = "SELECT el.*, ak.name as key_name, u.username
                 FROM api_error_log el
                 LEFT JOIN api_keys ak ON el.api_key_id = ak.id
                 LEFT JOIN users u ON ak.user_id = u.id
                 WHERE 1=1";
        $params = [];

        if (isset($filters['api_key_id'])) {
            $query .= " AND el.api_key_id = ?";
            $params[] = $filters['api_key_id'];
        }

        if (isset($filters['error_code'])) {
            $query .= " AND el.error_code = ?";
            $params[] = $filters['error_code'];
        }

        $query .= " ORDER BY el.created_at DESC LIMIT ?";
        $params[] = $filters['limit'] ?? 100;

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function logAction($action, $data) {
        $auditLog = new AuditLog();
        $auditLog->create([
            'user_id' => $this->currentUser->id,
            'action' => $action,
            'details' => json_encode($data)
        ]);
    }
}
?>
