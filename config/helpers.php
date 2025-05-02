<?php
if (!function_exists('base_url')) {
    function base_url($path = '') {
        $config = require_once __DIR__ . '/app.php';
        return $config['base_path'] . '/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    function redirect($path, $message = null, $type = 'success') {
        if ($message) {
            $_SESSION['flash'] = [
                'message' => $message,
                'type' => $type
            ];
        }
        header('Location: ' . base_url($path));
        exit;
    }
}

if (!function_exists('config')) {
    function config($key, $default = null) {
        static $config = null;
        if ($config === null) {
            $config = require_once __DIR__ . '/app.php';
        }

        $keys = explode('.', $key);
        $value = $config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('validate_csrf')) {
    function validate_csrf($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('old')) {
    function old($key, $default = '') {
        return $_SESSION['old'][$key] ?? $default;
    }
}

if (!function_exists('has_error')) {
    function has_error($field) {
        return isset($_SESSION['errors'][$field]);
    }
}

if (!function_exists('get_error')) {
    function get_error($field) {
        $error = $_SESSION['errors'][$field] ?? '';
        unset($_SESSION['errors'][$field]);
        return $error;
    }
}

if (!function_exists('format_money')) {
    function format_money($amount) {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}

if (!function_exists('format_date')) {
    function format_date($date, $format = 'd M Y') {
        return date($format, strtotime($date));
    }
}

if (!function_exists('is_active')) {
    function is_active($path) {
        $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $path = $base_path . '/' . ltrim($path, '/');
        return $current_path === $path;
    }
}

if (!function_exists('sanitize')) {
    function sanitize($data) {
        if (is_array($data)) {
            return array_map('sanitize', $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('is_ajax')) {
    function is_ajax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}

if (!function_exists('json_response')) {
    function json_response($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

if (!function_exists('asset')) {
    function asset($path) {
        return base_url('public/assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('generate_random_string')) {
    function generate_random_string($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $string;
    }
}

if (!function_exists('get_flash')) {
    function get_flash($key = null) {
        if ($key === null) {
            $flash = $_SESSION['flash'] ?? [];
            unset($_SESSION['flash']);
            return $flash;
        }
        
        $value = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $value;
    }
}

if (!function_exists('has_permission')) {
    function has_permission($permission) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // Admin has all permissions
        if ($_SESSION['role_name'] === 'admin') {
            return true;
        }

        // Check specific permissions based on role
        $permissions = [
            'cashier' => ['process_order', 'view_products'],
            'sales' => ['create_order', 'view_products', 'view_commissions']
        ];

        return isset($permissions[$_SESSION['role_name']]) && 
               in_array($permission, $permissions[$_SESSION['role_name']]);
    }
}
