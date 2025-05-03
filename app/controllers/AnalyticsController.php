<?php

class AnalyticsController extends BaseController {
    protected $requiresAuth = true;
    private $analyticsModel;

    public function __construct() {
        parent::__construct();
        $this->analyticsModel = new Analytics();
    }

    /**
     * Display analytics dashboard
     */
    public function index() {
        try {
            $filters = [
                'year' => $_GET['year'] ?? date('Y'),
                'month' => $_GET['month'] ?? date('n')
            ];

            // Get sales trends
            $salesTrends = $this->analyticsModel->getSalesTrends($filters);

            // Get market response data
            $marketResponse = $this->analyticsModel->getMarketResponse($filters);

            // Get performance metrics
            $performanceMetrics = $this->analyticsModel->getPerformanceMetrics($filters);

            // Get predictive analytics
            $predictiveAnalytics = $this->analyticsModel->getPredictiveAnalytics([
                'date_from' => date('Y-m-d'),
                'date_to' => date('Y-m-d', strtotime('+3 months'))
            ]);

            return $this->render('analytics/index', [
                'title' => 'Analytics Dashboard',
                'salesTrends' => $salesTrends,
                'marketResponse' => $marketResponse,
                'performanceMetrics' => $performanceMetrics,
                'predictiveAnalytics' => $predictiveAnalytics,
                'filters' => $filters
            ]);
        } catch (Exception $e) {
            Logger::log("Error in analytics dashboard: " . $e->getMessage(), 'ERROR');
            return $this->render('analytics/index', [
                'title' => 'Analytics Dashboard',
                'error' => 'An error occurred while loading analytics data.'
            ]);
        }
    }

    /**
     * Display sales trends
     */
    public function trends() {
        try {
            $filters = [
                'year' => $_GET['year'] ?? date('Y'),
                'month' => $_GET['month'] ?? null,
                'product_id' => $_GET['product_id'] ?? null,
                'category_id' => $_GET['category_id'] ?? null
            ];

            $trends = $this->analyticsModel->getSalesTrends($filters);

            if ($this->isAjax()) {
                return $this->json(['success' => true, 'data' => $trends]);
            }

            return $this->render('analytics/trends', [
                'title' => 'Sales Trends',
                'trends' => $trends,
                'filters' => $filters
            ]);
        } catch (Exception $e) {
            Logger::log("Error viewing sales trends: " . $e->getMessage(), 'ERROR');
            if ($this->isAjax()) {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            return $this->render('analytics/trends', [
                'title' => 'Sales Trends',
                'error' => 'An error occurred while loading trend data.'
            ]);
        }
    }

    /**
     * Display market response analysis
     */
    public function marketResponse() {
        try {
            $filters = [
                'year' => $_GET['year'] ?? date('Y'),
                'month' => $_GET['month'] ?? null,
                'product_id' => $_GET['product_id'] ?? null,
                'customer_type' => $_GET['customer_type'] ?? null
            ];

            $response = $this->analyticsModel->getMarketResponse($filters);

            if ($this->isAjax()) {
                return $this->json(['success' => true, 'data' => $response]);
            }

            return $this->render('analytics/market-response', [
                'title' => 'Market Response Analysis',
                'response' => $response,
                'filters' => $filters
            ]);
        } catch (Exception $e) {
            Logger::log("Error viewing market response: " . $e->getMessage(), 'ERROR');
            if ($this->isAjax()) {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            return $this->render('analytics/market-response', [
                'title' => 'Market Response Analysis',
                'error' => 'An error occurred while loading response data.'
            ]);
        }
    }

    /**
     * Display performance metrics
     */
    public function performance() {
        try {
            $filters = [
                'year' => $_GET['year'] ?? date('Y'),
                'month' => $_GET['month'] ?? null,
                'metric_type' => $_GET['metric_type'] ?? null
            ];

            $metrics = $this->analyticsModel->getPerformanceMetrics($filters);

            if ($this->isAjax()) {
                return $this->json(['success' => true, 'data' => $metrics]);
            }

            return $this->render('analytics/performance', [
                'title' => 'Performance Metrics',
                'metrics' => $metrics,
                'filters' => $filters
            ]);
        } catch (Exception $e) {
            Logger::log("Error viewing performance metrics: " . $e->getMessage(), 'ERROR');
            if ($this->isAjax()) {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            return $this->render('analytics/performance', [
                'title' => 'Performance Metrics',
                'error' => 'An error occurred while loading metric data.'
            ]);
        }
    }

    /**
     * Display predictive analytics
     */
    public function predictions() {
        try {
            $filters = [
                'product_id' => $_GET['product_id'] ?? null,
                'date_from' => $_GET['date_from'] ?? date('Y-m-d'),
                'date_to' => $_GET['date_to'] ?? date('Y-m-d', strtotime('+3 months'))
            ];

            $predictions = $this->analyticsModel->getPredictiveAnalytics($filters);

            if ($this->isAjax()) {
                return $this->json(['success' => true, 'data' => $predictions]);
            }

            return $this->render('analytics/predictions', [
                'title' => 'Predictive Analytics',
                'predictions' => $predictions,
                'filters' => $filters
            ]);
        } catch (Exception $e) {
            Logger::log("Error viewing predictions: " . $e->getMessage(), 'ERROR');
            if ($this->isAjax()) {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            return $this->render('analytics/predictions', [
                'title' => 'Predictive Analytics',
                'error' => 'An error occurred while loading prediction data.'
            ]);
        }
    }

    /**
     * Calculate analytics data
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

            // Calculate all analytics data
            $this->analyticsModel->calculateSalesTrends($year, $month);
            $this->analyticsModel->calculateMarketResponse($year, $month);
            $this->analyticsModel->calculatePerformanceMetrics($year, $month);
            $this->analyticsModel->generatePredictiveAnalytics();

            if ($this->isAjax()) {
                return $this->json(['success' => true]);
            }

            $this->redirect('/analytics');
        } catch (Exception $e) {
            Logger::log("Error calculating analytics: " . $e->getMessage(), 'ERROR');
            if ($this->isAjax()) {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            $this->redirect('/analytics');
        }
    }

    /**
     * Export analytics data
     */
    public function export() {
        try {
            $type = $_GET['type'] ?? 'sales';
            $format = $_GET['format'] ?? 'csv';

            switch ($type) {
                case 'sales':
                    $data = $this->analyticsModel->getSalesTrends($_GET);
                    $filename = 'sales_trends';
                    break;
                case 'market':
                    $data = $this->analyticsModel->getMarketResponse($_GET);
                    $filename = 'market_response';
                    break;
                case 'performance':
                    $data = $this->analyticsModel->getPerformanceMetrics($_GET);
                    $filename = 'performance_metrics';
                    break;
                case 'predictions':
                    $data = $this->analyticsModel->getPredictiveAnalytics($_GET);
                    $filename = 'predictive_analytics';
                    break;
                default:
                    throw new Exception("Invalid export type");
            }

            if ($format === 'csv') {
                $this->exportToCsv($data, $filename);
            } else {
                throw new Exception("Invalid export format");
            }
        } catch (Exception $e) {
            Logger::log("Error exporting analytics: " . $e->getMessage(), 'ERROR');
            $this->redirect('/analytics');
        }
    }

    /**
     * Export data to CSV
     */
    private function exportToCsv($data, $filename) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

        $output = fopen('php://output', 'w');

        // Write headers
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
        }

        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
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
