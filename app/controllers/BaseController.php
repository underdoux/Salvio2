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

        Logger::log("Session status: " . session_status());
        Logger::log("Session ID: " . session_id());
        Logger::log("Session data: " . print_r($_SESSION, true));

        // Temporarily disable auth check for test_session.php to avoid redirect loop
        $currentScript = basename($_SERVER['SCRIPT_NAME']);
        if ($currentScript === 'test_session.php') {
            return;
        }

        // Check authentication if required
        if ($this->requiresAuth) {
            if (!$this->isAuthenticated()) {
                $requestUri = $_SERVER['REQUEST_URI'] ?? 'unknown';
                Logger::log("Unauthorized access attempt to {$requestUri}");
                Logger::log("User session data: " . print_r($_SESSION['user'] ?? 'no user data', true));
                $this->redirect('/Salvio2/public/auth');
            } else {
                Logger::log("Authenticated user accessing {$_SERVER['REQUEST_URI']}");
                Logger::log("User role: " . ($_SESSION['user']['role'] ?? 'no role'));
            }
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
        try {
            Logger::log("Rendering view: {$view}");
            Logger::log("View data: " . print_r($data, true));
            
            // Clean any existing output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            extract($data);
            
            if ($view === 'layouts/main') {
                Logger::log("Rendering main layout directly");
                require __DIR__ . "/../../app/views/layouts/main.php";
                return;
            }
            
            // Start buffering for view content
            ob_start();
            
            $viewPath = __DIR__ . "/../../app/views/{$view}.php";
            Logger::log("View path: {$viewPath}");
            
            if (!file_exists($viewPath)) {
                throw new Exception("View file not found: {$viewPath}");
            }
            
            Logger::log("Loading view content");
            require $viewPath;
            
            // Get view content and clean buffer
            $content = ob_get_clean();
            Logger::log("View content length: " . strlen($content));
            
            // Start new buffer for final output
            ob_start();
            
            $layoutPath = __DIR__ . "/../../app/views/layouts/main.php";
            Logger::log("Layout path: {$layoutPath}");
            
            if (!file_exists($layoutPath)) {
                throw new Exception("Layout file not found: {$layoutPath}");
            }
            
            Logger::log("Rendering with layout");
            require $layoutPath;
            
            // Flush final output
            ob_end_flush();
            
        } catch (Exception $e) {
            // Clean any remaining buffers
            while (ob_get_level()) {
                ob_end_clean();
            }
            Logger::log("Error rendering view: " . $e->getMessage());
            echo "Error rendering view: " . $e->getMessage();
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
