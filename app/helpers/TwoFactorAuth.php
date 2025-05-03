<?php

class TwoFactorAuth {
    private $cache;
    private $codeLength = 6;
    private $codeExpiry = 300; // 5 minutes
    
    // Define sensitive settings that require 2FA
    private $sensitiveSettings = [
        'smtp' => ['password', 'api_key'],
        'security' => ['encryption_key', 'api_secret'],
        'payment' => ['gateway_key', 'merchant_id'],
        'whatsapp' => ['api_token', 'webhook_secret'],
        'commission' => ['global_rate', 'payout_schedule'],
        'tax' => ['rate', 'calculation_method']
    ];

    public function __construct() {
        $this->cache = Cache::getInstance();
    }

    /**
     * Check if a setting requires 2FA
     */
    public function requiresTwoFactor($key) {
        $type = $this->getSettingType($key);
        $name = $this->getSettingName($key);
        
        return isset($this->sensitiveSettings[$type]) && 
               in_array($name, $this->sensitiveSettings[$type]);
    }

    /**
     * Generate and send 2FA code
     */
    public function generateCode($userId, $key) {
        $code = $this->generateRandomCode();
        $cacheKey = "2fa:{$userId}:{$key}";
        
        // Store code with expiry
        $this->cache->set($cacheKey, [
            'code' => $code,
            'attempts' => 0,
            'generated_at' => time()
        ], $this->codeExpiry);

        // Get user email from session or database
        $user = $this->getUserDetails($userId);
        
        // Send code via email
        $this->sendCodeByEmail($user['email'], $code, $key);
        
        return [
            'sent' => true,
            'expires_in' => $this->codeExpiry,
            'email_hint' => $this->maskEmail($user['email'])
        ];
    }

    /**
     * Verify 2FA code
     */
    public function verifyCode($userId, $key, $code) {
        $cacheKey = "2fa:{$userId}:{$key}";
        $stored = $this->cache->get($cacheKey);
        
        if (!$stored) {
            throw new Exception('Code expired or not found');
        }

        // Check attempts
        if ($stored['attempts'] >= 3) {
            $this->cache->delete($cacheKey);
            throw new Exception('Too many invalid attempts');
        }

        // Update attempts
        $stored['attempts']++;
        $this->cache->set($cacheKey, $stored, $this->codeExpiry);

        // Verify code
        if ($stored['code'] !== $code) {
            throw new Exception('Invalid code');
        }

        // Check expiry
        if (time() - $stored['generated_at'] > $this->codeExpiry) {
            throw new Exception('Code expired');
        }

        // Clear code after successful verification
        $this->cache->delete($cacheKey);
        
        return true;
    }

    /**
     * Generate random numeric code
     */
    private function generateRandomCode() {
        $min = pow(10, $this->codeLength - 1);
        $max = pow(10, $this->codeLength) - 1;
        return str_pad(random_int($min, $max), $this->codeLength, '0', STR_PAD_LEFT);
    }

    /**
     * Send code via email
     */
    private function sendCodeByEmail($email, $code, $key) {
        $subject = 'Two-Factor Authentication Required';
        $message = "Your verification code for changing setting '{$key}' is: {$code}\n\n";
        $message .= "This code will expire in " . ($this->codeExpiry / 60) . " minutes.\n";
        $message .= "If you did not request this code, please contact support immediately.";

        // Use your email sending implementation
        Mailer::send($email, $subject, $message);
        
        // Log the action
        Logger::log("2FA code sent to {$email} for setting {$key}", 'INFO');
    }

    /**
     * Get setting type from key
     */
    private function getSettingType($key) {
        $parts = explode('.', $key);
        return $parts[0];
    }

    /**
     * Get setting name from key
     */
    private function getSettingName($key) {
        $parts = explode('.', $key);
        return $parts[1] ?? '';
    }

    /**
     * Get user details
     */
    private function getUserDetails($userId) {
        // Implement your user fetching logic
        // For now, using session data
        return [
            'email' => $_SESSION['user_email'] ?? '',
            'name' => $_SESSION['user_name'] ?? ''
        ];
    }

    /**
     * Mask email address for display
     */
    private function maskEmail($email) {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return '***@***.***';
        
        $name = $parts[0];
        $domain = $parts[1];
        
        $maskedName = substr($name, 0, 2) . str_repeat('*', strlen($name) - 2);
        return $maskedName . '@' . $domain;
    }

    /**
     * Check if user has pending 2FA verification
     */
    public function hasPendingVerification($userId, $key) {
        return $this->cache->get("2fa:{$userId}:{$key}") !== null;
    }

    /**
     * Get remaining verification attempts
     */
    public function getRemainingAttempts($userId, $key) {
        $stored = $this->cache->get("2fa:{$userId}:{$key}");
        if (!$stored) return 0;
        
        return max(0, 3 - $stored['attempts']);
    }

    /**
     * Get code expiry time
     */
    public function getCodeExpiry($userId, $key) {
        $stored = $this->cache->get("2fa:{$userId}:{$key}");
        if (!$stored) return 0;
        
        return max(0, ($stored['generated_at'] + $this->codeExpiry) - time());
    }

    /**
     * Clear pending verification
     */
    public function clearVerification($userId, $key) {
        $this->cache->delete("2fa:{$userId}:{$key}");
    }
}
