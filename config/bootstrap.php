<?php

// Start session
session_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Load configuration
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

// Create storage directories if they don't exist
$storage_dirs = [
    'storage/logs',
    'storage/backups',
    'storage/cache',
    'storage/uploads',
    'storage/exports'
];

foreach ($storage_dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Set up error handling
function errorHandler($errno, $errstr, $errfile, $errline) {
    $log_file = __DIR__ . '/../storage/logs/php_errors.log';
    $timestamp = date('d-M-Y H:i:s e');
    $message = "[$timestamp] $errstr in $errfile on line $errline\n";
    error_log($message, 3, $log_file);
    
    if (config('debug')) {
        echo "<div style='background:#FFF0F0;padding:10px;margin:10px;border:1px solid #FFD0D0;'>";
        echo "<h3>Error</h3>";
        echo "<p><strong>Message:</strong> $errstr</p>";
        echo "<p><strong>File:</strong> $errfile</p>";
        echo "<p><strong>Line:</strong> $errline</p>";
        echo "</div>";
    } else {
        include __DIR__ . '/../views/errors/500.php';
    }
    
    return true;
}
set_error_handler('errorHandler');

// Set up exception handling
function exceptionHandler($exception) {
    $log_file = __DIR__ . '/../storage/logs/php_errors.log';
    $timestamp = date('d-M-Y H:i:s e');
    $message = "[$timestamp] Uncaught Exception: " . $exception->getMessage() . 
               " in " . $exception->getFile() . 
               " on line " . $exception->getLine() . "\n";
    error_log($message, 3, $log_file);
    
    if (config('debug')) {
        echo "<div style='background:#FFF0F0;padding:10px;margin:10px;border:1px solid #FFD0D0;'>";
        echo "<h3>Uncaught Exception</h3>";
        echo "<p><strong>Message:</strong> " . $exception->getMessage() . "</p>";
        echo "<p><strong>File:</strong> " . $exception->getFile() . "</p>";
        echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
        echo "<h4>Stack Trace:</h4>";
        echo "<pre>" . $exception->getTraceAsString() . "</pre>";
        echo "</div>";
    } else {
        include __DIR__ . '/../views/errors/500.php';
    }
}
set_exception_handler('exceptionHandler');

// Set up shutdown function
function shutdownHandler() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $log_file = __DIR__ . '/../storage/logs/php_errors.log';
        $timestamp = date('d-M-Y H:i:s e');
        $message = "[$timestamp] Fatal Error: " . $error['message'] . 
                   " in " . $error['file'] . 
                   " on line " . $error['line'] . "\n";
        error_log($message, 3, $log_file);
        
        if (!config('debug')) {
            include __DIR__ . '/../views/errors/500.php';
        }
    }
}
register_shutdown_function('shutdownHandler');

// Clean old session data
function cleanOldSessions() {
    $session_lifetime = config('session_lifetime', 7200); // 2 hours default
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $session_lifetime)) {
        session_unset();
        session_destroy();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}
cleanOldSessions();

// Set up CSRF protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        include __DIR__ . '/../views/errors/403.php';
        exit;
    }
}

// Set up output buffering
ob_start();
