<?php

// Define the application root directory
define('APP_ROOT', dirname(__DIR__));

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Autoload classes
spl_autoload_register(function ($class) {
    // Convert class name to file path
    $paths = [
        APP_ROOT . '/app/controllers/',
        APP_ROOT . '/app/models/',
        APP_ROOT . '/app/helpers/'
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Start session
session_start();

// Load configuration
$config = require_once APP_ROOT . '/config/app.php';

// Set timezone
date_default_timezone_set($config['timezone'] ?? 'Asia/Jakarta');

// Include routes
require_once APP_ROOT . '/app/routes.php';
