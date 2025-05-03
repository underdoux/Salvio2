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

// Load routes
require_once __DIR__ . '/../app/routes.php';

// Get the current URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($uri, '/');

// Remove the base path (/Salvio2/public)
$basePath = 'Salvio2/public';
if (strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}
$uri = trim($uri, '/');

Logger::log("Processing URI: " . $uri);

// Find matching route
$matchedRoute = null;
$params = [];

foreach ($routes as $pattern => $handler) {
    // Convert route pattern to regex
    $regexPattern = str_replace('/', '\/', $pattern);
    $regexPattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^\/]+)', $regexPattern);
    $regexPattern = "/^{$regexPattern}$/";
    
    if (preg_match($regexPattern, $uri, $matches)) {
        $matchedRoute = $handler;
        // Extract named parameters
        foreach ($matches as $key => $value) {
            if (!is_numeric($key)) {
                $params[$key] = $value;
            }
        }
        break;
    }
}

try {
    if ($matchedRoute) {
        [$controllerName, $actionName] = $matchedRoute;
        
        // Create controller instance
        $controller = new $controllerName();
        
        // Call the action with parameters
        call_user_func_array([$controller, $actionName], $params);
    } else {
        Logger::log("No route found for URI: " . $uri);
        
        // Create controller instance for 404 page
        $controller = new BaseController();
        $controller->render('errors/404', [
            'title' => '404 Not Found',
            'description' => 'The page you are looking for could not be found.'
        ]);
    }
} catch (Exception $e) {
    Logger::log("Routing error: " . $e->getMessage());
    
    // Create controller instance for error page
    $controller = new BaseController();
    $controller->render('errors/404', [
        'title' => 'Error',
        'description' => 'An error occurred while processing your request.'
    ]);
}
