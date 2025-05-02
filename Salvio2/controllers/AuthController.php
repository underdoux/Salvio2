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
        if (auth()) {
            return $this->redirect('');
        }

        // If not a POST request, show login form
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->view('user/login');
        }

        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            return $this->redirect('login', ['error' => 'Invalid request']);
        }

        // Validate input
        if (!$this->validate($_POST, [
            'username' => 'required',
            'password' => 'required'
        ])) {
            return $this->redirect('login');
        }

        // Attempt login
        $user = $this->userModel->findByUsername($_POST['username']);
        
        if (!$user || !password_verify($_POST['password'], $user['password'])) {
            return $this->redirect('login', ['error' => 'Invalid username or password']);
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        
        // Log activity
        $this->logActivity('login', [
            'username' => $user['username'],
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);

        // Redirect to home
        return $this->redirect('');
    }

    public function logout() {
        if (auth()) {
            // Log activity before destroying session
            $this->logActivity('logout', [
                'username' => user()['username'],
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
        }

        // Destroy session
        session_destroy();
        
        // Redirect to login
        return $this->redirect('login', [
            'flash' => [
                'type' => 'success',
                'message' => 'You have been logged out successfully'
            ]
        ]);
    }

    public function changePassword() {
        // Require authentication
        if (!auth()) {
            return $this->redirect('login');
        }

        // If not a POST request, show change password form
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->view('user/change_password');
        }

        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            return $this->redirect('change-password', ['error' => 'Invalid request']);
        }

        // Validate input
        if (!$this->validate($_POST, [
            'current_password' => 'required',
            'new_password' => 'required|min:6',
            'confirm_password' => 'required'
        ])) {
            return $this->redirect('change-password');
        }

        // Check if current password is correct
        if (!password_verify($_POST['current_password'], user()['password'])) {
            return $this->redirect('change-password', ['error' => 'Current password is incorrect']);
        }

        // Check if new passwords match
        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            return $this->redirect('change-password', ['error' => 'New passwords do not match']);
        }

        // Update password
        $success = $this->userModel->updatePassword(
            user()['id'], 
            password_hash($_POST['new_password'], PASSWORD_DEFAULT)
        );

        if (!$success) {
            return $this->redirect('change-password', ['error' => 'Failed to update password']);
        }

        // Log activity
        $this->logActivity('password_changed', [
            'username' => user()['username'],
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);

        // Redirect with success message
        return $this->redirect('change-password', [
            'flash' => [
                'type' => 'success',
                'message' => 'Password changed successfully'
            ]
        ]);
    }
}
