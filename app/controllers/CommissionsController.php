<?php

require_once __DIR__ . '/../models/Commission.php';

class CommissionsController extends BaseController {
    protected $requiresAuth = true;
    protected $commission;

    public function __construct() {
        parent::__construct();
        $this->commission = new Commission();
    }

    protected function getStatusBadgeClass($status) {
        switch ($status) {
            case 'pending':
                return 'warning';
            case 'approved':
                return 'success';
            case 'paid':
                return 'primary';
            default:
                return 'secondary';
        }
    }

    public function index() {
        $filters = [
            'start_date' => $_GET['date_from'] ?? date('Y-m-01'),
            'end_date' => $_GET['date_to'] ?? date('Y-m-t'),
            'status' => $_GET['status'] ?? null
        ];

        $commissions = $this->commission->getAll($filters);
        
        // Group commissions by user and calculate totals
        $summary = [];
        foreach ($commissions as $commission) {
            $userId = $commission['user_id'];
            if (!isset($summary[$userId])) {
                $summary[$userId] = [
                    'id' => $userId,
                    'user_id' => $userId,
                    'username' => $commission['user_name'],
                    'total_orders' => 0,
                    'total_commission' => 0,
                    'status' => $commission['status']
                ];
            }
            $summary[$userId]['total_orders']++;
            $summary[$userId]['total_commission'] += $commission['amount'];
        }

        $data = [
            'title' => 'Commissions',
            'description' => 'View and manage commission records',
            'summary' => array_values($summary),
            'filters' => $filters,
            'metrics' => $this->commission->getCommissionPerformanceMetrics(null, 30) // Last 30 days
        ];
        
        $this->render('commissions/index', $data);
    }

    public function reports() {
        $filters = [
            'start_date' => $_GET['start_date'] ?? date('Y-m-01'),
            'end_date' => $_GET['end_date'] ?? date('Y-m-t'),
            'status' => $_GET['status'] ?? null,
            'user_id' => $_GET['user_id'] ?? null
        ];

        $period = $_GET['period'] ?? 'monthly';
        $year = $_GET['year'] ?? date('Y');

        $data = [
            'title' => 'Commission Reports',
            'description' => 'View detailed commission reports and analytics',
            'filters' => $filters,
            'commissions' => $this->commission->getCommissionReport($filters),
            'summary' => $this->commission->getCommissionSummaryByPeriod($period, $year),
            'trends' => $this->commission->getCommissionTrendsByProduct($filters['start_date'], $filters['end_date']),
            'metrics' => $this->commission->getCommissionPerformanceMetrics(null, 30) // Last 30 days
        ];

        $this->render('commissions/reports', $data);
    }

    public function exportReport() {
        $filters = [
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
            'status' => $_GET['status'] ?? null,
            'user_id' => $_GET['user_id'] ?? null
        ];

        $data = $this->commission->exportCommissionReport($filters);
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="commission_report_' . date('Y-m-d') . '.csv"');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add headers
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
        }
        
        // Add data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }

    public function performanceMetrics($userId = null) {
        $period = $_GET['period'] ?? 30; // Default to last 30 days
        $metrics = $this->commission->getCommissionPerformanceMetrics($userId, $period);
        
        $this->json([
            'success' => true,
            'metrics' => $metrics
        ]);
    }

    public function productTrends() {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        
        $trends = $this->commission->getCommissionTrendsByProduct($startDate, $endDate);
        
        $this->json([
            'success' => true,
            'trends' => $trends
        ]);
    }

    public function periodSummary() {
        $period = $_GET['period'] ?? 'monthly';
        $year = $_GET['year'] ?? date('Y');
        
        $summary = $this->commission->getCommissionSummaryByPeriod($period, $year);
        
        $this->json([
            'success' => true,
            'summary' => $summary
        ]);
    }

    public function recordPayment($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $data = [
                'amount' => $_POST['amount'] ?? null,
                'payment_date' => $_POST['payment_date'] ?? date('Y-m-d'),
                'payment_method' => $_POST['payment_method'] ?? null,
                'reference_number' => $_POST['reference_number'] ?? null,
                'notes' => $_POST['notes'] ?? null
            ];

            // Validate required fields
            if (!$data['amount'] || !$data['payment_method']) {
                throw new Exception('Amount and payment method are required');
            }

            $paymentId = $this->commission->recordPayment($id, $data);
            Logger::log("Commission payment recorded for commission #{$id}");

            $this->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'payment_id' => $paymentId
            ]);

        } catch (Exception $e) {
            Logger::log("Error recording commission payment: " . $e->getMessage(), 'ERROR');
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function voidPayment($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $reason = $_POST['reason'] ?? null;
            if (!$reason) {
                throw new Exception('Void reason is required');
            }

            $this->commission->voidPayment($id, $reason);
            Logger::log("Commission payment #{$id} voided");

            $this->json([
                'success' => true,
                'message' => 'Payment voided successfully'
            ]);

        } catch (Exception $e) {
            Logger::log("Error voiding commission payment: " . $e->getMessage(), 'ERROR');
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
