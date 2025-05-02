<?php
// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/php_errors.log');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
$config = [
    'app_name' => 'POS Pharma',
    'app_version' => '1.0.0',
    'base_path' => '/Salvio2',
    'debug' => true
];

// Try to load config file
$configFile = __DIR__ . '/app.php';
if (file_exists($configFile)) {
    $loadedConfig = @include $configFile;
    if (is_array($loadedConfig)) {
        $config = array_merge($config, $loadedConfig);
    }
}

// Set timezone
date_default_timezone_set($config['timezone'] ?? 'Asia/Jakarta');

// Define common functions
function base_url($path = '') {
    global $config;
    return $config['base_path'] . $path;
}

function redirect($path) {
    global $config;
    $url = $config['base_path'] . $path;
    header("Location: $url");
    exit;
}

function view($path, $data = []) {
    extract($data);
    require __DIR__ . '/../views/' . $path . '.php';
}

function asset($path) {
    global $config;
    return $config['base_path'] . '/public/assets/' . $path;
}

function config($key, $default = null) {
    global $config;
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

// Error handler
function errorHandler($errno, $errstr, $errfile, $errline) {
    $message = date('Y-m-d H:i:s') . " - Error [$errno] $errstr in $errfile on line $errline\n";
    error_log($message, 3, __DIR__ . '/../storage/logs/php_errors.log');
    
    if (config('debug', false)) {
        echo "<h1>Error</h1>";
        echo "<p>$errstr</p>";
        echo "<p>File: $errfile</p>";
        echo "<p>Line: $errline</p>";
    } else {
        echo "<h1>An error occurred</h1>";
        echo "<p>Please try again later.</p>";
    }
    
    return true;
}

// Exception handler
function exceptionHandler($e) {
    $message = date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . 
               " in " . $e->getFile() . " on line " . $e->getLine() . "\n" .
               $e->getTraceAsString() . "\n";
    error_log($message, 3, __DIR__ . '/../storage/logs/php_errors.log');
    
    if (config('debug', false)) {
        echo "<h1>Exception</h1>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "<p>File: " . $e->getFile() . "</p>";
        echo "<p>Line: " . $e->getLine() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    } else {
        echo "<h1>An error occurred</h1>";
        echo "<p>Please try again later.</p>";
    }
}

// Set error and exception handlers
set_error_handler('errorHandler');
set_exception_handler('exceptionHandler');

// Database connection
require_once __DIR__ . '/database.php';
$db = new Database();
$conn = $db->getConnection();

return [
    'config' => $config,
    'conn' => $conn
];
