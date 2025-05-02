<?php
// Load bootstrap
$app = require_once __DIR__ . '/../config/bootstrap.php';
$config = $app['config'];
$conn = $app['conn'];

// Get the request URI and remove base path
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = $config['base_path'];

// Remove base path from request URI
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Remove /public from request URI if present
if (strpos($requestUri, '/public') === 0) {
    $requestUri = substr($requestUri, strlen('/public'));
}

// Parse the path
$path = parse_url($requestUri, PHP_URL_PATH);

// Simple router
switch ($path) {
    case '/login':
        if (!isset($_SESSION['user_id'])) {
            view('user/login', ['config' => $config]);
        } else {
            redirect('/');
        }
        break;

    case '/auth/login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../controllers/AuthController.php';
            $auth = new AuthController();
            $auth->login($_POST['username'], $_POST['password']);
        } else {
            redirect('/login');
        }
        break;

    case '/logout':
        session_destroy();
        redirect('/login');
        break;

    case '':
    case '/':
        if (isset($_SESSION['user_id'])) {
            view('home', [
                'config' => $config,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'] ?? 'User',
                    'role' => $_SESSION['role_id'] ?? 'user'
                ]
            ]);
        } else {
            redirect('/login');
        }
        break;

    default:
        if (!isset($_SESSION['user_id'])) {
            redirect('/login');
        }
        
        // Check if file exists in public directory (for assets)
        $publicFile = __DIR__ . $path;
        if (file_exists($publicFile) && !is_dir($publicFile)) {
            $extension = pathinfo($publicFile, PATHINFO_EXTENSION);
            $mimeTypes = [
                'css' => 'text/css',
                'js' => 'application/javascript',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'woff' => 'application/font-woff',
                'woff2' => 'application/font-woff2',
                'ttf' => 'application/font-ttf',
                'eot' => 'application/vnd.ms-fontobject'
            ];
            
            if (isset($mimeTypes[$extension])) {
                header('Content-Type: ' . $mimeTypes[$extension]);
                readfile($publicFile);
                exit;
            }
        }

        // If not a public file, check for a view file
        $viewFile = __DIR__ . '/../views' . $path . '.php';
        if (file_exists($viewFile)) {
            view(ltrim($path, '/'), [
                'config' => $config,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'] ?? 'User',
                    'role' => $_SESSION['role_id'] ?? 'user'
                ]
            ]);
            exit;
        }

        // If no matching route or file found, show 404
        http_response_code(404);
        if ($config['debug']) {
            echo "404 Not Found: " . htmlspecialchars($path);
            echo "<br>Request URI: " . htmlspecialchars($requestUri);
            echo "<br>Base Path: " . htmlspecialchars($basePath);
        } else {
            view('errors/404', ['config' => $config]);
        }
        break;
}
