<?php

class BaseController {
    protected $requiresAuth = false;
    protected $config;
    protected $baseUrl;
    protected $debug = false;
    protected $layout = 'layouts/main';

    public function __construct() {
        $this->loadConfig();
        
        if ($this->requiresAuth) {
            $this->checkAuth();
        }
    }

    protected function loadConfig() {
        $defaultConfig = [
            'base_url' => 'http://localhost/Salvio2/public',
            'site_name' => 'POS & Pharmaceutical Distribution System',
            'timezone' => 'Asia/Jakarta',
            'debug' => false,
            'session' => [
                'lifetime' => 7200,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true
            ]
        ];

        $configFile = __DIR__ . '/../../config/app.php';
        if (file_exists($configFile)) {
            $fileConfig = require $configFile;
            if (is_array($fileConfig)) {
                $this->config = array_merge($defaultConfig, $fileConfig);
            } else {
                $this->config = $defaultConfig;
            }
        } else {
            $this->config = $defaultConfig;
        }

        $this->baseUrl = $this->config['base_url'];
        $this->debug = $this->config['debug'];
    }

    protected function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }

    public function render($view, $data = []) {
        // Add base URL and debug info to data array
        $data['baseUrl'] = $this->baseUrl;
        if ($this->debug) {
            $data['debugInfo'] = $this->getDebugInfo();
        }
        
        // Extract data to make variables available in view
        extract($data);

        // Start output buffering
        ob_start();

        // Include the view file
        $viewFile = __DIR__ . "/../views/{$view}.php";
        if (!file_exists($viewFile)) {
            throw new Exception("View file not found: {$view}");
        }

        require $viewFile;

        // Get the contents and clean the buffer
        $content = ob_get_clean();

        // Include the layout if it exists and not disabled
        if ($this->layout !== null) {
            $layoutFile = __DIR__ . "/../views/{$this->layout}.php";
            if (file_exists($layoutFile)) {
                require $layoutFile;
            } else {
                echo $content;
            }
        } else {
            echo $content;
        }
    }

    protected function getDebugInfo() {
        return [
            'uri' => $_SERVER['REQUEST_URI'],
            'method' => $_SERVER['REQUEST_METHOD'],
            'script' => $_SERVER['SCRIPT_NAME'],
            'path' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            'query' => $_SERVER['QUERY_STRING'] ?? '',
            'baseUrl' => $this->baseUrl,
            'fullUrl' => $this->getFullUrl(),
            'segments' => $this->getUriSegments(),
            'params' => $this->getAllParams()
        ];
    }

    protected function getFullUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    protected function getUriSegments() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = trim($path, '/');
        return $path ? explode('/', $path) : [];
    }

    protected function getAllParams() {
        return [
            'get' => $_GET,
            'post' => $_POST,
            'files' => $_FILES,
            'cookie' => $_COOKIE,
            'session' => $_SESSION ?? []
        ];
    }

    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect($path) {
        $url = $this->baseUrl . $path;
        header("Location: {$url}");
        exit;
    }

    protected function url($path = '') {
        return $this->baseUrl . $path;
    }

    protected function asset($path) {
        return $this->baseUrl . '/assets/' . ltrim($path, '/');
    }

    protected function getCurrentUser() {
        if (isset($_SESSION['user_id'])) {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT * FROM users WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        }
        return null;
    }

    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function isGet() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    protected function getPost($key = null, $default = null) {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    protected function getQuery($key = null, $default = null) {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    protected function debug($data) {
        if ($this->debug) {
            echo '<pre>';
            var_dump($data);
            echo '</pre>';
        }
    }
}
