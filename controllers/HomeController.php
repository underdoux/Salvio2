<?php
require_once __DIR__ . '/Controller.php';

class HomeController extends Controller {
    public function index() {
        if (!auth()) {
            return $this->redirect('login');
        }

        // Get counts for dashboard
        $counts = [
            'products' => $this->db->count("SELECT COUNT(*) FROM products"),
            'orders' => $this->db->count("SELECT COUNT(*) FROM orders"),
            'pending_orders' => $this->db->count("SELECT COUNT(*) FROM orders WHERE status != 'completed'"),
            'low_stock' => $this->db->count("SELECT COUNT(*) FROM products WHERE stock <= 10 AND by_order = 0")
        ];

        // Get recent orders
        $recent_orders = $this->db->fetchAll(
            "SELECT o.*, u.username 
             FROM orders o 
             JOIN users u ON o.user_id = u.id 
             ORDER BY o.created_at DESC 
             LIMIT 5"
        );

        // Get low stock products
        $low_stock_products = $this->db->fetchAll(
            "SELECT * FROM products 
             WHERE stock <= 10 
             AND by_order = 0 
             ORDER BY stock ASC 
             LIMIT 5"
        );

        // Get today's sales
        $today_sales = $this->db->fetchOne(
            "SELECT COALESCE(SUM(total), 0) as total 
             FROM orders 
             WHERE DATE(created_at) = CURDATE()"
        );

        return $this->view('home', [
            'title' => 'Dashboard',
            'counts' => $counts,
            'recent_orders' => $recent_orders,
            'low_stock_products' => $low_stock_products,
            'today_sales' => $today_sales['total'] ?? 0
        ]);
    }
}
