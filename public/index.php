<?php
session_start();

// Load configuration
$config = require_once __DIR__ . '/../config/database.php';

// Database connection
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $db = new PDO($dsn, $config['username'], $config['password'], $config['options']);
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

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
