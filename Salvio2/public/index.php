<?php

// Load bootstrap file
require_once __DIR__ . '/../config/bootstrap.php';

// Parse URL
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = parse_url(config('base_url'), PHP_URL_PATH);

if ($base_path && strpos($request_uri, $base_path) === 0) {
    $request_uri = substr($request_uri, strlen($base_path));
}

$request_uri = trim($request_uri, '/');
$uri_parts = explode('?', $request_uri);
$path = $uri_parts[0];

if (empty($path)) {
    $path = 'home';
}

// Route the request
$path_parts = explode('/', $path);
$controller_name = ucfirst(array_shift($path_parts)) . 'Controller';
$action = array_shift($path_parts) ?: 'index';
$params = $path_parts;

// Load controller
$controller_file = __DIR__ . "/../controllers/{$controller_name}.php";

if (!file_exists($controller_file)) {
    http_response_code(404);
    include __DIR__ . '/../views/errors/404.php';
    exit;
}

require_once $controller_file;

// Create controller instance
if (!class_exists($controller_name)) {
    http_response_code(404);
    include __DIR__ . '/../views/errors/404.php';
    exit;
}

$controller = new $controller_name();

// Call action
if (!method_exists($controller, $action)) {
    http_response_code(404);
    include __DIR__ . '/../views/errors/404.php';
    exit;
}

try {
    // Call action with parameters
    $response = call_user_func_array([$controller, $action], $params);
    
    // Output response
    if (is_string($response)) {
        echo $response;
    }
    
} catch (Exception $e) {
    if (config('debug')) {
        throw $e;
    } else {
        error_log($e->getMessage());
        http_response_code(500);
        include __DIR__ . '/../views/errors/500.php';
    }
}

// Flush output buffer
ob_end_flush();
