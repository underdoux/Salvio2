<?php

class AuthController extends BaseController {
    protected $requiresAuth = false;
    protected $layout = null; // Disable layout for auth pages

    public function login() {
        // If already logged in, redirect to dashboard
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }

        if ($this->isPost()) {
            $username = $this->getPost('username');
            $password = $this->getPost('password');

            try {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    $this->redirect('/dashboard');
                } else {
                    return $this->render('auth/login', [
                        'error' => 'Invalid username or password',
                        'username' => $username
                    ]);
                }
            } catch (Exception $e) {
                Logger::log("Login error: " . $e->getMessage(), 'ERROR');
                return $this->render('auth/login', [
                    'error' => 'An error occurred during login. Please try again.',
                    'username' => $username
                ]);
            }
        }

        // GET request - show login form
        return $this->render('auth/login', [
            'title' => 'Login - Salvio POS'
        ]);
    }

    public function logout() {
        session_start();
        session_destroy();
        $this->redirect('/login');
    }
}
