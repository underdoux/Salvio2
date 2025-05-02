<?php
class AuthService {
    private static $initialized = false;

    public static function initSession() {
        if (!self::$initialized) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            self::$initialized = true;
        }
    }

    public static function login($userId, $username, $roleId) {
        self::initSession();
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['role_id'] = $roleId;
    }

    public static function logout() {
        self::initSession();
        session_destroy();
        self::$initialized = false;
    }

    public static function checkAccess(array $allowedRoles) {
        self::initSession();
        if (!isset($_SESSION['role_id'])) {
            header("Location: /login");
            exit();
        }

        $roleId = $_SESSION['role_id'];
        $roleMap = [
            1 => 'Admin',
            2 => 'Cashier',
            3 => 'Sales'
        ];

        $userRole = $roleMap[$roleId] ?? null;
        if (!$userRole || !in_array($userRole, $allowedRoles)) {
            http_response_code(403);
            echo "403 Forbidden - You do not have permission to access this page.";
            exit();
        }
    }

    public static function isLoggedIn() {
        self::initSession();
        return isset($_SESSION['user_id']);
    }
}
?>
