<?php

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
        
        $this->render('auth/login', [
            'title' => 'Login - Salvio POS'
        ]);
    }

    public function login() {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = $this->userModel->authenticate($username, $password);

        if ($user) {
            $_SESSION['user'] = $user;
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'Welcome back, ' . $user['username'] . '!'
            ];
            $this->redirect('/Salvio2/public/');
        } else {
            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => 'Invalid username or password'
            ];
            $this->redirect('/Salvio2/public/auth');
        }
    }

    public function logout() {
        session_destroy();
        $this->redirect('/Salvio2/public/auth');
    }
}
