<?php
require_once __DIR__ . '/../config/database.php';

class InsightService {
    private $conn;
    private $cache;
    private $cacheExpiry = 3600; // 1 hour

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->cache = new InsightCache();
    }

    public function getDashboardInsights() {
        $cacheKey = 'dashboard_insights';
        $cached = $this->cache->get($cacheKey);
        if ($cached) return $cached;

        $insights = [
            'sales' => $this->getSalesInsights(),
            'products' => $this->getProductInsights(),
            'customers' => $this->getCustomerInsights(),
            'performance' => $this->getPerformanceMetrics()
        ];

        $this->cache->set($cacheKey, $insights, $this->cacheExpiry);
        return $insights;
    }

    private function getSalesInsights() {
        // Sales Trend Analysis
        $salesTrend = $this->executeQuery("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_orders,
                SUM(total) as total_revenue,
                AVG(total) as average_order_value
            FROM orders
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");

        // Peak Sales Hours
        $peakHours = $this->executeQuery("
            SELECT 
                HOUR(created_at) as hour,
                COUNT(*) as order_count,
                SUM(total) as revenue
            FROM orders
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY HOUR(created_at)
            ORDER BY order_count DESC
            LIMIT 5
        ");

        // Payment Method Distribution
        $paymentMethods = $this->executeQuery("
            SELECT 
                payment_method,
                COUNT(*) as count,
                SUM(amount) as total_amount
            FROM payments
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY payment_method
        ");

        // Sales Growth
        $growth = $this->calculateGrowth('orders', 'total');

        return [
            'trend' => $salesTrend,
            'peak_hours' => $peakHours,
            'payment_methods' => $paymentMethods,
            'growth' => $growth
        ];
    }

    private function getProductInsights() {
        // Best Selling Products
        $bestSellers = $this->executeQuery("
            SELECT 
                p.id,
                p.name,
                p.category_id,
                c.name as category_name,
                COUNT(oi.id) as order_count,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.quantity * oi.original_price) as total_revenue
            FROM products p
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY p.id
            ORDER BY total_revenue DESC
            LIMIT 10
        ");

        // Low Stock Products
        $lowStock = $this->executeQuery("
            SELECT 
                p.id,
                p.name,
                p.stock,
                p.min_stock,
                c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.stock <= p.min_stock
            ORDER BY (p.stock / p.min_stock) ASC
            LIMIT 10
        ");

        // Category Performance
        $categoryPerformance = $this->executeQuery("
            SELECT 
                c.id,
                c.name,
                COUNT(DISTINCT o.id) as order_count,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.quantity * oi.original_price) as total_revenue
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id
            WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY c.id
            ORDER BY total_revenue DESC
        ");

        // Stock Turnover
        $stockTurnover = $this->executeQuery("
            SELECT 
                p.id,
                p.name,
                p.stock,
                SUM(oi.quantity) as sold_quantity,
                (SUM(oi.quantity) / p.stock) as turnover_rate
            FROM products p
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id
            WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND p.stock > 0
            GROUP BY p.id
            ORDER BY turnover_rate DESC
            LIMIT 10
        ");

        return [
            'best_sellers' => $bestSellers,
            'low_stock' => $lowStock,
            'category_performance' => $categoryPerformance,
            'stock_turnover' => $stockTurnover
        ];
    }

    private function getCustomerInsights() {
        // Customer Segments
        $segments = $this->executeQuery("
            SELECT 
                CASE 
                    WHEN total_orders >= 10 THEN 'VIP'
                    WHEN total_orders >= 5 THEN 'Regular'
                    ELSE 'New'
                END as segment,
                COUNT(*) as customer_count,
                AVG(total_spent) as average_spent
            FROM (
                SELECT 
                    customer_id,
                    COUNT(*) as total_orders,
                    SUM(total) as total_spent
                FROM orders
                GROUP BY customer_id
            ) customer_stats
            GROUP BY segment
        ");

        // Customer Retention
        $retention = $this->executeQuery("
            SELECT 
                DATE_FORMAT(first_order, '%Y-%m') as cohort_month,
                COUNT(DISTINCT customer_id) as cohort_size,
                SUM(CASE WHEN months_since_first = 1 THEN 1 ELSE 0 END) as month_1,
                SUM(CASE WHEN months_since_first = 2 THEN 1 ELSE 0 END) as month_2,
                SUM(CASE WHEN months_since_first = 3 THEN 1 ELSE 0 END) as month_3
            FROM (
                SELECT 
                    customer_id,
                    MIN(created_at) as first_order,
                    TIMESTAMPDIFF(MONTH, MIN(created_at), created_at) as months_since_first
                FROM orders
                GROUP BY customer_id, DATE_FORMAT(created_at, '%Y-%m')
            ) customer_orders
            GROUP BY cohort_month
            ORDER BY cohort_month DESC
            LIMIT 6
        ");

        // Customer Lifetime Value
        $ltv = $this->executeQuery("
            SELECT 
                AVG(total_spent) as average_ltv,
                MAX(total_spent) as max_ltv,
                MIN(total_spent) as min_ltv
            FROM (
                SELECT 
                    customer_id,
                    COUNT(*) as total_orders,
                    SUM(total) as total_spent,
                    AVG(total) as average_order_value
                FROM orders
                GROUP BY customer_id
            ) customer_stats
        ");

        return [
            'segments' => $segments,
            'retention' => $retention,
            'lifetime_value' => $ltv
        ];
    }

    private function getPerformanceMetrics() {
        // Key Performance Indicators
        $kpis = [
            'revenue' => $this->calculateKPI('orders', 'total', 'SUM'),
            'orders' => $this->calculateKPI('orders', 'id', 'COUNT'),
            'average_order' => $this->calculateKPI('orders', 'total', 'AVG'),
            'customers' => $this->calculateKPI('orders', 'customer_id', 'COUNT DISTINCT')
        ];

        // Conversion Rates
        $conversions = $this->executeQuery("
            SELECT 
                COUNT(DISTINCT o.id) / COUNT(DISTINCT c.id) * 100 as cart_to_order,
                SUM(CASE WHEN o.status = 'completed' THEN 1 ELSE 0 END) / COUNT(DISTINCT o.id) * 100 as order_completion
            FROM carts c
            LEFT JOIN orders o ON c.customer_id = o.customer_id
                AND o.created_at >= c.created_at
                AND o.created_at <= DATE_ADD(c.created_at, INTERVAL 1 DAY)
            WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");

        // Sales Performance
        $salesPerformance = $this->executeQuery("
            SELECT 
                u.id,
                u.name,
                COUNT(DISTINCT o.id) as total_orders,
                SUM(o.total) as total_revenue,
                AVG(o.total) as average_order_value,
                COUNT(DISTINCT o.customer_id) as unique_customers
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id
            WHERE u.role_id = (SELECT id FROM roles WHERE name = 'sales')
                AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY u.id
            ORDER BY total_revenue DESC
        ");

        return [
            'kpis' => $kpis,
            'conversions' => $conversions,
            'sales_performance' => $salesPerformance
        ];
    }

    private function calculateGrowth($table, $field, $period = 30) {
        $current = $this->executeQuery("
            SELECT SUM($field) as total
            FROM $table
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
        ")[0]['total'] ?? 0;

        $previous = $this->executeQuery("
            SELECT SUM($field) as total
            FROM $table
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL " . ($period * 2) . " DAY)
                AND created_at < DATE_SUB(NOW(), INTERVAL $period DAY)
        ")[0]['total'] ?? 0;

        return $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
    }

    private function calculateKPI($table, $field, $function) {
        $current = $this->executeQuery("
            SELECT $function($field) as value
            FROM $table
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")[0]['value'] ?? 0;

        $previous = $this->executeQuery("
            SELECT $function($field) as value
            FROM $table
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")[0]['value'] ?? 0;

        return [
            'current' => $current,
            'previous' => $previous,
            'growth' => $previous > 0 ? (($current - $previous) / $previous) * 100 : 0
        ];
    }

    private function executeQuery($query) {
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

class InsightCache {
    private $cacheDir;

    public function __construct() {
        $this->cacheDir = __DIR__ . '/../storage/cache/insights';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    public function get($key) {
        $file = $this->getCacheFile($key);
        if (!file_exists($file)) {
            return null;
        }

        $data = json_decode(file_get_contents($file), true);
        if ($data['expires'] < time()) {
            unlink($file);
            return null;
        }

        return $data['data'];
    }

    public function set($key, $data, $ttl) {
        $file = $this->getCacheFile($key);
        $content = json_encode([
            'expires' => time() + $ttl,
            'data' => $data
        ]);
        file_put_contents($file, $content);
    }

    private function getCacheFile($key) {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
}
?>
