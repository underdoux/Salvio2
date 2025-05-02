<?php

class Controller {
    protected $db;
    protected $user;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->user = user();
    }

    protected function view($view, $data = []) {
        // Extract data to make variables available in view
        extract($data);

        // Start output buffering
        ob_start();

        // Include the view file
        $view_file = __DIR__ . "/../views/{$view}.php";
        if (!file_exists($view_file)) {
            throw new Exception("View file not found: {$view}");
        }
        require $view_file;

        // Get the buffered content
        $content = ob_get_clean();

        // Return the content
        return $content;
    }

    protected function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        return json_encode($data);
    }

    protected function redirect($url, $with = []) {
        if (!empty($with)) {
            foreach ($with as $key => $value) {
                $_SESSION[$key] = $value;
            }
        }
        header("Location: " . base_url($url));
        exit;
    }

    protected function back($with = []) {
        if (!empty($with)) {
            foreach ($with as $key => $value) {
                $_SESSION[$key] = $value;
            }
        }
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    protected function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $rule_parts = explode('|', $rule);
            
            foreach ($rule_parts as $rule_part) {
                if (strpos($rule_part, ':') !== false) {
                    list($rule_name, $rule_value) = explode(':', $rule_part);
                } else {
                    $rule_name = $rule_part;
                    $rule_value = null;
                }
                
                switch ($rule_name) {
                    case 'required':
                        if (empty($value)) {
                            $errors[$field] = ucfirst($field) . ' is required';
                        }
                        break;
                        
                    case 'min':
                        if (strlen($value) < $rule_value) {
                            $errors[$field] = ucfirst($field) . ' must be at least ' . $rule_value . ' characters';
                        }
                        break;
                        
                    case 'max':
                        if (strlen($value) > $rule_value) {
                            $errors[$field] = ucfirst($field) . ' must not exceed ' . $rule_value . ' characters';
                        }
                        break;
                        
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = 'Invalid email format';
                        }
                        break;
                        
                    case 'numeric':
                        if (!is_numeric($value)) {
                            $errors[$field] = ucfirst($field) . ' must be a number';
                        }
                        break;
                        
                    case 'date':
                        if (!strtotime($value)) {
                            $errors[$field] = 'Invalid date format';
                        }
                        break;
                }
            }
        }
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $data;
            return false;
        }
        
        return true;
    }

    protected function requirePermission($permission) {
        if (!has_permission($permission)) {
            if (!auth()) {
                $this->redirect('login', ['error' => 'Please login to continue']);
            }
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';
            exit;
        }
    }

    protected function logActivity($action, $details = []) {
        if (!auth()) {
            return false;
        }

        require_once __DIR__ . '/../models/AuditLog.php';
        $audit = new AuditLog();
        return $audit->log($this->user['id'], $action, $details);
    }
}
