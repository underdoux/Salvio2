<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Basic routing logic (can be expanded)
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($request) {
    case '/' :
    case '/home' :
        require __DIR__ . '/../views/home.php';
        break;
    case '/login' :
        require __DIR__ . '/../controllers/UserController.php';
        $controller = new UserController();
        $controller->login();
        break;
    case '/logout' :
        require __DIR__ . '/../controllers/UserController.php';
        $controller = new UserController();
        $controller->logout();
        break;
    case '/products' :
        require __DIR__ . '/../controllers/ProductController.php';
        $controller = new ProductController();
        $controller->list();
        break;
    case '/products/add' :
        require __DIR__ . '/../controllers/ProductController.php';
        $controller = new ProductController();
        $controller->add();
        break;
    case '/orders' :
        require __DIR__ . '/../controllers/OrderController.php';
        $controller = new OrderController();
        $controller->list();
        break;
    case '/orders/create' :
        require __DIR__ . '/../controllers/OrderController.php';
        $controller = new OrderController();
        $controller->create();
        break;
    default:
        http_response_code(404);
        echo "404 Not Found";
        break;
}
?>
