<?php

class BaseController {
    protected $db;
    protected $view;
    protected $requiresAuth = true;

    public function __construct() {
        global $db;
        $this->db = $db;

        // Check authentication if required
        if ($this->requiresAuth && !$this->isAuthenticated()) {
            $this->redirect('/Salvio2/public/auth');
        }
    }

    protected function isAuthenticated() {
        return isset($_SESSION['user']);
    }

    protected function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }

    protected function render($view, $data = []) {
        extract($data);
        ob_start();
        require_once "../app/views/{$view}.php";
        $content = ob_get_clean();
        require_once "../app/views/layouts/main.php";
    }

    protected function json($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    protected function redirect($url) {
        header("Location: {$url}");
        exit;
    }
}
