<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Product.php';

class HomeController extends Controller {
    private $orderModel;
    private $productModel;

    public function __construct() {
        parent::__construct();
        $this->orderModel = new Order();
        $this->productModel = new Product();
    }

    public function index() {
        // Require login
        $this->requireLogin();

        // Get dashboard data based on role
        $data = [];

        if ($_SESSION['role_name'] === 'admin') {
            // Get today's sales
            $today = date('Y-m-d');
            $todaySales = $this->orderModel->getDailySales($today);
            $data['today_sales'] = $todaySales['total'] ?? 0;
            $data['today_orders'] = $todaySales['count'] ?? 0;

            // Get low stock products
            $data['low_stock_count'] = $this->productModel->getLowStockCount();

            // Get pending orders
            $data['pending_orders'] = $this->orderModel->getPendingOrderCount();

            // Get monthly revenue
            $month = date('Y-m');
            $monthlyRevenue = $this->orderModel->getMonthlySales($month);
            $data['monthly_revenue'] = $monthlyRevenue['total'] ?? 0;

            // Get recent orders
            $data['recent_orders'] = $this->orderModel->getRecent(5);

            // Get low stock products
            $data['low_stock_products'] = $this->productModel->getLowStock(5);
        }

        // Show dashboard view
        $this->view('home', $data);
    }
}
