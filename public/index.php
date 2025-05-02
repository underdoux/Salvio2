<?php
// Initialize application
require_once __DIR__ . '/../config/bootstrap.php';

// Parse URL
$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_path = config('base_path');

// Remove base path from URL
if (strpos($url, $base_path) === 0) {
    $url = substr($url, strlen($base_path));
}
$url = trim($url, '/');

// Default route
if (empty($url)) {
    $url = 'home';
}

// Route to appropriate controller/action
$parts = explode('/', $url);
$controller_name = ucfirst($parts[0]) . 'Controller';
$action = $parts[1] ?? 'index';
$params = array_slice($parts, 2);

// Load controller
$controller_file = __DIR__ . "/../controllers/{$controller_name}.php";

try {
    if (!file_exists($controller_file)) {
        throw new Exception("Controller not found: {$controller_name}");
    }

    require_once $controller_file;
    
    if (!class_exists($controller_name)) {
        throw new Exception("Controller class not found: {$controller_name}");
    }

    $controller = new $controller_name();
    
    if (!method_exists($controller, $action)) {
        throw new Exception("Action not found: {$action}");
    }

    // Call controller action with parameters
    echo call_user_func_array([$controller, $action], $params);

} catch (Exception $e) {
    error_log($e->getMessage());
    
    if (config('debug')) {
        echo "<pre>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
    } else {
        http_response_code(404);
        require __DIR__ . '/../views/errors/404.php';
    }
}
