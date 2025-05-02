<?php
class Controller {
    protected $conn;
    protected $auditLog;
    protected $config;

    public function __construct() {
        // Load bootstrap and get database connection
        $app = require_once __DIR__ . '/../config/bootstrap.php';
        $this->conn = $app['conn'];
        $this->config = $app['config'];

        // Initialize audit log
        require_once __DIR__ . '/../models/AuditLog.php';
        $this->auditLog = new AuditLog();
    }

    protected function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            redirect('/login');
        }
    }

    protected function requireRole($roles) {
        $this->requireLogin();

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        if (!in_array($_SESSION['role_name'], $roles)) {
            $this->auditLog->log(
                $_SESSION['user_id'],
                'unauthorized_access',
                [
                    'required_roles' => $roles,
                    'user_role' => $_SESSION['role_name'],
                    'url' => $_SERVER['REQUEST_URI']
                ]
            );

            http_response_code(403);
            if ($this->isAjaxRequest()) {
                echo json_encode(['error' => 'Unauthorized access']);
                exit;
            } else {
                require_once __DIR__ . '/../views/errors/403.php';
                exit;
            }
        }
    }

    protected function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function view($path, $data = []) {
        // Add common data
        $data['config'] = $this->config;
        if (isset($_SESSION['user_id'])) {
            $data['user'] = [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role_name']
            ];
        }

        // Extract data to make variables available in view
        extract($data);

        // Start output buffering
        ob_start();

        // Include the view file
        require __DIR__ . '/../views/' . $path . '.php';

        // Get the content and clean the buffer
        $content = ob_get_clean();

        // Include the layout if not an AJAX request
        if (!$this->isAjaxRequest()) {
            require __DIR__ . '/../views/layouts/base.php';
        } else {
            echo $content;
        }
    }

    protected function redirect($path, $message = null, $type = 'success') {
        if ($message) {
            $_SESSION['flash'] = [
                'message' => $message,
                'type' => $type
            ];
        }
        redirect($path);
    }

    protected function validateCSRF() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $this->auditLog->log(
                    $_SESSION['user_id'] ?? 0,
                    'csrf_validation_failed',
                    [
                        'url' => $_SERVER['REQUEST_URI'],
                        'ip' => $_SERVER['REMOTE_ADDR']
                    ]
                );

                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['error' => 'Invalid CSRF token'], 403);
                } else {
                    $this->redirect('/login', 'Session expired. Please login again.', 'error');
                }
            }
        }
    }

    protected function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    protected function log($action, $details = []) {
        if (isset($_SESSION['user_id'])) {
            $this->auditLog->log($_SESSION['user_id'], $action, $details);
        }
    }

    protected function getPostData() {
        if ($this->isAjaxRequest() && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                return json_decode($input, true) ?? [];
            }
        }
        return $_POST;
    }

    protected function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    protected function validateRequired($data, $fields) {
        $errors = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        return $errors;
    }
}
