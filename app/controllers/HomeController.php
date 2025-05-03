<?php

class HomeController extends BaseController {
    protected $requiresAuth = true;

    public function index() {
        $user = $this->getCurrentUser();
        
        return $this->render('home/index', [
            'title' => 'Dashboard',
            'user' => $user
        ]);
    }
}
