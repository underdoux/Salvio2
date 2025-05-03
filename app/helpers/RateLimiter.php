<?php

class RateLimiter {
    private $cache;
    private $defaultAttempts = 10;  // Default attempts per window
    private $defaultWindow = 300;    // Default 5-minute window
    
    // Define limits for different setting types
    private $settingLimits = [
        'currency' => ['attempts' => 5, 'window' => 3600],     // 5 attempts per hour
        'smtp' => ['attempts' => 5, 'window' => 3600],         // 5 attempts per hour
        'security' => ['attempts' => 3, 'window' => 3600],     // 3 attempts per hour
        'tax' => ['attempts' => 5, 'window' => 3600],          // 5 attempts per hour
        'commission' => ['attempts' => 10, 'window' => 3600],  // 10 attempts per hour
        'default' => ['attempts' => 20, 'window' => 300]       // 20 attempts per 5 minutes
    ];

    public function __construct() {
        $this->cache = Cache::getInstance();
    }

    /**
     * Check if the action is allowed based on rate limits
     *
     * @param string $key Setting key being modified
     * @param string $userId User making the change
     * @return array ['allowed' => bool, 'remaining' => int, 'reset' => int]
     */
    public function checkLimit($key, $userId) {
        $type = $this->getSettingType($key);
        $limits = $this->getLimits($type);
        
        $cacheKey = "ratelimit:{$type}:{$userId}";
        $attempts = $this->getAttempts($cacheKey);
        
        if (!$attempts) {
            // First attempt
            $attempts = [
                'count' => 0,
                'window_start' => time()
            ];
        }

        // Check if window has expired
        if (time() - $attempts['window_start'] > $limits['window']) {
            // Reset window
            $attempts = [
                'count' => 0,
                'window_start' => time()
            ];
        }

        $remaining = $limits['attempts'] - $attempts['count'];
        $allowed = $remaining > 0;
        $reset = $attempts['window_start'] + $limits['window'];

        if ($allowed) {
            // Increment attempt count
            $attempts['count']++;
            $this->cache->set($cacheKey, $attempts, $limits['window']);
            $remaining--;
        }

        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'reset' => $reset
        ];
    }

    /**
     * Get rate limits for a setting type
     */
    private function getLimits($type) {
        return $this->settingLimits[$type] ?? $this->settingLimits['default'];
    }

    /**
     * Get current attempts from cache
     */
    private function getAttempts($key) {
        return $this->cache->get($key);
    }

    /**
     * Extract setting type from key
     */
    private function getSettingType($key) {
        $parts = explode('.', $key);
        return $parts[0];
    }

    /**
     * Reset limits for a user
     */
    public function resetLimits($userId) {
        foreach (array_keys($this->settingLimits) as $type) {
            $this->cache->delete("ratelimit:{$type}:{$userId}");
        }
    }

    /**
     * Get current limits status for a user
     */
    public function getLimitStatus($userId) {
        $status = [];
        foreach (array_keys($this->settingLimits) as $type) {
            $cacheKey = "ratelimit:{$type}:{$userId}";
            $attempts = $this->getAttempts($cacheKey);
            if ($attempts) {
                $limits = $this->getLimits($type);
                $remaining = $limits['attempts'] - $attempts['count'];
                $reset = $attempts['window_start'] + $limits['window'] - time();
                $status[$type] = [
                    'remaining' => $remaining,
                    'reset_in' => $reset,
                    'total' => $limits['attempts']
                ];
            }
        }
        return $status;
    }

    /**
     * Check if a specific setting type is currently rate limited
     */
    public function isLimited($type, $userId) {
        $cacheKey = "ratelimit:{$type}:{$userId}";
        $attempts = $this->getAttempts($cacheKey);
        if (!$attempts) {
            return false;
        }

        $limits = $this->getLimits($type);
        if (time() - $attempts['window_start'] > $limits['window']) {
            return false;
        }

        return $attempts['count'] >= $limits['attempts'];
    }

    /**
     * Get time until rate limit reset
     */
    public function getResetTime($type, $userId) {
        $cacheKey = "ratelimit:{$type}:{$userId}";
        $attempts = $this->getAttempts($cacheKey);
        if (!$attempts) {
            return 0;
        }

        $limits = $this->getLimits($type);
        $reset = $attempts['window_start'] + $limits['window'] - time();
        return max(0, $reset);
    }
}
