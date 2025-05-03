<?php

require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel {
    protected $table = 'users';

    public function findByUsername($username) {
        try {
            Logger::log("Looking up user: {$username}");
            $sql = "SELECT id, username, password, role, status FROM {$this->table} WHERE username = ? AND status = TRUE LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                Logger::log("User found with role: " . ($user['role'] ?? 'no role'));
            } else {
                Logger::log("No user found with username: {$username}");
            }
            
            return $user;
        } catch (Exception $e) {
            Logger::log("Database error in findByUsername: " . $e->getMessage());
            throw $e;
        }
    }

    public function authenticate($username, $password) {
        try {
            Logger::log("Attempting authentication for user: {$username}");
            $user = $this->findByUsername($username);
            
            if (!$user) {
                Logger::log("Authentication failed: user not found for username '{$username}'");
                return false;
            }

            if (password_verify($password, $user['password'])) {
                Logger::log("Password verified for user '{$username}' with role '{$user['role']}'");
                // Remove password from session data
                unset($user['password']);
                return $user;
            } else {
                Logger::log("Authentication failed: password mismatch for username '{$username}'");
            }

            return false;
        } catch (Exception $e) {
            Logger::log("Authentication error: " . $e->getMessage());
            throw $e;
        }
    }
}
