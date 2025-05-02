<?php
class Controller {
    protected $db;
    protected $user;

    public function __construct() {
        // Initialize database connection
        $database = new Database();
        $this->db = $database->getConnection();

        // Set current user for audit logs if logged in
        if (isset($_SESSION['user_id'])) {
            $this->setCurrentUser($_SESSION['user_id']);
        }
    }

    protected function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            redirect('login', 'Please login to continue.', 'warning');
        }
    }

    protected function requirePermission($permission) {
        if (!has_permission($permission)) {
            if (is_ajax()) {
                json_response(['error' => 'Unauthorized access.'], 403);
            } else {
                redirect('403', 'You do not have permission to access this page.', 'error');
            }
        }
    }

    protected function view($name, $data = []) {
        // Extract data to make variables available in view
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        $viewPath = __DIR__ . "/../views/{$name}.php";
        if (!file_exists($viewPath)) {
            throw new Exception("View {$name} not found");
        }
        
        require $viewPath;
        
        // Get the buffered content
        $content = ob_get_clean();
        
        // Return the content
        return $content;
    }

    protected function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $ruleArray = explode('|', $rule);
            
            foreach ($ruleArray as $singleRule) {
                if (strpos($singleRule, ':') !== false) {
                    [$ruleName, $ruleValue] = explode(':', $singleRule);
                } else {
                    $ruleName = $singleRule;
                    $ruleValue = null;
                }
                
                switch ($ruleName) {
                    case 'required':
                        if (empty($value)) {
                            $errors[$field] = ucfirst($field) . ' is required';
                        }
                        break;
                        
                    case 'min':
                        if (strlen($value) < $ruleValue) {
                            $errors[$field] = ucfirst($field) . ' must be at least ' . $ruleValue . ' characters';
                        }
                        break;
                        
                    case 'max':
                        if (strlen($value) > $ruleValue) {
                            $errors[$field] = ucfirst($field) . ' must not exceed ' . $ruleValue . ' characters';
                        }
                        break;
                        
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = 'Invalid email address';
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
                        
                    case 'matches':
                        if ($value !== $data[$ruleValue]) {
                            $errors[$field] = ucfirst($field) . ' does not match ' . $ruleValue;
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

    protected function setCurrentUser($userId) {
        try {
            $stmt = $this->db->prepare("CALL set_current_user(?)");
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Error setting current user: " . $e->getMessage());
        }
    }

    protected function logAudit($action, $details = []) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_log (user_id, action, details)
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute([
                $_SESSION['user_id'] ?? null,
                $action,
                json_encode($details)
            ]);
        } catch (PDOException $e) {
            error_log("Error logging audit: " . $e->getMessage());
        }
    }

    protected function beginTransaction() {
        return $this->db->beginTransaction();
    }

    protected function commit() {
        return $this->db->commit();
    }

    protected function rollback() {
        return $this->db->rollBack();
    }
}
