<?php

class AuditLogger {
    private $db;
    private $currentUser;
    private static $instance = null;

    private function __construct() {
        $this->db = Database::getInstance();
        $this->currentUser = $_SESSION['user_id'] ?? null;
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Log a setting change with comprehensive details
     */
    public function logSettingChange($key, $oldValue, $newValue, $context = []) {
        $data = [
            'event_type' => 'setting_change',
            'setting_key' => $key,
            'old_value' => $this->sanitizeValue($oldValue),
            'new_value' => $this->sanitizeValue($newValue),
            'user_id' => $this->currentUser,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'timestamp' => date('Y-m-d H:i:s'),
            'session_id' => session_id(),
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'request_path' => $_SERVER['REQUEST_URI'],
            'setting_type' => $this->getSettingType($key),
            'requires_2fa' => $context['requires_2fa'] ?? false,
            'validation_rules' => json_encode($context['validation_rules'] ?? []),
            'rate_limit_remaining' => $context['rate_limit_remaining'] ?? null,
            'change_reason' => $context['reason'] ?? null,
            'related_changes' => json_encode($context['related_changes'] ?? [])
        ];

        $this->insertAuditLog($data);
        $this->notifyAdminsIfSensitive($key, $data);
    }

    /**
     * Log a security event with detailed context
     */
    public function logSecurityEvent($eventType, $details, $context = []) {
        $data = [
            'event_type' => $eventType,
            'details' => json_encode($details),
            'user_id' => $this->currentUser,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'timestamp' => date('Y-m-d H:i:s'),
            'session_id' => session_id(),
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'request_path' => $_SERVER['REQUEST_URI'],
            'severity' => $context['severity'] ?? 'info',
            'result' => $context['result'] ?? 'success',
            'related_user' => $context['related_user'] ?? null,
            'affected_resource' => $context['affected_resource'] ?? null,
            'authentication_method' => $context['auth_method'] ?? 'session'
        ];

        $this->insertAuditLog($data);
        $this->alertOnSuspiciousActivity($data);
    }

    /**
     * Log a validation event
     */
    public function logValidation($key, $value, $result, $context = []) {
        $data = [
            'event_type' => 'validation',
            'setting_key' => $key,
            'attempted_value' => $this->sanitizeValue($value),
            'validation_result' => $result,
            'user_id' => $this->currentUser,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'timestamp' => date('Y-m-d H:i:s'),
            'validation_rules' => json_encode($context['rules'] ?? []),
            'error_details' => $context['error'] ?? null,
            'validator_version' => $context['version'] ?? '1.0'
        ];

        $this->insertAuditLog($data);
    }

    /**
     * Log rate limiting events
     */
    public function logRateLimit($key, $attempts, $context = []) {
        $data = [
            'event_type' => 'rate_limit',
            'setting_key' => $key,
            'attempts' => $attempts,
            'user_id' => $this->currentUser,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'timestamp' => date('Y-m-d H:i:s'),
            'window_start' => $context['window_start'] ?? null,
            'window_size' => $context['window_size'] ?? null,
            'limit_type' => $context['limit_type'] ?? 'default',
            'remaining_attempts' => $context['remaining'] ?? 0
        ];

        $this->insertAuditLog($data);
    }

    /**
     * Log two-factor authentication events
     */
    public function log2FAEvent($key, $action, $context = []) {
        $data = [
            'event_type' => '2fa',
            'setting_key' => $key,
            'action' => $action,
            'user_id' => $this->currentUser,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'timestamp' => date('Y-m-d H:i:s'),
            'verification_method' => $context['method'] ?? 'email',
            'attempt_number' => $context['attempt'] ?? 1,
            'success' => $context['success'] ?? false,
            'error_type' => $context['error'] ?? null,
            'expiry_time' => $context['expiry'] ?? null
        ];

        $this->insertAuditLog($data);
    }

    /**
     * Get comprehensive audit trail for a setting
     */
    public function getAuditTrail($key, $filters = []) {
        $query = "SELECT * FROM audit_logs WHERE setting_key = ? ";
        $params = [$key];

        if (!empty($filters['start_date'])) {
            $query .= "AND timestamp >= ? ";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $query .= "AND timestamp <= ? ";
            $params[] = $filters['end_date'];
        }

        if (!empty($filters['user_id'])) {
            $query .= "AND user_id = ? ";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['event_type'])) {
            $query .= "AND event_type = ? ";
            $params[] = $filters['event_type'];
        }

        $query .= "ORDER BY timestamp DESC";

        return $this->db->query($query, $params);
    }

    /**
     * Get security event summary
     */
    public function getSecuritySummary($timeframe = '24h') {
        $query = "SELECT 
                    event_type,
                    COUNT(*) as count,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT ip_address) as unique_ips,
                    MIN(timestamp) as first_occurrence,
                    MAX(timestamp) as last_occurrence
                 FROM audit_logs 
                 WHERE timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                 GROUP BY event_type";

        $hours = str_replace(['h', 'd', 'w'], ['', '*24', '*168'], $timeframe);
        return $this->db->query($query, [$hours]);
    }

    private function insertAuditLog($data) {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));
        
        $query = "INSERT INTO audit_logs ({$columns}) VALUES ({$values})";
        $this->db->query($query, array_values($data));
    }

    private function sanitizeValue($value) {
        if (is_array($value)) {
            return json_encode($value);
        }
        
        // Mask sensitive data
        if ($this->isSensitiveData($value)) {
            return '[REDACTED]';
        }
        
        return (string)$value;
    }

    private function isSensitiveData($value) {
        // Add patterns for sensitive data (passwords, keys, etc.)
        $patterns = [
            '/password/i',
            '/secret/i',
            '/key/i',
            '/token/i',
            '/credential/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    private function getSettingType($key) {
        $parts = explode('.', $key);
        return $parts[0];
    }

    private function notifyAdminsIfSensitive($key, $data) {
        if ($this->isSensitiveSetting($key)) {
            // Implement admin notification logic
            // (Email, SMS, Slack, etc.)
        }
    }

    private function alertOnSuspiciousActivity($data) {
        // Implement suspicious activity detection and alerting
        // (Multiple failed attempts, unusual patterns, etc.)
    }

    private function isSensitiveSetting($key) {
        $sensitiveTypes = [
            'smtp',
            'security',
            'payment',
            'api',
            'tax'
        ];

        return in_array($this->getSettingType($key), $sensitiveTypes);
    }
}
