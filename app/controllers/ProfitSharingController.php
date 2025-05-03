<?php

class ProfitSharingController extends BaseController {
    protected $requiresAuth = true;
    private $profitSharingModel;

    public function __construct() {
        parent::__construct();
        $this->profitSharingModel = new ProfitSharing();
    }

    /**
     * Display profit sharing dashboard
     */
    public function index() {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 10;

            $filters = [
                'year' => $_GET['year'] ?? date('Y'),
                'month' => $_GET['month'] ?? null,
                'status' => $_GET['status'] ?? null
            ];

            $profits = $this->profitSharingModel->getAllMonthlyProfits($filters, $page, $limit);

            return $this->render('profit_sharing/index', [
                'title' => 'Profit Sharing',
                'profits' => $profits,
                'filters' => $filters,
                'currentPage' => $page
            ]);
        } catch (Exception $e) {
            Logger::log("Error in profit sharing index: " . $e->getMessage(), 'ERROR');
            return $this->render('profit_sharing/index', [
                'title' => 'Profit Sharing',
                'error' => 'An error occurred while loading profit data.'
            ]);
        }
    }

    /**
     * Calculate monthly profit
     */
    public function calculate() {
        try {
            if (!$this->isPost()) {
                throw new Exception("Invalid request method");
            }

            $year = $this->getPost('year');
            $month = $this->getPost('month');

            if (!$year || !$month) {
                throw new Exception("Year and month are required");
            }

            $profitId = $this->profitSharingModel->calculateMonthlyProfit($year, $month);
            
            if ($this->isAjax()) {
                return $this->json(['success' => true, 'profit_id' => $profitId]);
            }
            
            $this->redirect('/profit-sharing/view/' . $profitId);
        } catch (Exception $e) {
            Logger::log("Error calculating profit: " . $e->getMessage(), 'ERROR');
            if ($this->isAjax()) {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            $this->redirect('/profit-sharing');
        }
    }

    /**
     * View profit details
     */
    public function view($id) {
        try {
            $profit = $this->profitSharingModel->getMonthlyProfitById($id);
            if (!$profit) {
                throw new Exception("Profit calculation not found");
            }

            $distributions = $this->profitSharingModel->getProfitDistributions($id);
            $logs = $this->profitSharingModel->getCalculationLogs($id);

            return $this->render('profit_sharing/view', [
                'title' => "Profit Details",
                'profit' => $profit,
                'distributions' => $distributions,
                'logs' => $logs
            ]);
        } catch (Exception $e) {
            Logger::log("Error viewing profit {$id}: " . $e->getMessage(), 'ERROR');
            $this->redirect('/profit-sharing');
        }
    }

    /**
     * Finalize monthly profit
     */
    public function finalize($id) {
        try {
            if (!$this->isPost()) {
                throw new Exception("Invalid request method");
            }

            $this->profitSharingModel->finalizeMonthlyProfit($id);
            
            if ($this->isAjax()) {
                return $this->json(['success' => true]);
            }
            
            $this->redirect('/profit-sharing/view/' . $id);
        } catch (Exception $e) {
            Logger::log("Error finalizing profit {$id}: " . $e->getMessage(), 'ERROR');
            if ($this->isAjax()) {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            $this->redirect('/profit-sharing/view/' . $id);
        }
    }

    /**
     * Process distribution payment
     */
    public function processPayment() {
        try {
            if (!$this->isPost()) {
                throw new Exception("Invalid request method");
            }

            $distributionId = $this->getPost('distribution_id');
            $paymentData = [
                'payment_date' => $this->getPost('payment_date')
            ];

            $this->profitSharingModel->processDistributionPayment($distributionId, $paymentData);
            
            if ($this->isAjax()) {
                return $this->json(['success' => true]);
            }
            
            $this->redirect('/profit-sharing');
        } catch (Exception $e) {
            Logger::log("Error processing distribution payment: " . $e->getMessage(), 'ERROR');
            if ($this->isAjax()) {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            $this->redirect('/profit-sharing');
        }
    }

    /**
     * Generate profit sharing report
     */
    public function report() {
        try {
            $filters = [
                'year' => $_GET['year'] ?? date('Y'),
                'month' => $_GET['month'] ?? null,
                'status' => $_GET['status'] ?? null
            ];

            $profits = $this->profitSharingModel->getAllMonthlyProfits($filters);

            $format = $_GET['format'] ?? 'html';
            if ($format === 'csv') {
                $this->exportToCsv($profits);
                exit;
            }

            return $this->render('profit_sharing/report', [
                'title' => 'Profit Sharing Report',
                'profits' => $profits,
                'filters' => $filters
            ]);
        } catch (Exception $e) {
            Logger::log("Error generating profit report: " . $e->getMessage(), 'ERROR');
            return $this->render('profit_sharing/report', [
                'title' => 'Profit Sharing Report',
                'error' => 'An error occurred while generating the report.'
            ]);
        }
    }

    /**
     * Export profit data to CSV
     */
    private function exportToCsv($profits) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="profit_sharing_report.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'Year',
            'Month',
            'Total Sales',
            'Total Costs',
            'Total Expenses',
            'Total Commissions',
            'Net Profit',
            'Status',
            'Distributions',
            'Paid Distributions'
        ]);

        foreach ($profits as $profit) {
            fputcsv($output, [
                $profit['year'],
                $profit['month'],
                $profit['total_sales'],
                $profit['total_costs'],
                $profit['total_expenses'],
                $profit['total_commissions'],
                $profit['net_profit'],
                ucfirst($profit['status']),
                $profit['distribution_count'],
                $profit['paid_count']
            ]);
        }

        fclose($output);
    }

    /**
     * Check if request is AJAX
     */
    private function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
