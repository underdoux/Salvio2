<?php

class TwoFactorRecovery {
    private static $instance = null;
    private $db;
    private $auditLogger;
    private $twoFactorAuth;

    private function __construct() {
        $this->db = Database::getInstance();
        $this->auditLogger = AuditLogger::getInstance();
        $this->twoFactorAuth = TwoFactorAuth::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Generate backup codes for a user
     */
    public function generateBackupCodes($userId, $count = 10) {
        try {
            $this->db->beginTransaction();

            // Delete existing unused backup codes
            $sql = "DELETE FROM two_factor_backup_codes 
                    WHERE user_id = ? AND used = 0";
            $this->db->query($sql, [$userId]);

            $codes = [];
            for ($i = 0; $i < $count; $i++) {
                $code = $this->generateRandomCode();
                $sql = "INSERT INTO two_factor_backup_codes 
                        (user_id, code) VALUES (?, ?)";
                $this->db->query($sql, [$userId, $code]);
                $codes[] = $code;
            }

            $this->db->commit();
            return $codes;
        } catch (Exception $e) {
            $this->db->rollback();
            $this->auditLogger->log('backup_codes_generation_failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Verify backup code
     */
    public function verifyBackupCode($userId, $code) {
        try {
            $sql = "SELECT id FROM two_factor_backup_codes 
                    WHERE user_id = ? AND code = ? AND used = 0";
            $result = $this->db->query($sql, [$userId, $code]);

            if (!empty($result)) {
                $this->markBackupCodeUsed($result[0]['id']);
                $this->logRecovery($userId, 'backup_codes', 'success');
                return true;
            }

            $this->logRecovery($userId, 'backup_codes', 'failed');
            return false;
        } catch (Exception $e) {
            $this->auditLogger->log('backup_code_verification_failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Setup SMS verification
     */
    public function setupSmsVerification($userId, $phoneNumber) {
        try {
            $code = $this->generateVerificationCode();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));

            $sql = "INSERT INTO two_factor_sms_verifications 
                    (user_id, phone_number, verification_code, expires_at)
                    VALUES (?, ?, ?, ?)";
            
            $this->db->query($sql, [
                $userId,
                $phoneNumber,
                $code,
                $expiresAt
            ]);

            // Send SMS via your SMS provider
            $this->sendSmsCode($phoneNumber, $code);

            return true;
        } catch (Exception $e) {
            $this->auditLogger->log('sms_verification_setup_failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Verify SMS code
     */
    public function verifySmsCode($userId, $code) {
        try {
            $sql = "SELECT * FROM two_factor_sms_verifications 
                    WHERE user_id = ? 
                    AND verification_code = ?
                    AND verified = 0
                    AND attempts < 3
                    AND expires_at > NOW()
                    ORDER BY created_at DESC
                    LIMIT 1";
            
            $result = $this->db->query($sql, [$userId, $code]);

            if (empty($result)) {
                $this->incrementSmsAttempts($userId);
                $this->logRecovery($userId, 'sms', 'failed');
                return false;
            }

            $this->markSmsVerified($result[0]['id']);
            $this->logRecovery($userId, 'sms', 'success');
            return true;
        } catch (Exception $e) {
            $this->auditLogger->log('sms_verification_failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Add trusted device
     */
    public function addTrustedDevice($userId, $deviceIdentifier, $deviceName = null) {
        try {
            $trustedUntil = date('Y-m-d H:i:s', strtotime('+30 days'));

            $sql = "INSERT INTO two_factor_trusted_devices 
                    (user_id, device_identifier, device_name, trusted_until)
                    VALUES (?, ?, ?, ?)";
            
            $this->db->query($sql, [
                $userId,
                $deviceIdentifier,
                $deviceName,
                $trustedUntil
            ]);

            $this->logRecovery($userId, 'trusted_device', 'success');
            return true;
        } catch (Exception $e) {
            $this->auditLogger->log('trusted_device_add_failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Verify trusted device
     */
    public function verifyTrustedDevice($userId, $deviceIdentifier) {
        try {
            $sql = "SELECT * FROM two_factor_trusted_devices 
                    WHERE user_id = ? 
                    AND device_identifier = ?
                    AND trusted_until > NOW()";
            
            $result = $this->db->query($sql, [$userId, $deviceIdentifier]);

            if (!empty($result)) {
                $this->updateDeviceLastUsed($result[0]['id']);
                return true;
            }

            return false;
        } catch (Exception $e) {
            $this->auditLogger->log('trusted_device_verification_failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Remove trusted device
     */
    public function removeTrustedDevice($userId, $deviceId) {
        try {
            $sql = "DELETE FROM two_factor_trusted_devices 
                    WHERE id = ? AND user_id = ?";
            
            $this->db->query($sql, [$deviceId, $userId]);
            return true;
        } catch (Exception $e) {
            $this->auditLogger->log('trusted_device_removal_failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get recovery methods
     */
    public function getRecoveryMethods($userId) {
        $sql = "SELECT * FROM two_factor_recovery_methods 
                WHERE user_id = ?";
        return $this->db->query($sql, [$userId]);
    }

    /**
     * Enable recovery method
     */
    public function enableRecoveryMethod($userId, $methodType) {
        try {
            $sql = "INSERT INTO two_factor_recovery_methods 
                    (user_id, method_type) VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE is_enabled = 1";
            
            $this->db->query($sql, [$userId, $methodType]);
            return true;
        } catch (Exception $e) {
            $this->auditLogger->log('recovery_method_enable_failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Disable recovery method
     */
    public function disableRecoveryMethod($userId, $methodType) {
        try {
            $sql = "UPDATE two_factor_recovery_methods 
                    SET is_enabled = 0 
                    WHERE user_id = ? AND method_type = ?";
            
            $this->db->query($sql, [$userId, $methodType]);
            return true;
        } catch (Exception $e) {
            $this->auditLogger->log('recovery_method_disable_failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generate random backup code
     */
    private function generateRandomCode($length = 16) {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Generate verification code
     */
    private function generateVerificationCode($length = 6) {
        return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Mark backup code as used
     */
    private function markBackupCodeUsed($codeId) {
        $sql = "UPDATE two_factor_backup_codes 
                SET used = 1, used_at = NOW() 
                WHERE id = ?";
        $this->db->query($sql, [$codeId]);
    }

    /**
     * Increment SMS verification attempts
     */
    private function incrementSmsAttempts($userId) {
        $sql = "UPDATE two_factor_sms_verifications 
                SET attempts = attempts + 1 
                WHERE user_id = ? 
                AND verified = 0 
                ORDER BY created_at DESC 
                LIMIT 1";
        $this->db->query($sql, [$userId]);
    }

    /**
     * Mark SMS as verified
     */
    private function markSmsVerified($verificationId) {
        $sql = "UPDATE two_factor_sms_verifications 
                SET verified = 1 
                WHERE id = ?";
        $this->db->query($sql, [$verificationId]);
    }

    /**
     * Update device last used timestamp
     */
    private function updateDeviceLastUsed($deviceId) {
        $sql = "UPDATE two_factor_trusted_devices 
                SET last_used_at = NOW() 
                WHERE id = ?";
        $this->db->query($sql, [$deviceId]);
    }

    /**
     * Send SMS code
     */
    private function sendSmsCode($phoneNumber, $code) {
        // Implementation would depend on your SMS provider
        // This is a placeholder for the actual SMS sending logic
    }

    /**
     * Log recovery attempt
     */
    private function logRecovery($userId, $methodType, $status) {
        $sql = "INSERT INTO two_factor_recovery_logs 
                (user_id, method_type, status, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $userId,
            $methodType,
            $status,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);

        if ($status === 'success') {
            $sql = "UPDATE two_factor_recovery_methods 
                    SET last_used_at = NOW() 
                    WHERE user_id = ? AND method_type = ?";
            $this->db->query($sql, [$userId, $methodType]);
        }
    }
}
