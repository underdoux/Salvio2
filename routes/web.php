<?php
// Authentication Routes
$router->get('/login', 'AuthController@showLoginForm');
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');

// Product Routes
$router->group(['middleware' => 'auth'], function($router) {
    // Product List
    $router->get('/products', function() {
        return require_once __DIR__ . '/../views/product/index.php';
    });

    // Product Detail
    $router->get('/products/{id}', function($id) {
        $productId = htmlspecialchars($id);
        return require_once __DIR__ . '/../views/product/detail.php';
    });

    // Product API Routes
    $router->get('/api/products', 'ProductController@index');
    $router->get('/api/products/stats', 'ProductController@getStats');
    $router->post('/api/products', 'ProductController@store');
    $router->get('/api/products/{id}', 'ProductController@show');
    $router->put('/api/products/{id}', 'ProductController@update');
    $router->delete('/api/products/{id}', 'ProductController@destroy');
    $router->post('/api/products/bulk-delete', 'ProductController@bulkDelete');
    $router->post('/api/products/import', 'ProductController@import');
    $router->get('/api/products/export', 'ProductController@export');
    $router->post('/api/products/validate-import', 'ProductController@validateImport');
    
    // Product Stock Routes
    $router->get('/api/products/{id}/stock-movements', 'StockController@getMovements');
    $router->post('/api/products/{id}/stock', 'StockController@adjust');
    
    // Product Sales Routes
    $router->get('/api/products/{id}/sales', 'ProductController@getSales');

    // BPOM Routes
    $router->get('/api/bpom/search', 'BPOMController@search');
    
    // Category Routes
    $router->get('/api/categories', 'CategoryController@index');
    $router->post('/api/categories', 'CategoryController@store');
    $router->put('/api/categories/{id}', 'CategoryController@update');
    $router->delete('/api/categories/{id}', 'CategoryController@destroy');
});

// Dashboard Routes
$router->group(['middleware' => 'auth'], function($router) {
    $router->get('/dashboard', 'DashboardController@index');
    $router->get('/api/dashboard/stats', 'DashboardController@getStats');
    $router->get('/api/dashboard/sales-chart', 'DashboardController@getSalesChart');
    $router->get('/api/dashboard/top-products', 'DashboardController@getTopProducts');
    $router->get('/api/dashboard/recent-activities', 'DashboardController@getRecentActivities');
});

// Settings Routes
$router->group(['middleware' => ['auth', 'permission:manage_settings']], function($router) {
    $router->get('/settings', 'SettingController@index');
    $router->get('/api/settings', 'SettingController@getAll');
    $router->post('/api/settings', 'SettingController@update');
    $router->post('/api/settings/reset', 'SettingController@reset');
});

// User Management Routes
$router->group(['middleware' => ['auth', 'permission:manage_users']], function($router) {
    $router->get('/users', 'UserController@index');
    $router->post('/api/users', 'UserController@store');
    $router->put('/api/users/{id}', 'UserController@update');
    $router->delete('/api/users/{id}', 'UserController@destroy');
    $router->post('/api/users/{id}/reset-password', 'UserController@resetPassword');
});

// Role Management Routes
$router->group(['middleware' => ['auth', 'permission:manage_roles']], function($router) {
    $router->get('/roles', 'RoleController@index');
    $router->post('/api/roles', 'RoleController@store');
    $router->put('/api/roles/{id}', 'RoleController@update');
    $router->delete('/api/roles/{id}', 'RoleController@destroy');
});

// Error Handlers
$router->set404(function() {
    header('HTTP/1.1 404 Not Found');
    return require_once __DIR__ . '/../views/errors/404.php';
});

$router->setError(function($error) {
    error_log($error->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    return require_once __DIR__ . '/../views/errors/500.php';
});
