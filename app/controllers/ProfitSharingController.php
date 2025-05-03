<?php

require_once __DIR__ . '/../models/ProfitSharing.php';

class ProfitSharingController extends BaseController {
    protected $requiresAuth = true;
    protected $profitSharing;

    public function __construct() {
        parent::__construct();
        $this->profitSharing = new ProfitSharing();
    }

    public function index() {
        $filters = [
            'status' => $_GET['status'] ?? null,
            'year' => $_GET['year'] ?? date('Y')
        ];

        $data = [
            'title' => 'Profit Sharing',
            'description' => 'Manage profit sharing and distributions',
            'profits' => $this->profitSharing->getMonthlyProfits($filters)
        ];
        
        $this->render('profit_sharing/index', $data);
    }

    public function calculate() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $month = $_POST['month'] ?? null;
            if (!$month) {
                throw new Exception('Month is required');
            }

            // Calculate profits for the month
            $profitId = $this->profitSharing->calculateMonthlyProfit($month);

            $_SESSION['success'] = 'Monthly profit calculated successfully';
            header('Location: /Salvio2/public/profit-sharing');
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /Salvio2/public/profit-sharing');
            exit;
        }
    }

    public function finalize() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $profitId = $_POST['profit_id'] ?? null;
            if (!$profitId) {
                throw new Exception('Profit ID is required');
            }

            // Finalize the profit record
            $this->profitSharing->finalizeProfitRecord($profitId);

            $_SESSION['success'] = 'Monthly profit finalized successfully';
            header('Location: /Salvio2/public/profit-sharing');
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /Salvio2/public/profit-sharing');
            exit;
        }
    }

    public function view($id) {
        try {
            $profit = $this->profitSharing->getProfitDetails($id);
            $distributions = $this->profitSharing->getProfitDistributions($id);

            $data = [
                'title' => 'Profit Details - ' . date('F Y', strtotime($profit['month'])),
                'description' => 'View profit details and distributions',
                'profit' => $profit,
                'distributions' => $distributions
            ];

            $this->render('profit_sharing/view', $data);

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /Salvio2/public/profit-sharing');
            exit;
        }
    }

    public function export($id) {
        try {
            $profit = $this->profitSharing->getProfitDetails($id);
            $month = date('Y-m', strtotime($profit['month']));
            
            $data = $this->profitSharing->exportProfitReport($month);
            $filename = "profit_report_{$month}.csv";
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $fp = fopen('php://output', 'w');
            foreach ($data as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /Salvio2/public/profit-sharing');
            exit;
        }
    }

    public function trends() {
        $period = $_GET['period'] ?? 'monthly';
        $year = $_GET['year'] ?? date('Y');
        $currentMonth = date('Y-m');

        // Get profit trends data
        $profitTrends = $this->profitSharing->getProfitTrends($period);
        $distributionMetrics = $this->profitSharing->getDistributionMetrics($period, $year);
        $investorPerformance = $this->profitSharing->getInvestorPerformance();
        $comparativeAnalysis = $this->profitSharing->getComparativeAnalysis($currentMonth);

        // Calculate key metrics
        $latestProfit = $profitTrends[0] ?? [];
        $metrics = [
            [
                'label' => 'Current Net Profit',
                'value' => $latestProfit['net_profit'] ?? 0,
                'type' => 'currency'
            ],
            [
                'label' => 'Total Investors',
                'value' => $latestProfit['total_investors'] ?? 0,
                'type' => 'number'
            ],
            [
                'label' => 'Average Distribution',
                'value' => $distributionMetrics[0]['avg_percentage'] ?? 0,
                'type' => 'percentage'
            ],
            [
                'label' => 'Total Distributed',
                'value' => $distributionMetrics[0]['total_distributed'] ?? 0,
                'type' => 'currency'
            ]
        ];

        $data = [
            'title' => 'Profit Sharing Trends',
            'description' => 'View profit sharing trends and analytics',
            'metrics' => $metrics,
            'profitTrends' => $profitTrends,
            'distributionMetrics' => $distributionMetrics,
            'investorPerformance' => $investorPerformance,
            'comparativeAnalysis' => $comparativeAnalysis
        ];

        $this->render('profit_sharing/trends', $data);
    }
}
