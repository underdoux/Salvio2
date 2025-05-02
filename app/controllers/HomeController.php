<?php

class HomeController extends BaseController {
    public function index() {
        $data = [
            'title' => 'Welcome to Salvio POS',
            'description' => 'Pharmaceutical Distribution Management System'
        ];
        
        $this->render('home/index', $data);
    }
}
