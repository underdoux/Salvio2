<?php

// Define routes
$routes = [
    // Auth routes
    '/login' => ['AuthController', 'login'],
    '/logout' => ['AuthController', 'logout'],
    
    // Dashboard
    '/' => ['HomeController', 'index'],
    '/dashboard' => ['HomeController', 'index'],
    
    // Orders
    '/orders' => ['OrdersController', 'index'],
    '/orders/create' => ['OrdersController', 'create'],
    '/orders/view/{id}' => ['OrdersController', 'view'],
    
    // Products
    '/products' => ['ProductsController', 'index'],
    '/products/create' => ['ProductsController', 'create'],
    '/products/edit/{id}' => ['ProductsController', 'edit'],
    
    // Commissions
    '/commissions' => ['CommissionsController', 'index'],
    '/commissions/details/{id}' => ['CommissionsController', 'details'],
    '/commissions/rates' => ['CommissionsController', 'rates'],
    
    // Profit Sharing
    '/profit-sharing' => ['ProfitSharingController', 'index'],
    '/profit-sharing/view/{id}' => ['ProfitSharingController', 'view'],
    '/profit-sharing/report' => ['ProfitSharingController', 'report'],
    '/profit-sharing/calculate' => ['ProfitSharingController', 'calculate'],
    '/profit-sharing/finalize/{id}' => ['ProfitSharingController', 'finalize'],
    '/profit-sharing/process-payment' => ['ProfitSharingController', 'processPayment'],
    
    // Analytics
    '/analytics' => ['AnalyticsController', 'index'],
    '/analytics/trends' => ['AnalyticsController', 'trends'],
    '/analytics/market-response' => ['AnalyticsController', 'marketResponse'],
    '/analytics/performance' => ['AnalyticsController', 'performance'],
    '/analytics/predictions' => ['AnalyticsController', 'predictions'],
    '/analytics/calculate' => ['AnalyticsController', 'calculate'],
    '/analytics/export' => ['AnalyticsController', 'export'],

    // System Settings
    '/settings' => ['SettingsController', 'index'],
    '/settings/edit/{key}' => ['SettingsController', 'edit'],
    '/settings/update/{key}' => ['SettingsController', 'update'],
];

// Get the current URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/Salvio2/public', '', $uri);

// Route not found by default
$routeFound = false;

foreach ($routes as $route => $handler) {
    // Convert route parameters to regex pattern
    $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route);
    $pattern = str_replace('/', '\/', $pattern);
    $pattern = '/^' . $pattern . '$/';
    
    if (preg_match($pattern, $uri, $matches)) {
        $controllerName = $handler[0];
        $methodName = $handler[1];
        
        // Remove the full match from the matches array
        array_shift($matches);
        
        // Include and instantiate the controller
        require_once __DIR__ . "/controllers/{$controllerName}.php";
        $controller = new $controllerName();
        
        // Call the method with any parameters
        call_user_func_array([$controller, $methodName], $matches);
        
        $routeFound = true;
        break;
    }
}

// If no route was found, show 404 error
if (!$routeFound) {
    http_response_code(404);
    require __DIR__ . '/views/errors/404.php';
}
