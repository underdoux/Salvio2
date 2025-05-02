<?php
// Start session
session_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/php_errors.log');

// Load configuration
$config = require_once __DIR__ . '/app.php';

// Load helpers
require_once __DIR__ . '/helpers.php';

// Set timezone
date_default_timezone_set($config['timezone']);

// Database connection
try {
    $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
    $conn = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your configuration.");
}

// Set up error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        // Error is not specified in error_reporting
        return;
    }

    $error_type = match ($errno) {
        E_USER_ERROR => 'Fatal Error',
        E_USER_WARNING => 'Warning',
        E_USER_NOTICE => 'Notice',
        default => 'Unknown Error'
    };

    error_log("$error_type: $errstr in $errfile on line $errline");

    if ($errno == E_USER_ERROR) {
        exit(1);
    }

    return true;
});

// Set up exception handler
set_exception_handler(function($e) {
    error_log($e->getMessage());
    
    if (config('debug')) {
        throw $e;
    } else {
        http_response_code(500);
        if (is_ajax()) {
            json_response(['error' => 'An error occurred. Please try again.'], 500);
        } else {
            require_once __DIR__ . '/../views/errors/500.php';
        }
    }
});

// Clean old session data
if (isset($_SESSION['old'])) {
    unset($_SESSION['old']);
}
if (isset($_SESSION['errors'])) {
    unset($_SESSION['errors']);
}

// Store POST data in session for form repopulation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['old'] = $_POST;
}

// Return application container
return [
    'config' => $config,
    'conn' => $conn
];
