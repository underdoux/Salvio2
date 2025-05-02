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

// Basic routing
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($uri, '/');

// Remove the base path (Salvio2/public)
$basePath = 'Salvio2/public';
if (strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}
$uri = trim($uri, '/');

// Default route
if (empty($uri)) {
    $uri = 'home/index';
}

// Split into controller, action, and parameters
$parts = explode('/', $uri);
$controllerName = ucfirst($parts[0]) . 'Controller';
$actionName = isset($parts[1]) ? $parts[1] : 'index';

// Get additional parameters
$params = array_slice($parts, 2);

// Check if controller exists
if (!file_exists(__DIR__ . "/../app/controllers/{$controllerName}.php")) {
    http_response_code(404);
    die('Controller not found');
}

// Create controller instance and call action
$controller = new $controllerName();
if (!method_exists($controller, $actionName)) {
    http_response_code(404);
    die('Action not found');
}

// Call the action with parameters
call_user_func_array([$controller, $actionName], $params);
