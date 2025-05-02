<?php

// Map URLs to controller actions
$routes = [
    // Auth routes
    'login' => ['AuthController', 'index'],
    'auth' => ['AuthController', 'index'],
    'logout' => ['AuthController', 'logout'],
    
    // Product routes
    'products' => ['ProductsController', 'index'],
    'products/create' => ['ProductsController', 'create'],
    'products/edit/{id}' => ['ProductsController', 'edit'],
    'products/delete/{id}' => ['ProductsController', 'delete'],
    
    // Order routes
    'orders' => ['OrdersController', 'index'],
    'orders/create' => ['OrdersController', 'create'],
    'orders/view/{id}' => ['OrdersController', 'view'],
    'orders/update-status/{id}' => ['OrdersController', 'updateStatus'],
    
    // Commission routes
    'commissions' => ['CommissionsController', 'index'],
    'commissions/rates' => ['CommissionsController', 'rates'],
    'commissions/update-rate' => ['CommissionsController', 'updateRate'],
    'commissions/details/{id}' => ['CommissionsController', 'details'],
    
    // Profit Sharing routes
    'profit-sharing' => ['ProfitSharingController', 'index'],
    'profit-sharing/calculate' => ['ProfitSharingController', 'calculate'],
    'profit-sharing/view/{id}' => ['ProfitSharingController', 'view'],
    'profit-sharing/finalize/{id}' => ['ProfitSharingController', 'finalize'],
    'profit-sharing/report/{id}' => ['ProfitSharingController', 'report'],
    
    // Default route
    '' => ['HomeController', 'index']
];

// Get the current URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($uri, '/');

// Remove the base path (Salvio2/public)
$basePath = 'Salvio2/public';
if (strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}
$uri = trim($uri, '/');

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

if ($matchedRoute) {
    [$controllerName, $actionName] = $matchedRoute;
    
    // Create controller instance
    $controller = new $controllerName();
    
    // Call the action with parameters
    call_user_func_array([$controller, $actionName], $params);
} else {
    http_response_code(404);
    die('Route not found');
}
