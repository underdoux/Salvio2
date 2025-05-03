<?php

class CommissionsController extends BaseController {
    protected $requiresAuth = true;
    private $commissionModel;
    private $productModel;

    public function __construct() {
        parent::__construct();
        $this->commissionModel = new Commission();
        $this->productModel = new Product();
    }

    public function index() {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 10;

            $filters = [
                'user_id' => $_GET['user_id'] ?? null,
                'status' => $_GET['status'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null
            ];

            $commissions = $this->commissionModel->getAll($filters, $page, $limit);

            return $this->render('commissions/index', [
                'title' => 'Commission Management',
                'commissions' => $commissions,
                'filters' => $filters,
                'currentPage' => $page
            ]);
        } catch (Exception $e) {
            Logger::log("Error in commissions index: " . $e->getMessage(), 'ERROR');
            return $this->render('commissions/index', [
                'title' => 'Commission Management',
                'error' => 'An error occurred while loading commissions.'
            ]);
        }
    }

    public function view($id) {
        try {
            $commission = $this->commissionModel->getById($id);
            if (!$commission) {
                throw new Exception("Commission not found");
            }

            return $this->render('commissions/view', [
                'title' => "Commission Details",
                'commission' => $commission
            ]);
        } catch (Exception $e) {
            Logger::log("Error viewing commission {$id}: " . $e->getMessage(), 'ERROR');
            $this->redirect('/commissions');
        }
    }

    public function rates() {
        try {
            if ($this->isPost()) {
                $data = [
                    'id' => $this->getPost('id'),
                    'type' => $this->getPost('type'),
                    'reference_id' => $this->getPost('reference_id'),
                    'rate_percent' => $this->getPost('rate_percent'),
                    'min_amount' => $this->getPost('min_amount'),
                    'max_amount' => $this->getPost('max_amount'),
                    'effective_from' => $this->getPost('effective_from'),
                    'effective_to' => $this->getPost('effective_to')
                ];

                $this->commissionModel->saveRate($data);
                
                if ($this->isAjax()) {
                    return $this->json(['success' => true]);
                }
                
                $this->redirect('/commissions/rates');
            }

            $rates = $this->commissionModel->getRates();
            $products = $this->productModel->getAll();

            return $this->render('commissions/rates', [
                'title' => 'Commission Rates',
                'rates' => $rates,
                'products' => $products
            ]);
        } catch (Exception $e) {
            Logger::log("Error managing commission rates: " . $e->getMessage(), 'ERROR');
            if ($this->isAjax()) {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            return $this->render('commissions/rates', [
                'title' => 'Commission Rates',
                'error' => 'An error occurred while managing commission rates.'
            ]);
        }
    }

    public function updateStatus($id) {
        try {
            if (!$this->isPost()) {
                throw new Exception("Invalid request method");
            }

            $status = $this->getPost('status');
            $notes = $this->getPost('notes');

            $this->commissionModel->updateStatus($id, $status, $notes);
            
            if ($this->isAjax()) {
                return $this->json(['success' => true]);
            }
            
            $this->redirect('/commissions/view/' . $id);
        } catch (Exception $e) {
            Logger::log("Error updating commission status {$id}: " . $e->getMessage(), 'ERROR');
            if ($this->isAjax()) {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            $this->redirect('/commissions/view/' . $id);
        }
    }

    public function addAdjustment($id) {
        try {
            if (!$this->isPost()) {
                throw new Exception("Invalid request method");
            }

            $type = $this->getPost('type');
            $amount = $this->getPost('amount');
            $reason = $this->getPost('reason');

            $this->commissionModel->addAdjustment($id, $type, $amount, $reason, $_SESSION['user_id']);
            
            if ($this->isAjax()) {
                return $this->json(['success' => true]);
            }
            
            $this->redirect('/commissions/view/' . $id);
        } catch (Exception $e) {
            Logger::log("Error adding commission adjustment {$id}: " . $e->getMessage(), 'ERROR');
            if ($this->isAjax()) {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            $this->redirect('/commissions/view/' . $id);
        }
    }

    public function processPayment() {
        try {
            if (!$this->isPost()) {
                throw new Exception("Invalid request method");
            }

            $userId = $this->getPost('user_id');
            $commissions = json_decode($this->getPost('commissions'), true);
            
            $paymentData = [
                'amount' => $this->getPost('amount'),
                'payment_date' => $this->getPost('payment_date'),
                'payment_method' => $this->getPost('payment_method'),
                'reference_number' => $this->getPost('reference_number'),
                'notes' => $this->getPost('notes')
            ];

            $this->commissionModel->processPayment($userId, $commissions, $paymentData);
            
            if ($this->isAjax()) {
                return $this->json(['success' => true]);
            }
            
            $this->redirect('/commissions');
        } catch (Exception $e) {
            Logger::log("Error processing commission payment: " . $e->getMessage(), 'ERROR');
            if ($this->isAjax()) {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            $this->redirect('/commissions');
        }
    }

    public function report() {
        try {
            $filters = [
                'user_id' => $_GET['user_id'] ?? null,
                'status' => $_GET['status'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null
            ];

            $format = $_GET['format'] ?? 'html';
            $commissions = $this->commissionModel->getAll($filters);

            if ($format === 'csv') {
                $this->exportToCsv($commissions);
                exit;
            }

            return $this->render('commissions/report', [
                'title' => 'Commission Report',
                'commissions' => $commissions,
                'filters' => $filters
            ]);
        } catch (Exception $e) {
            Logger::log("Error generating commission report: " . $e->getMessage(), 'ERROR');
            return $this->render('commissions/report', [
                'title' => 'Commission Report',
                'error' => 'An error occurred while generating the report.'
            ]);
        }
    }

    private function exportToCsv($commissions) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="commission_report.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'Order #',
            'Sales Person',
            'Product',
            'Original Price',
            'Commission Rate',
            'Commission Amount',
            'Status',
            'Created Date'
        ]);

        foreach ($commissions as $commission) {
            fputcsv($output, [
                $commission['order_number'],
                $commission['sales_person'],
                $commission['product_name'],
                $commission['original_price'],
                $commission['rate_percent'] . '%',
                $commission['commission_amount'],
                ucfirst($commission['status']),
                date('Y-m-d', strtotime($commission['created_at']))
            ]);
        }

        fclose($output);
    }

    private function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
