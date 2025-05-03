<?php

require_once __DIR__ . '/../helpers/Logger.php';

class BaseController {
    protected $db;
    protected $view;
    protected $requiresAuth = true;

    public function __construct() {
        global $db;
        $this->db = $db;

        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Temporarily disable auth check for test_session.php to avoid redirect loop
        $currentScript = basename($_SERVER['SCRIPT_NAME']);
        if ($currentScript === 'test_session.php') {
            return;
        }

        // Check authentication if required
        if ($this->requiresAuth && !$this->isAuthenticated()) {
            $requestUri = $_SERVER['REQUEST_URI'] ?? 'unknown';
            Logger::log("Unauthorized access attempt to {$requestUri}");
            $this->redirect('/Salvio2/public/auth');
        }
    }

    protected function isAuthenticated() {
        if (!isset($_SESSION['user']) || !isset($_SESSION['auth_time'])) {
            return false;
        }

        // Check if session has expired (1 hour)
        $sessionTimeout = 3600; // 1 hour in seconds
        if (time() - $_SESSION['auth_time'] > $sessionTimeout) {
            // Session expired, destroy it
            session_destroy();
            return false;
        }

        // Update last activity time
        $_SESSION['auth_time'] = time();
        return true;
    }

    protected function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }

    protected function render($view, $data = []) {
        extract($data);
        if ($view === 'layouts/main') {
            require_once "../app/views/layouts/main.php";
        } else {
            ob_start();
            require_once "../app/views/{$view}.php";
            $content = ob_get_clean();
            require_once "../app/views/layouts/main.php";
        }
    }

    protected function json($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    protected function redirect($url) {
        header("Location: {$url}");
        exit;
    }

    protected function getStatusBadgeClass($status) {
        return match($status) {
            'new' => 'primary',
            'in_progress' => 'warning',
            'completed' => 'success',
            'paid' => 'info',
            default => 'secondary'
        };
    }
}
