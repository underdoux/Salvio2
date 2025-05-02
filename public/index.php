<?php
// Load bootstrap
$app = require_once __DIR__ . '/../config/bootstrap.php';

// Parse URL
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$script_name = dirname($_SERVER['SCRIPT_NAME']);
$base_path = rtrim($script_name, '/');
$path = substr(urldecode($request_uri), strlen($base_path));

// Default route
if ($path == '' || $path == '/') {
    $path = '/home';
}

// Route definitions
$routes = [
    '/login' => ['AuthController', 'showLogin'],
    '/auth/login' => ['AuthController', 'login'],
    '/logout' => ['AuthController', 'logout'],
    '/auth/change-password' => ['AuthController', 'changePassword'],
    '/home' => ['HomeController', 'index'],
    '/products' => ['ProductController', 'index'],
    '/products/create' => ['ProductController', 'create'],
    '/products/edit' => ['ProductController', 'edit'],
    '/products/delete' => ['ProductController', 'delete'],
    '/orders' => ['OrderController', 'index'],
    '/orders/create' => ['OrderController', 'create'],
    '/orders/edit' => ['OrderController', 'edit'],
    '/orders/delete' => ['OrderController', 'delete'],
    '/commissions' => ['CommissionController', 'index'],
    '/profits' => ['ProfitDistributionController', 'index'],
    '/reports' => ['ReportController', 'index'],
    '/settings' => ['SettingController', 'index']
];

// Extract controller and action from path
$path = strtok($path, '?');
$path = rtrim($path, '/');

try {
    if (isset($routes[$path])) {
        // Get controller and action
        list($controller_name, $action) = $routes[$path];
        
        // Include controller file
        $controller_file = __DIR__ . "/../controllers/{$controller_name}.php";
        if (!file_exists($controller_file)) {
            throw new Exception("Controller file not found: {$controller_file}");
        }
        require_once $controller_file;
        
        // Create controller instance
        $controller = new $controller_name();
        
        // Call action
        if (method_exists($controller, $action)) {
            // Get query parameters
            $params = $_GET;
            unset($params['route']);
            
            // Call action with parameters
            call_user_func_array([$controller, $action], $params);
        } else {
            throw new Exception("Action not found: {$action}");
        }
    } else {
        // Check if it's an asset request
        if (preg_match('/\.(css|js|jpg|jpeg|png|gif|ico)$/', $path)) {
            $file = __DIR__ . $path;
            if (file_exists($file)) {
                // Get file extension
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                
                // Set content type
                $content_types = [
                    'css' => 'text/css',
                    'js' => 'application/javascript',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'ico' => 'image/x-icon'
                ];
                
                if (isset($content_types[$ext])) {
                    header('Content-Type: ' . $content_types[$ext]);
                    readfile($file);
                    exit;
                }
            }
        }
        
        // Route not found
        http_response_code(404);
        require_once __DIR__ . '/../views/errors/404.php';
    }
} catch (Exception $e) {
    // Log error
    error_log($e->getMessage());
    
    // Show error page
    http_response_code(500);
    require_once __DIR__ . '/../views/errors/500.php';
}
