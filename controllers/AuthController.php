<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/User.php';

class AuthController extends Controller {
    private $userModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
    }

    public function login() {
        // If already logged in, redirect to home
        if (isset($_SESSION['user_id'])) {
            redirect('');
        }

        // Handle POST request
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $csrf_token = $_POST['csrf_token'] ?? '';

            // Validate CSRF token
            if (!validate_csrf($csrf_token)) {
                redirect('login', 'Invalid request. Please try again.', 'error');
            }

            // Validate input
            if (!$this->validate($_POST, [
                'username' => 'required',
                'password' => 'required'
            ])) {
                redirect('login');
            }

            try {
                // Attempt login
                $user = $this->userModel->findByUsername($username);
                
                if ($user && password_verify($password, $user['password'])) {
                    // Set session data
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role_name'] = $this->userModel->getRoleName($user['role_id']);

                    // Log successful login
                    $this->logAudit('user_login', [
                        'user_id' => $user['id'],
                        'username' => $user['username'],
                        'ip_address' => $_SERVER['REMOTE_ADDR']
                    ]);

                    // Redirect to home page
                    redirect('');
                } else {
                    // Log failed login attempt
                    $this->logAudit('login_failed', [
                        'username' => $username,
                        'ip_address' => $_SERVER['REMOTE_ADDR']
                    ]);

                    redirect('login', 'Invalid username or password.', 'error');
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
                redirect('login', 'An error occurred. Please try again.', 'error');
            }
        }

        // Show login form
        return $this->view('user/login');
    }

    public function logout() {
        if (isset($_SESSION['user_id'])) {
            // Log logout
            $this->logAudit('user_logout', [
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);

            // Clear session
            session_destroy();
        }

        redirect('login', 'You have been logged out.', 'success');
    }

    public function changePassword() {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('403');
        }

        // Validate CSRF token
        if (!validate_csrf($_POST['csrf_token'] ?? '')) {
            if (is_ajax()) {
                json_response(['error' => 'Invalid request.'], 400);
            }
            redirect('', 'Invalid request.', 'error');
        }

        // Validate input
        if (!$this->validate($_POST, [
            'current_password' => 'required',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|matches:new_password'
        ])) {
            if (is_ajax()) {
                json_response(['error' => 'Please check your input.'], 400);
            }
            redirect('');
        }

        try {
            $user = $this->userModel->findById($_SESSION['user_id']);
            
            // Verify current password
            if (!password_verify($_POST['current_password'], $user['password'])) {
                if (is_ajax()) {
                    json_response(['error' => 'Current password is incorrect.'], 400);
                }
                redirect('', 'Current password is incorrect.', 'error');
            }

            // Update password
            $this->userModel->updatePassword(
                $_SESSION['user_id'],
                password_hash($_POST['new_password'], PASSWORD_DEFAULT)
            );

            // Log password change
            $this->logAudit('password_changed', [
                'user_id' => $_SESSION['user_id'],
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);

            if (is_ajax()) {
                json_response([
                    'success' => true,
                    'message' => 'Password changed successfully.'
                ]);
            }

            redirect('', 'Password changed successfully.', 'success');
        } catch (Exception $e) {
            error_log($e->getMessage());
            if (is_ajax()) {
                json_response(['error' => 'An error occurred. Please try again.'], 500);
            }
            redirect('', 'An error occurred. Please try again.', 'error');
        }
    }
}
