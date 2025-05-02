<?php

class CommissionsController extends BaseController {
    private $commission;

    public function __construct() {
        parent::__construct();
        $this->commission = new Commission();
    }

    public function index() {
        // Get filter parameters
        $filters = [
            'status' => $_GET['status'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? ''
        ];

        // Get commission summary
        $summary = $this->commission->getSummary($filters);

        $this->render('commissions/index', [
            'title' => 'Sales Commissions',
            'summary' => $summary,
            'filters' => $filters
        ]);
    }

    public function rates() {
        // Get categories and products for dropdowns
        $categories = (new Category())->all();
        $products = (new Product())->all();
        
        // Get current commission rates
        $rates = $this->commission->getRates();

        $this->render('commissions/rates', [
            'title' => 'Commission Rates',
            'rates' => $rates,
            'categories' => $categories,
            'products' => $products
        ]);
    }

    public function details($userId = null) {
        if (!$userId) {
            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => 'User ID is required'
            ];
            header('Location: /Salvio2/public/commissions');
            exit;
        }

        // Get user details
        $user = (new User())->find($userId);
        if (!$user) {
            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => 'User not found'
            ];
            header('Location: /Salvio2/public/commissions');
            exit;
        }

        // Get commission details
        $commissions = $this->commission->getDetailsByUser($userId);

        $this->render('commissions/details', [
            'title' => 'Commission Details',
            'username' => $user['username'],
            'commissions' => $commissions
        ]);
    }

    public function updateRate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request method'], 405);
            return;
        }

        $data = [
            'type' => $_POST['type'] ?? null,
            'reference_id' => $_POST['reference_id'] ?? null,
            'rate' => $_POST['rate'] ?? null
        ];

        if (!$data['type'] || !$data['rate']) {
            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => 'Type and rate are required'
            ];
            header('Location: /Salvio2/public/commissions/rates');
            exit;
        }

        // Update rate
        $success = $this->commission->updateRate($data);

        $_SESSION['flash'] = [
            'type' => $success ? 'success' : 'danger',
            'message' => $success ? 'Commission rate updated successfully' : 'Failed to update commission rate'
        ];

        header('Location: /Salvio2/public/commissions/rates');
        exit;
    }

    public function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request method'], 405);
            return;
        }

        $data = [
            'commission_id' => $_POST['commission_id'] ?? null,
            'status' => $_POST['status'] ?? null
        ];

        if (!$data['commission_id'] || !$data['status']) {
            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => 'Commission ID and status are required'
            ];
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        // Update status
        $success = $this->commission->updateStatus($data['commission_id'], $data['status']);

        $_SESSION['flash'] = [
            'type' => $success ? 'success' : 'danger',
            'message' => $success ? 'Commission status updated successfully' : 'Failed to update commission status'
        ];

        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    protected function getStatusBadgeClass($status) {
        return match($status) {
            'pending' => 'warning',
            'approved' => 'info',
            'paid' => 'success',
            default => 'secondary'
        };
    }
}
