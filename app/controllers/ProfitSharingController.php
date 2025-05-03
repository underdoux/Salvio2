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
        $data = [
            'title' => 'Profit Sharing',
            'description' => 'Manage profit sharing and distributions',
            'profits' => $this->profitSharing->getMonthlyProfits()
        ];
        $this->render('profit_sharing/index', $data);
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

    public function exportProfitReport($month) {
        try {
            $data = $this->profitSharing->exportProfitReport($month);
            $filename = "profit_report_" . $month . ".csv";
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $fp = fopen('php://output', 'w');
            foreach ($data as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);
            exit;

        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function exportDistributionHistory() {
        try {
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            $data = $this->profitSharing->exportDistributionHistory($startDate, $endDate);
            $filename = "distribution_history.csv";
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $fp = fopen('php://output', 'w');
            foreach ($data as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);
            exit;

        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function exportInvestorReport($investorId) {
        try {
            $year = $_GET['year'] ?? date('Y');
            
            $data = $this->profitSharing->exportInvestorReport($investorId, $year);
            $filename = "investor_report_{$investorId}_{$year}.csv";
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $fp = fopen('php://output', 'w');
            foreach ($data as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);
            exit;

        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
