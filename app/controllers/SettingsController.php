<?php

class SettingsController extends BaseController {
    private $settings;
    private $rateLimiter;
    private $twoFactorAuth;

    public function __construct() {
        parent::__construct();
        $this->settings = new Settings();
        $this->rateLimiter = new RateLimiter();
        $this->twoFactorAuth = new TwoFactorAuth();
    }

    public function update($key) {
        $this->checkPermission('settings.edit');
        
        try {
            // Check rate limit
            $limit = $this->rateLimiter->checkLimit($key, $_SESSION['user_id']);
            if (!$limit['allowed']) {
                $resetTime = $this->rateLimiter->getResetTime($this->getSettingType($key), $_SESSION['user_id']);
                $minutes = ceil($resetTime / 60);
                
                return $this->jsonResponse([
                    'success' => false,
                    'message' => "Rate limit exceeded. Please try again in {$minutes} minutes.",
                    'rate_limit' => [
                        'remaining' => $limit['remaining'],
                        'reset' => $limit['reset']
                    ]
                ], 429);
            }

            // Validate CSRF token
            if (!$this->validateCsrfToken()) {
                throw new Exception('Invalid security token');
            }

            $data = $this->getRequestData();
            if (!isset($data['value'])) {
                throw new Exception('No value provided');
            }

            // Check if setting requires 2FA
            if ($this->twoFactorAuth->requiresTwoFactor($key)) {
                // If no verification code provided, generate and send one
                if (!isset($data['verification_code'])) {
                    $result = $this->twoFactorAuth->generateCode($_SESSION['user_id'], $key);
                    return $this->jsonResponse([
                        'requires_2fa' => true,
                        'message' => 'Please check your email for verification code',
                        'email_hint' => $result['email_hint'],
                        'expires_in' => $result['expires_in']
                    ]);
                }

                // Verify the provided code
                try {
                    $this->twoFactorAuth->verifyCode(
                        $_SESSION['user_id'],
                        $key,
                        $data['verification_code']
                    );
                } catch (Exception $e) {
                    $remaining = $this->twoFactorAuth->getRemainingAttempts($_SESSION['user_id'], $key);
                    return $this->jsonResponse([
                        'success' => false,
                        'requires_2fa' => true,
                        'message' => $e->getMessage(),
                        'remaining_attempts' => $remaining
                    ], 400);
                }
            }

            // Get current setting
            $current = $this->settings->get($key);
            if (!$current) {
                throw new Exception('Setting not found');
            }

            // Update the setting
            $success = $this->settings->set(
                $key,
                $data['value'],
                $data['type'] ?? $current['type'],
                $data['description'] ?? $current['description']
            );

            if ($success) {
                // Clear 2FA verification if it was used
                if ($this->twoFactorAuth->requiresTwoFactor($key)) {
                    $this->twoFactorAuth->clearVerification($_SESSION['user_id'], $key);
                }

                // Log the change
                Logger::log("Setting '{$key}' updated by user {$_SESSION['user_id']}", 'INFO', [
                    'old_value' => $current['value'],
                    'new_value' => $data['value'],
                    'remaining_attempts' => $limit['remaining'],
                    'required_2fa' => $this->twoFactorAuth->requiresTwoFactor($key)
                ]);

                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Setting updated successfully',
                    'rate_limit' => [
                        'remaining' => $limit['remaining'],
                        'reset' => $limit['reset']
                    ]
                ]);
            }

            throw new Exception('Failed to update setting');

        } catch (Exception $e) {
            Logger::log("Error updating setting '{$key}': " . $e->getMessage(), 'ERROR');
            
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function resendVerificationCode($key) {
        $this->checkPermission('settings.edit');
        
        try {
            if (!$this->twoFactorAuth->requiresTwoFactor($key)) {
                throw new Exception('Two-factor authentication not required for this setting');
            }

            // Clear any existing verification
            $this->twoFactorAuth->clearVerification($_SESSION['user_id'], $key);

            // Generate and send new code
            $result = $this->twoFactorAuth->generateCode($_SESSION['user_id'], $key);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Verification code sent',
                'email_hint' => $result['email_hint'],
                'expires_in' => $result['expires_in']
            ]);

        } catch (Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function index() {
        $this->checkPermission('settings.view');
        
        // Get rate limit status for display
        $limitStatus = $this->rateLimiter->getLimitStatus($_SESSION['user_id']);
        
        $settings = $this->settings->getAll();
        $groupedSettings = $this->groupSettings($settings);
        
        // Add 2FA requirement info to settings
        foreach ($groupedSettings as $type => &$typeSettings) {
            foreach ($typeSettings as &$setting) {
                $setting['requires_2fa'] = $this->twoFactorAuth->requiresTwoFactor($setting['key']);
            }
        }
        
        return $this->render('settings/index', [
            'settings' => $groupedSettings,
            'limitStatus' => $limitStatus
        ]);
    }

    public function history($key) {
        $this->checkPermission('settings.view');
        
        try {
            $history = $this->settings->getHistory($key);
            return $this->jsonResponse(['success' => true, 'history' => $history]);
        } catch (Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to fetch setting history'
            ], 400);
        }
    }

    public function resetLimits() {
        $this->checkPermission('settings.manage');
        
        try {
            $userId = $this->getRequestData()['user_id'] ?? null;
            if (!$userId) {
                throw new Exception('No user ID provided');
            }

            $this->rateLimiter->resetLimits($userId);
            Logger::log("Rate limits reset for user {$userId} by admin {$_SESSION['user_id']}", 'INFO');

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Rate limits reset successfully'
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    private function groupSettings($settings) {
        $grouped = [];
        foreach ($settings as $setting) {
            $type = $this->getSettingType($setting['key']);
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $setting;
        }
        return $grouped;
    }

    private function getSettingType($key) {
        $parts = explode('.', $key);
        return $parts[0];
    }

    private function validateCsrfToken() {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        return $token && $token === $_SESSION['csrf_token'];
    }

    private function getRequestData() {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?: $_POST;
    }

    private function checkPermission($permission) {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Unauthorized', 401);
        }
        
        // Add your permission checking logic here
        if ($permission === 'settings.manage' && !$_SESSION['is_admin']) {
            throw new Exception('Forbidden', 403);
        }
    }
}
