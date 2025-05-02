<?php

require_once __DIR__ . '/../helpers/Logger.php';

class AuthController extends BaseController {
    private $userModel;
    protected $requiresAuth = false;

    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
    }

    public function index() {
        // If already logged in, redirect to home
        if (isset($_SESSION['user'])) {
            $this->redirect('/Salvio2/public/');
        }

        // Handle POST request for login
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleLogin();
            return;
        }
        
        // Show login form for GET request
        $this->render('auth/login', [
            'title' => 'Login - Salvio POS'
        ]);
    }

    private function handleLogin() {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = $this->userModel->authenticate($username, $password);

        if ($user) {
            Logger::log("User '{$username}' logged in successfully.");
            $_SESSION['user'] = $user;
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'Welcome back, ' . $user['username'] . '!'
            ];
            $this->redirect('/Salvio2/public/');
        } else {
            Logger::log("Failed login attempt for username '{$username}'.");
            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => 'Invalid username or password'
            ];
            $this->redirect('/Salvio2/public/auth');
        }
    }

    public function logout() {
        if (isset($_SESSION['user'])) {
            Logger::log("User '{$_SESSION['user']['username']}' logged out.");
        }
        session_destroy();
        $this->redirect('/Salvio2/public/auth');
    }
}
