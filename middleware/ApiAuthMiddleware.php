<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/SettingsService.php';

class ApiAuthMiddleware {
    private $db;
    private $settings;
    private $currentApiKey;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->settings = new SettingsService();
    }

    public function handle() {
        // Check if API is enabled
        if (!$this->settings->get('api', 'enable_api')) {
            $this->respondWithError('API access is disabled', 403);
        }

        // Get API key from header
        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            $this->respondWithError('API key is required', 401);
        }

        // Validate API key
        $keyData = $this->validateApiKey($apiKey);
        if (!$keyData) {
            $this->respondWithError('Invalid API key', 401);
        }

        // Check if key is expired
        if ($this->isKeyExpired($keyData)) {
            $this->respondWithError('API key has expired', 401);
        }

        // Check rate limit
        if ($this->isRateLimitExceeded($keyData)) {
            $this->respondWithError('Rate limit exceeded', 429);
        }

        // Store current API key for later use
        $this->currentApiKey = $keyData;

        // Update usage metrics
        $this->updateUsageMetrics($keyData);

        return true;
    }

    private function getApiKey() {
        $headers = getallheaders();
        return $headers['X-API-Key'] ?? null;
    }

    private function validateApiKey($key) {
        $query = "SELECT ak.*, u.id as user_id, u.role_id 
                 FROM api_keys ak
                 JOIN users u ON ak.user_id = u.id
                 WHERE ak.key_hash = ? AND ak.is_active = 1";

        $stmt = $this->db->prepare($query);
        $keyHash = hash('sha256', $key);
        $stmt->execute([$keyHash]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function isKeyExpired($keyData) {
        return strtotime($keyData['expires_at']) < time();
    }

    private function isRateLimitExceeded($keyData) {
        $rateLimit = $this->settings->get('api', 'rate_limit_per_minute');
        $window = 60; // 1 minute window

        $query = "SELECT COUNT(*) as request_count
                 FROM api_requests
                 WHERE api_key_id = ?
                 AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$keyData['id'], $window]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['request_count'] >= $rateLimit;
    }

    private function updateUsageMetrics($keyData) {
        // Log API request
        $query = "INSERT INTO api_requests 
                (api_key_id, endpoint, method, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $keyData['id'],
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);

        // Update last used timestamp
        $query = "UPDATE api_keys 
                 SET last_used_at = NOW(), 
                     total_requests = total_requests + 1
                 WHERE id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$keyData['id']]);
    }

    private function respondWithError($message, $code) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => $message,
            'code' => $code
        ]);
        exit;
    }

    public function getCurrentApiKey() {
        return $this->currentApiKey;
    }

    public function hasPermission($permission) {
        if (!$this->currentApiKey) {
            return false;
        }

        $query = "SELECT COUNT(*) as has_permission
                 FROM role_permissions rp
                 JOIN permissions p ON rp.permission_id = p.id
                 WHERE rp.role_id = ? AND p.name = ?";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $this->currentApiKey['role_id'],
            $permission
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['has_permission'] > 0;
    }

    public function logAccess($endpoint, $responseCode, $responseTime) {
        if (!$this->currentApiKey) {
            return;
        }

        $query = "INSERT INTO api_access_log 
                (api_key_id, endpoint, method, response_code, response_time, ip_address)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $this->currentApiKey['id'],
            $endpoint,
            $_SERVER['REQUEST_METHOD'],
            $responseCode,
            $responseTime,
            $_SERVER['REMOTE_ADDR']
        ]);
    }

    public function validateScope($requiredScope) {
        if (!$this->currentApiKey) {
            return false;
        }

        $scopes = json_decode($this->currentApiKey['scopes'], true);
        return in_array($requiredScope, $scopes);
    }

    public function getRequestsRemaining() {
        if (!$this->currentApiKey) {
            return 0;
        }

        $rateLimit = $this->settings->get('api', 'rate_limit_per_minute');
        $window = 60;

        $query = "SELECT COUNT(*) as request_count
                 FROM api_requests
                 WHERE api_key_id = ?
                 AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$this->currentApiKey['id'], $window]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return max(0, $rateLimit - $result['request_count']);
    }
}
?>
