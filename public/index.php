<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set session cookie parameters
ini_set('session.gc_maxlifetime', 3600); // 1 hour
ini_set('session.cookie_lifetime', 3600); // 1 hour

// Set session cookie path to /Salvio2 to ensure cookie is sent on all requests
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/Salvio2/public',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration and helpers
$config = require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/Logger.php';

// Database connection
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $db = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    // Test connection
    $db->query("SELECT 1");
    Logger::log("Database connection established successfully");
} catch (PDOException $e) {
    Logger::log("Database connection failed: " . $e->getMessage());
    die('Connection failed: ' . $e->getMessage());
}

// Make database connection available globally
$GLOBALS['db'] = $db;

// Autoload classes
spl_autoload_register(function ($class) {
    // Convert class name to file path
    $paths = [
        __DIR__ . '/../app/controllers/',
        __DIR__ . '/../app/models/',
        __DIR__ . '/../app/helpers/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Load and process routes
require_once __DIR__ . '/../app/routes.php';
