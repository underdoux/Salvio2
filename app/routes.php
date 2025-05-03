<?php

// Map URLs to controller actions
$routes = [
    // Auth routes
    'login' => ['AuthController', 'index'],
    'auth' => ['AuthController', 'index'],
    'auth/logout' => ['AuthController', 'logout'],
    
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
    'commissions/payment/{id}' => ['CommissionsController', 'recordPayment'],
    'commissions/void-payment/{id}' => ['CommissionsController', 'voidPayment'],
    'commissions/payment-history/{id}' => ['CommissionsController', 'paymentHistory'],
    'commissions/pending-payments' => ['CommissionsController', 'pendingPayments'],
    'commissions/payment-summary' => ['CommissionsController', 'paymentSummary'],
    'commissions/reports' => ['CommissionsController', 'reports'],
    'commissions/export-report' => ['CommissionsController', 'exportReport'],
    'commissions/performance-metrics/{id?}' => ['CommissionsController', 'performanceMetrics'],
    'commissions/product-trends' => ['CommissionsController', 'productTrends'],
    'commissions/period-summary' => ['CommissionsController', 'periodSummary'],
    
    // Profit Sharing routes
    'profit-sharing' => ['ProfitSharingController', 'index'],
    'profit-sharing/calculate' => ['ProfitSharingController', 'calculate'],
    'profit-sharing/view/{id}' => ['ProfitSharingController', 'view'],
    'profit-sharing/finalize/{id}' => ['ProfitSharingController', 'finalize'],
    'profit-sharing/report/{id}' => ['ProfitSharingController', 'report'],
    'profit-sharing/trends' => ['ProfitSharingController', 'trends'],
    'profit-sharing/trend-data' => ['ProfitSharingController', 'getTrendData'],
    'profit-sharing/investor-trends/{id}' => ['ProfitSharingController', 'getInvestorTrends'],
    'profit-sharing/profit-breakdown/{id}' => ['ProfitSharingController', 'getProfitBreakdown'],
    'profit-sharing/export/profit/{month}' => ['ProfitSharingController', 'exportProfitReport'],
    'profit-sharing/export/distributions' => ['ProfitSharingController', 'exportDistributionHistory'],
    'profit-sharing/export/investor/{id}' => ['ProfitSharingController', 'exportInvestorReport'],
    
    // Reports routes
    'reports' => ['HomeController', 'reports'],
    
    // Analytics routes
    'analytics' => ['AnalyticsController', 'index'],
    'analytics/best-selling' => ['AnalyticsController', 'bestSelling'],
    'analytics/least-performing' => ['AnalyticsController', 'leastPerforming'],
    'analytics/market-response' => ['AnalyticsController', 'marketResponse'],
    'analytics/sales-trends' => ['AnalyticsController', 'salesTrends'],
    'analytics/product/{id}' => ['AnalyticsController', 'productMetrics'],
    'analytics/category/{id}' => ['AnalyticsController', 'categoryMetrics'],
    'analytics/export/best-selling' => ['AnalyticsController', 'exportBestSelling'],
    'analytics/export/market-response' => ['AnalyticsController', 'exportMarketResponse'],
    'analytics/export/sales-trends' => ['AnalyticsController', 'exportSalesTrends'],
    'analytics/export/product/{id}' => ['AnalyticsController', 'exportProductMetrics'],

    // Default route
    '' => ['HomeController', 'index']
];

// Get the current URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($uri, '/');

echo "DEBUG URI: " . $uri . "\n"; // Debug output

// Remove the base path (/Salvio2/public)
$basePath = '/Salvio2/public';
if (strpos('/' . $uri, $basePath) === 0) {
    $uri = substr('/' . $uri, strlen($basePath));
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
