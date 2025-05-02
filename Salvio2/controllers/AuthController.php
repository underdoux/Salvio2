<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/User.php';

class AuthController extends Controller {
    private $userModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
    }

    public function showLogin() {
        // If already logged in, redirect to dashboard
        if (isset($_SESSION['user_id'])) {
            redirect('/');
        }

        // Generate CSRF token
        $csrf_token = $this->generateCSRFToken();
        
        // Show login page
        $this->view('user/login', [
            'csrf_token' => $csrf_token
        ]);
    }

    public function login($username = null, $password = null) {
        try {
            // Validate CSRF token
            $this->validateCSRF();

            // Get POST data if not provided
            if ($username === null || $password === null) {
                $data = $this->getPostData();
                $username = $data['username'] ?? '';
                $password = $data['password'] ?? '';
            }

            // Validate input
            $errors = $this->validateRequired([
                'username' => $username,
                'password' => $password
            ], ['username', 'password']);

            if (!empty($errors)) {
                $_SESSION['error'] = 'Username and password are required';
                redirect('/login');
                return;
            }

            // Sanitize input
            $username = $this->sanitizeInput($username);

            // Get user by username
            $user = $this->userModel->getByUsername($username);

            // Verify user exists and password is correct
            if (!$user || !password_verify($password, $user['password'])) {
                // Log failed login attempt
                $this->log('login_failed', [
                    'username' => $username,
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT']
                ]);

                $_SESSION['error'] = 'Invalid username or password';
                redirect('/login');
                return;
            }

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];

            // Generate new CSRF token after login
            $this->generateCSRFToken();

            // Log successful login
            $this->log('login_success', [
                'username' => $user['username'],
                'ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT']
            ]);

            // Redirect to dashboard
            redirect('/');

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = 'An error occurred during login. Please try again.';
            redirect('/login');
        }
    }

    public function logout() {
        try {
            // Log logout if user was logged in
            if (isset($_SESSION['user_id'])) {
                $this->log('logout', [
                    'username' => $_SESSION['username'],
                    'ip' => $_SERVER['REMOTE_ADDR']
                ]);
            }

            // Destroy session
            session_destroy();
            
            // Redirect to login page with message
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'You have been successfully logged out.'
            ];
            redirect('/login');

        } catch (PDOException $e) {
            error_log($e->getMessage());
            redirect('/login');
        }
    }

    public function changePassword() {
        // Require login
        $this->requireLogin();

        // Validate CSRF token
        $this->validateCSRF();

        // Get POST data
        $data = $this->getPostData();
        
        // Validate input
        $errors = $this->validateRequired($data, [
            'current_password',
            'new_password',
            'confirm_password'
        ]);

        if (!empty($errors)) {
            $this->jsonResponse([
                'success' => false,
                'errors' => $errors
            ], 400);
        }

        // Verify new password matches confirmation
        if ($data['new_password'] !== $data['confirm_password']) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'New password and confirmation do not match'
            ], 400);
        }

        // Validate password strength
        if (!$this->userModel->validatePassword($data['new_password'])) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character'
            ], 400);
        }

        // Change password
        if (!$this->userModel->changePassword(
            $_SESSION['user_id'],
            $data['current_password'],
            $data['new_password']
        )) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Current password is incorrect'
            ], 400);
        }

        // Log password change
        $this->log('password_changed', [
            'user_id' => $_SESSION['user_id'],
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);

        // Return success response
        $this->jsonResponse([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }
}
