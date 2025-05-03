<?php

require_once __DIR__ . '/../models/Analytics.php';

class AnalyticsController extends BaseController {
    protected $requiresAuth = true;
    protected $analytics;

    public function __construct() {
        parent::__construct();
        $this->analytics = new Analytics();
    }

    public function index() {
        $data = [
            'title' => 'Analytics Dashboard',
            'description' => 'View sales and performance analytics'
        ];
        $this->render('analytics/index', $data);
    }

    public function exportBestSelling() {
        try {
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $limit = $_GET['limit'] ?? 100;
            
            $data = $this->analytics->exportBestSellingProducts($startDate, $endDate, $limit);
            $filename = "best_selling_products.csv";
            
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

    public function exportMarketResponse() {
        try {
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            $data = $this->analytics->exportMarketResponse($startDate, $endDate);
            $filename = "market_response_analysis.csv";
            
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

    public function exportSalesTrends() {
        try {
            $period = $_GET['period'] ?? 'monthly';
            $limit = $_GET['limit'] ?? 12;
            
            $data = $this->analytics->exportSalesTrends($period, $limit);
            $filename = "sales_trends_{$period}.csv";
            
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

    public function exportProductMetrics($productId) {
        try {
            $data = $this->analytics->exportProductMetrics($productId);
            $filename = "product_metrics_{$productId}.csv";
            
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

    public function bestSelling() {
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $limit = $_GET['limit'] ?? 10;

        $data = $this->analytics->getBestSellingProducts($startDate, $endDate, $limit);
        $this->json($data);
    }

    public function marketResponse() {
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        $data = $this->analytics->getMarketResponse($startDate, $endDate);
        $this->json($data);
    }

    public function salesTrends() {
        $period = $_GET['period'] ?? 'monthly';
        $limit = $_GET['limit'] ?? 12;

        $data = $this->analytics->getSalesTrends($period, $limit);
        $this->json($data);
    }

    public function productMetrics($productId) {
        $data = $this->analytics->getProductMetrics($productId);
        $this->json($data);
    }
}
