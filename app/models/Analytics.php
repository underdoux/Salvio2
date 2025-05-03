<?php

class Analytics extends BaseModel {
    // ... (keep existing methods) ...

    public function getCustomerSegmentation() {
        $sql = "WITH CustomerMetrics AS (
                    SELECT 
                        c.id,
                        c.type,
                        COUNT(o.id) as order_count,
                        SUM(o.total_amount) as total_spent,
                        AVG(o.total_amount) as avg_order_value,
                        MAX(o.created_at) as last_order_date,
                        DATEDIFF(NOW(), MAX(o.created_at)) as days_since_last_order,
                        COUNT(DISTINCT DATE(o.created_at)) as unique_order_days
                    FROM customers c
                    LEFT JOIN orders o ON c.id = o.customer_id
                    GROUP BY c.id, c.type
                )
                SELECT 
                    CASE 
                        WHEN total_spent > 10000 AND order_count > 20 THEN 'VIP'
                        WHEN total_spent > 5000 AND order_count > 10 THEN 'High Value'
                        WHEN total_spent > 1000 AND order_count > 5 THEN 'Regular'
                        WHEN days_since_last_order > 180 THEN 'At Risk'
                        ELSE 'New'
                    END as segment,
                    COUNT(*) as customer_count,
                    AVG(total_spent) as avg_total_spent,
                    AVG(order_count) as avg_order_count,
                    AVG(avg_order_value) as avg_basket_size
                FROM CustomerMetrics
                GROUP BY 
                    CASE 
                        WHEN total_spent > 10000 AND order_count > 20 THEN 'VIP'
                        WHEN total_spent > 5000 AND order_count > 10 THEN 'High Value'
                        WHEN total_spent > 1000 AND order_count > 5 THEN 'Regular'
                        WHEN days_since_last_order > 180 THEN 'At Risk'
                        ELSE 'New'
                    END
                ORDER BY avg_total_spent DESC";

        return $this->db->query($sql)->fetchAll();
    }

    public function getProductAffinityAnalysis() {
        $sql = "WITH OrderPairs AS (
                    SELECT 
                        o1.order_id,
                        oi1.product_id as product1_id,
                        oi2.product_id as product2_id,
                        p1.name as product1_name,
                        p2.name as product2_name,
                        COUNT(*) as pair_count
                    FROM order_items oi1
                    JOIN order_items oi2 ON oi1.order_id = oi2.order_id AND oi1.product_id < oi2.product_id
                    JOIN orders o1 ON oi1.order_id = o1.id
                    JOIN products p1 ON oi1.product_id = p1.id
                    JOIN products p2 ON oi2.product_id = p2.id
                    GROUP BY o1.order_id, oi1.product_id, oi2.product_id, p1.name, p2.name
                )
                SELECT 
                    product1_name,
                    product2_name,
                    COUNT(*) as frequency,
                    AVG(pair_count) as avg_quantity
                FROM OrderPairs
                GROUP BY product1_id, product2_id, product1_name, product2_name
                HAVING COUNT(*) > 5
                ORDER BY frequency DESC
                LIMIT 100";

        return $this->db->query($sql)->fetchAll();
    }

    public function getSeasonalityAnalysis($year = null) {
        $year = $year ?? date('Y');
        
        $sql = "WITH DailySales AS (
                    SELECT 
                        DATE(o.created_at) as sale_date,
                        DAYOFWEEK(o.created_at) as day_of_week,
                        MONTH(o.created_at) as month,
                        COUNT(o.id) as order_count,
                        SUM(o.total_amount) as total_sales,
                        COUNT(DISTINCT o.customer_id) as unique_customers
                    FROM orders o
                    WHERE YEAR(o.created_at) = ?
                    GROUP BY DATE(o.created_at)
                )
                SELECT 
                    month,
                    day_of_week,
                    AVG(order_count) as avg_daily_orders,
                    AVG(total_sales) as avg_daily_sales,
                    AVG(unique_customers) as avg_daily_customers,
                    COUNT(*) as days_with_sales,
                    MAX(total_sales) as peak_daily_sales,
                    MIN(total_sales) as lowest_daily_sales
                FROM DailySales
                GROUP BY month, day_of_week
                ORDER BY month, day_of_week";

        return $this->db->query($sql, [$year])->fetchAll();
    }

    public function getPredictiveAnalysis($productId) {
        $sql = "WITH MonthlySales AS (
                    SELECT 
                        DATE_FORMAT(o.created_at, '%Y-%m') as month,
                        SUM(oi.quantity) as quantity,
                        AVG(oi.price) as avg_price,
                        COUNT(DISTINCT o.id) as order_count
                    FROM orders o
                    JOIN order_items oi ON o.id = oi.order_id
                    WHERE oi.product_id = ?
                    GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
                    ORDER BY month DESC
                    LIMIT 12
                )
                SELECT 
                    month,
                    quantity,
                    avg_price,
                    order_count,
                    AVG(quantity) OVER (ORDER BY month ROWS BETWEEN 2 PRECEDING AND CURRENT ROW) as moving_avg_quantity,
                    AVG(quantity) OVER (ORDER BY month ROWS BETWEEN 1 PRECEDING AND 1 FOLLOWING) as centered_avg_quantity,
                    LAG(quantity) OVER (ORDER BY month) as prev_month_quantity,
                    LEAD(quantity) OVER (ORDER BY month) as next_month_quantity,
                    quantity - LAG(quantity) OVER (ORDER BY month) as month_over_month_change
                FROM MonthlySales
                ORDER BY month DESC";

        return $this->db->query($sql, [$productId])->fetchAll();
    }

    public function getInventoryAnalysis() {
        $sql = "WITH ProductMetrics AS (
                    SELECT 
                        p.id,
                        p.name,
                        p.sku,
                        p.stock_quantity,
                        COUNT(oi.id) as times_ordered,
                        SUM(oi.quantity) as total_quantity_sold,
                        AVG(oi.quantity) as avg_order_quantity,
                        MAX(o.created_at) as last_ordered_date
                    FROM products p
                    LEFT JOIN order_items oi ON p.id = oi.product_id
                    LEFT JOIN orders o ON oi.order_id = o.id
                    GROUP BY p.id, p.name, p.sku, p.stock_quantity
                )
                SELECT 
                    id,
                    name,
                    sku,
                    stock_quantity,
                    times_ordered,
                    total_quantity_sold,
                    avg_order_quantity,
                    last_ordered_date,
                    CASE 
                        WHEN stock_quantity = 0 THEN 'Out of Stock'
                        WHEN stock_quantity < avg_order_quantity THEN 'Critical'
                        WHEN stock_quantity < avg_order_quantity * 3 THEN 'Low'
                        WHEN stock_quantity > avg_order_quantity * 10 THEN 'Overstocked'
                        ELSE 'Healthy'
                    END as stock_status,
                    CASE 
                        WHEN times_ordered = 0 THEN 'Dead Stock'
                        WHEN DATEDIFF(NOW(), last_ordered_date) > 90 THEN 'Slow Moving'
                        WHEN times_ordered > 100 THEN 'Fast Moving'
                        ELSE 'Regular'
                    END as inventory_velocity
                FROM ProductMetrics
                ORDER BY times_ordered DESC";

        return $this->db->query($sql)->fetchAll();
    }

    public function getCohortAnalysis($months = 12) {
        $sql = "WITH FirstPurchase AS (
                    SELECT 
                        customer_id,
                        DATE_FORMAT(MIN(created_at), '%Y-%m') as cohort_month
                    FROM orders
                    GROUP BY customer_id
                ),
                Purchases AS (
                    SELECT 
                        fp.customer_id,
                        fp.cohort_month,
                        DATE_FORMAT(o.created_at, '%Y-%m') as purchase_month,
                        PERIOD_DIFF(
                            DATE_FORMAT(o.created_at, '%Y%m'),
                            DATE_FORMAT(fp.cohort_month, '%Y%m')
                        ) as month_number,
                        COUNT(DISTINCT o.id) as purchase_count,
                        SUM(o.total_amount) as total_amount
                    FROM FirstPurchase fp
                    JOIN orders o ON fp.customer_id = o.customer_id
                    GROUP BY 
                        fp.customer_id,
                        fp.cohort_month,
                        DATE_FORMAT(o.created_at, '%Y-%m'),
                        month_number
                )
                SELECT 
                    cohort_month,
                    month_number,
                    COUNT(DISTINCT customer_id) as customer_count,
                    SUM(purchase_count) as total_purchases,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as avg_revenue_per_customer
                FROM Purchases
                WHERE cohort_month >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL ? MONTH), '%Y-%m')
                GROUP BY cohort_month, month_number
                ORDER BY cohort_month, month_number";

        return $this->db->query($sql, [$months])->fetchAll();
    }
}
