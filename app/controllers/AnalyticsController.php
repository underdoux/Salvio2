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
            'description' => 'Insightful analytics on sales and market response',
            'best_selling' => $this->analytics->getBestSellingProducts(5),
            'market_response' => $this->analytics->getMarketResponseByCustomerType(),
            'sales_trends' => $this->analytics->getSalesTrends(6)
        ];
        $this->render('analytics/index', $data);
    }

    public function bestSelling() {
        $products = $this->analytics->getBestSellingProducts();
        $categories = $this->analytics->getBestSellingCategories();

        $this->json([
            'best_selling_products' => $products,
            'best_selling_categories' => $categories
        ]);
    }

    public function leastPerforming() {
        $products = $this->analytics->getLeastPerformingProducts();
        $this->json(['least_performing_products' => $products]);
    }

    public function marketResponse() {
        $response = $this->analytics->getMarketResponseByCustomerType();
        $this->json(['market_response' => $response]);
    }

    public function salesTrends() {
        $trends = $this->analytics->getSalesTrends();
        $this->json(['sales_trends' => $trends]);
    }

    public function productMetrics($id) {
        $metrics = $this->analytics->getProductPerformanceMetrics($id);
        $this->json(['product_metrics' => $metrics]);
    }

    public function categoryMetrics($id) {
        $metrics = $this->analytics->getCategoryPerformanceMetrics($id);
        $this->json(['category_metrics' => $metrics]);
    }
}
