<?php

class Analytics extends BaseModel {
    public function getBestSellingProducts($limit = 10) {
        $sql = "SELECT 
                    p.id, 
                    p.name, 
                    p.category_id,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.quantity * oi.unit_price) as total_revenue
                FROM products p
                JOIN order_items oi ON p.id = oi.product_id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status = 'completed'
                GROUP BY p.id, p.name, p.category_id
                ORDER BY total_quantity DESC
                LIMIT ?";
        
        return $this->db->query($sql, [$limit])->fetchAll();
    }

    public function getBestSellingCategories($limit = 10) {
        $sql = "SELECT 
                    c.id,
                    c.name,
                    COUNT(DISTINCT p.id) as product_count,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.quantity * oi.unit_price) as total_revenue
                FROM categories c
                JOIN products p ON c.id = p.category_id
                JOIN order_items oi ON p.id = oi.product_id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status = 'completed'
                GROUP BY c.id, c.name
                ORDER BY total_quantity DESC
                LIMIT ?";
        
        return $this->db->query($sql, [$limit])->fetchAll();
    }

    public function getLeastPerformingProducts($limit = 10) {
        $sql = "SELECT 
                    p.id,
                    p.name,
                    p.category_id,
                    COALESCE(SUM(oi.quantity), 0) as total_quantity,
                    COALESCE(SUM(oi.quantity * oi.unit_price), 0) as total_revenue
                FROM products p
                LEFT JOIN order_items oi ON p.id = oi.product_id
                LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
                GROUP BY p.id, p.name, p.category_id
                ORDER BY total_quantity ASC
                LIMIT ?";
        
        return $this->db->query($sql, [$limit])->fetchAll();
    }

    public function getMarketResponseByCustomerType() {
        $sql = "SELECT 
                    c.type as customer_type,
                    COUNT(DISTINCT o.id) as total_orders,
                    COUNT(DISTINCT c.id) as customer_count,
                    SUM(o.total_amount) as total_revenue,
                    AVG(o.total_amount) as average_order_value
                FROM customers c
                JOIN orders o ON c.id = o.customer_id
                WHERE o.status = 'completed'
                GROUP BY c.type
                ORDER BY total_revenue DESC";
        
        return $this->db->query($sql)->fetchAll();
    }

    public function getSalesTrends($months = 12) {
        $sql = "SELECT 
                    DATE_FORMAT(o.order_date, '%Y-%m') as month,
                    COUNT(DISTINCT o.id) as total_orders,
                    COUNT(DISTINCT o.customer_id) as unique_customers,
                    SUM(o.total_amount) as total_revenue,
                    AVG(o.total_amount) as average_order_value
                FROM orders o
                WHERE o.status = 'completed'
                    AND o.order_date >= DATE_SUB(CURRENT_DATE, INTERVAL ? MONTH)
                GROUP BY month
                ORDER BY month ASC";
        
        return $this->db->query($sql, [$months])->fetchAll();
    }

    public function getProductPerformanceMetrics($productId) {
        $sql = "SELECT 
                    p.id,
                    p.name,
                    COUNT(DISTINCT o.id) as total_orders,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.quantity * oi.unit_price) as total_revenue,
                    AVG(oi.quantity) as average_quantity_per_order
                FROM products p
                LEFT JOIN order_items oi ON p.id = oi.product_id
                LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
                WHERE p.id = ?
                GROUP BY p.id, p.name";
        
        return $this->db->query($sql, [$productId])->fetch();
    }

    public function getCategoryPerformanceMetrics($categoryId) {
        $sql = "SELECT 
                    c.id,
                    c.name,
                    COUNT(DISTINCT p.id) as total_products,
                    COUNT(DISTINCT o.id) as total_orders,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.quantity * oi.unit_price) as total_revenue
                FROM categories c
                LEFT JOIN products p ON c.id = p.category_id
                LEFT JOIN order_items oi ON p.id = oi.product_id
                LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
                WHERE c.id = ?
                GROUP BY c.id, c.name";
        
        return $this->db->query($sql, [$categoryId])->fetch();
    }
}
