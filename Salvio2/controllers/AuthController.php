<?php
require_once __DIR__ . '/../config/database.php';

class AuthController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function login($username, $password) {
        try {
            // Prepare query
            $query = "SELECT id, username, password, role_id FROM users WHERE username = :username LIMIT 1";
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Start session and store user data
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role_id'] = $user['role_id'];
                    
                    // Redirect to dashboard
                    header('Location: /Salvio2/Salvio2/public/');
                    exit;
                }
            }
            
            // Invalid credentials
            $_SESSION['error'] = 'Invalid username or password';
            header('Location: /Salvio2/Salvio2/public/login');
            exit;
            
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Login failed. Please try again.';
            header('Location: /Salvio2/Salvio2/public/login');
            exit;
        }
    }

    public function logout() {
        // Clear all session data
        session_destroy();
        
        // Redirect to login page
        header('Location: /Salvio2/Salvio2/public/login');
        exit;
    }
}
