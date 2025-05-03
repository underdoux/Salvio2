<?php

class HomeController extends BaseController {
    protected $requiresAuth = true; // Reports should require authentication
    public function index() {
        if (!$this->isAuthenticated()) {
            $data = [
                'title' => 'Welcome to Salvio POS',
                'description' => 'Pharmaceutical Distribution Management System'
            ];
            $this->render('home/index', $data);
        } else {
            header('Location: /Salvio2/public/reports');
            exit;
        }
    }

    public function reports() {
        if (!$this->isAuthenticated()) {
            header('Location: /Salvio2/public/auth');
            exit;
        }

        $data = [
            'title' => 'Reports Dashboard',
            'description' => 'View sales and inventory reports'
        ];
        
        $this->render('home/reports', $data);
    }
}
