<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/AuthService.php';

class UserController {
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            $userModel = new User();
            $user = $userModel->verifyPassword($username, $password);

            if ($user) {
                AuthService::login($user['id'], $user['username'], $user['role_id']);
                header("Location: /home");
                exit();
            } else {
                $error = "Invalid username or password";
                require __DIR__ . '/../views/user/login.php';
            }
        } else {
            require __DIR__ . '/../views/user/login.php';
        }
    }

    public function logout() {
        AuthService::logout();
        header("Location: /login");
        exit();
    }

    // Check if current user has one of the allowed roles
    public function authorize(array $allowedRoles) {
        AuthService::checkAccess($allowedRoles);
    }
}
?>
