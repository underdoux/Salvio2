<?php

if (!function_exists('config')) {
    function config($key, $default = null) {
        static $config = null;
        if ($config === null) {
            $configFile = __DIR__ . '/app.php';
            if (!file_exists($configFile)) {
                return $default;
            }
            $config = include $configFile;
            if (!is_array($config)) {
                return $default;
            }
        }
        
        $keys = explode('.', $key);
        $value = $config;
        
        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        
        return $value;
    }
}

if (!function_exists('base_url')) {
    function base_url($path = '') {
        $base_url = config('base_url', '');
        return rtrim($base_url, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset($path) {
        return base_url('assets/' . ltrim($path, '/'));
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

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
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
        return $_SESSION['errors'][$field] ?? '';
    }
}

if (!function_exists('flash')) {
    function flash($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}

if (!function_exists('redirect')) {
    function redirect($path) {
        header('Location: ' . base_url($path));
        exit;
    }
}

if (!function_exists('auth')) {
    function auth() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('user')) {
    function user() {
        if (!auth()) {
            return null;
        }
        
        static $user = null;
        if ($user === null) {
            require_once __DIR__ . '/../models/User.php';
            $userModel = new User();
            $user = $userModel->findById($_SESSION['user_id']);
        }
        return $user;
    }
}

if (!function_exists('has_permission')) {
    function has_permission($permission) {
        if (!auth()) {
            return false;
        }
        
        require_once __DIR__ . '/../models/Role.php';
        $roleModel = new Role();
        return $roleModel->hasPermission(user()['role_id'], $permission);
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

if (!function_exists('format_datetime')) {
    function format_datetime($datetime, $format = 'd M Y H:i') {
        return date($format, strtotime($datetime));
    }
}

if (!function_exists('sanitize')) {
    function sanitize($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
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
