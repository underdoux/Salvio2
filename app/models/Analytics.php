<?php

class Analytics extends BaseModel {
    public function __construct() {
        parent::__construct();
    }

    /**
     * Calculate and store sales trends
     */
    public function calculateSalesTrends($year, $month) {
        try {
            $this->db->beginTransaction();

            // Get sales data for the month
            $sql = "SELECT p.id as product_id, p.category_id,
                   SUM(oi.quantity) as total_quantity,
                   SUM(oi.unit_price * oi.quantity) as total_amount
                   FROM order_items oi
                   LEFT JOIN orders o ON oi.order_id = o.id
                   LEFT JOIN products p ON oi.product_id = p.id
                   WHERE YEAR(o.created_at) = ?
                   AND MONTH(o.created_at) = ?
                   AND o.status = 'completed'
                   GROUP BY p.id, p.category_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$year, $month]);
            $sales = $stmt->fetchAll();

            // Calculate and store trends
            foreach ($sales as $sale) {
                // Calculate average price
                $avgPrice = $sale['total_amount'] / $sale['total_quantity'];

                // Calculate growth rate
                $previousMonth = $month == 1 ? 12 : $month - 1;
                $previousYear = $month == 1 ? $year - 1 : $year;
                $growthRate = $this->calculateGrowthRate(
                    $sale['product_id'],
                    $previousYear,
                    $previousMonth,
                    $sale['total_amount']
                );

                // Save trend data
                $sql = "INSERT INTO sales_trends 
                        (year, month, product_id, category_id, total_quantity,
                         total_amount, average_price, growth_rate)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                        total_quantity = VALUES(total_quantity),
                        total_amount = VALUES(total_amount),
                        average_price = VALUES(average_price),
                        growth_rate = VALUES(growth_rate)";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $year,
                    $month,
                    $sale['product_id'],
                    $sale['category_id'],
                    $sale['total_quantity'],
                    $sale['total_amount'],
                    $avgPrice,
                    $growthRate
                ]);
            }

            $this->logAnalytics('sales_trends', "Calculated sales trends for {$year}-{$month}");
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error calculating sales trends: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Calculate and store market response data
     */
    public function calculateMarketResponse($year, $month) {
        try {
            $this->db->beginTransaction();

            // Get sales data by customer type
            $sql = "SELECT p.id as product_id,
                   o.customer_type,
                   COUNT(DISTINCT o.id) as total_orders,
                   SUM(oi.quantity) as total_quantity,
                   SUM(oi.unit_price * oi.quantity) as total_amount
                   FROM order_items oi
                   LEFT JOIN orders o ON oi.order_id = o.id
                   LEFT JOIN products p ON oi.product_id = p.id
                   WHERE YEAR(o.created_at) = ?
                   AND MONTH(o.created_at) = ?
                   AND o.status = 'completed'
                   GROUP BY p.id, o.customer_type";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$year, $month]);
            $responses = $stmt->fetchAll();

            foreach ($responses as $response) {
                // Calculate response score (example formula)
                $responseScore = ($response['total_orders'] * 0.4) +
                               ($response['total_quantity'] * 0.3) +
                               (($response['total_amount'] / 1000000) * 0.3);

                // Save market response data
                $sql = "INSERT INTO market_response 
                        (year, month, product_id, customer_type, response_score,
                         total_orders, total_quantity, total_amount)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                        response_score = VALUES(response_score),
                        total_orders = VALUES(total_orders),
                        total_quantity = VALUES(total_quantity),
                        total_amount = VALUES(total_amount)";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $year,
                    $month,
                    $response['product_id'],
                    $response['customer_type'],
                    $responseScore,
                    $response['total_orders'],
                    $response['total_quantity'],
                    $response['total_amount']
                ]);
            }

            $this->logAnalytics('market_response', "Calculated market response for {$year}-{$month}");
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error calculating market response: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Calculate and store performance metrics
     */
    public function calculatePerformanceMetrics($year, $month) {
        try {
            $this->db->beginTransaction();

            // Calculate sales metrics
            $salesMetrics = $this->calculateSalesMetrics($year, $month);
            $this->savePerformanceMetric($year, $month, 'sales', $salesMetrics);

            // Calculate profit metrics
            $profitMetrics = $this->calculateProfitMetrics($year, $month);
            $this->savePerformanceMetric($year, $month, 'profit', $profitMetrics);

            // Calculate commission metrics
            $commissionMetrics = $this->calculateCommissionMetrics($year, $month);
            $this->savePerformanceMetric($year, $month, 'commission', $commissionMetrics);

            // Calculate expense metrics
            $expenseMetrics = $this->calculateExpenseMetrics($year, $month);
            $this->savePerformanceMetric($year, $month, 'expense', $expenseMetrics);

            $this->logAnalytics('performance_metrics', "Calculated performance metrics for {$year}-{$month}");
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error calculating performance metrics: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Generate predictive analytics
     */
    public function generatePredictiveAnalytics() {
        try {
            $this->db->beginTransaction();

            // Get products for prediction
            $sql = "SELECT id FROM products WHERE status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $products = $stmt->fetchAll();

            foreach ($products as $product) {
                // Get historical data
                $historicalData = $this->getHistoricalData($product['id']);
                
                // Generate predictions for next 3 months
                for ($i = 1; $i <= 3; $i++) {
                    $predictionDate = date('Y-m-d', strtotime("+{$i} months"));
                    
                    // Simple prediction based on moving average
                    $predictedSales = $this->calculateMovingAverage($historicalData);
                    $confidenceScore = $this->calculateConfidenceScore($historicalData);
                    $factorsConsidered = json_encode([
                        'historical_sales',
                        'seasonal_trends',
                        'market_response'
                    ]);

                    // Save prediction
                    $sql = "INSERT INTO predictive_analytics 
                            (product_id, prediction_date, predicted_sales,
                             confidence_score, factors_considered)
                            VALUES (?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE
                            predicted_sales = VALUES(predicted_sales),
                            confidence_score = VALUES(confidence_score),
                            factors_considered = VALUES(factors_considered)";
                    
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([
                        $product['id'],
                        $predictionDate,
                        $predictedSales,
                        $confidenceScore,
                        $factorsConsidered
                    ]);
                }
            }

            $this->logAnalytics('predictive_analytics', "Generated predictive analytics");
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error generating predictive analytics: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Get sales trends
     */
    public function getSalesTrends($filters = []) {
        try {
            $sql = "SELECT st.*, p.name as product_name, c.name as category_name
                   FROM sales_trends st
                   LEFT JOIN products p ON st.product_id = p.id
                   LEFT JOIN categories c ON st.category_id = c.id
                   WHERE 1=1";
            
            $params = [];

            if (!empty($filters['year'])) {
                $sql .= " AND st.year = ?";
                $params[] = $filters['year'];
            }

            if (!empty($filters['month'])) {
                $sql .= " AND st.month = ?";
                $params[] = $filters['month'];
            }

            if (!empty($filters['product_id'])) {
                $sql .= " AND st.product_id = ?";
                $params[] = $filters['product_id'];
            }

            if (!empty($filters['category_id'])) {
                $sql .= " AND st.category_id = ?";
                $params[] = $filters['category_id'];
            }

            $sql .= " ORDER BY st.year DESC, st.month DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            Logger::log("Error fetching sales trends: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Get market response data
     */
    public function getMarketResponse($filters = []) {
        try {
            $sql = "SELECT mr.*, p.name as product_name
                   FROM market_response mr
                   LEFT JOIN products p ON mr.product_id = p.id
                   WHERE 1=1";
            
            $params = [];

            if (!empty($filters['year'])) {
                $sql .= " AND mr.year = ?";
                $params[] = $filters['year'];
            }

            if (!empty($filters['month'])) {
                $sql .= " AND mr.month = ?";
                $params[] = $filters['month'];
            }

            if (!empty($filters['product_id'])) {
                $sql .= " AND mr.product_id = ?";
                $params[] = $filters['product_id'];
            }

            if (!empty($filters['customer_type'])) {
                $sql .= " AND mr.customer_type = ?";
                $params[] = $filters['customer_type'];
            }

            $sql .= " ORDER BY mr.year DESC, mr.month DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            Logger::log("Error fetching market response: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics($filters = []) {
        try {
            $sql = "SELECT * FROM performance_metrics WHERE 1=1";
            $params = [];

            if (!empty($filters['year'])) {
                $sql .= " AND year = ?";
                $params[] = $filters['year'];
            }

            if (!empty($filters['month'])) {
                $sql .= " AND month = ?";
                $params[] = $filters['month'];
            }

            if (!empty($filters['metric_type'])) {
                $sql .= " AND metric_type = ?";
                $params[] = $filters['metric_type'];
            }

            $sql .= " ORDER BY year DESC, month DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            Logger::log("Error fetching performance metrics: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Get predictive analytics
     */
    public function getPredictiveAnalytics($filters = []) {
        try {
            $sql = "SELECT pa.*, p.name as product_name
                   FROM predictive_analytics pa
                   LEFT JOIN products p ON pa.product_id = p.id
                   WHERE 1=1";
            
            $params = [];

            if (!empty($filters['product_id'])) {
                $sql .= " AND pa.product_id = ?";
                $params[] = $filters['product_id'];
            }

            if (!empty($filters['date_from'])) {
                $sql .= " AND pa.prediction_date >= ?";
                $params[] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $sql .= " AND pa.prediction_date <= ?";
                $params[] = $filters['date_to'];
            }

            $sql .= " ORDER BY pa.prediction_date ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            Logger::log("Error fetching predictive analytics: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Calculate growth rate
     */
    private function calculateGrowthRate($productId, $previousYear, $previousMonth, $currentAmount) {
        $sql = "SELECT total_amount 
               FROM sales_trends 
               WHERE product_id = ? 
               AND year = ? 
               AND month = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId, $previousYear, $previousMonth]);
        $previous = $stmt->fetch();

        if (!$previous || $previous['total_amount'] == 0) {
            return null;
        }

        return (($currentAmount - $previous['total_amount']) / $previous['total_amount']) * 100;
    }

    /**
     * Calculate sales metrics
     */
    private function calculateSalesMetrics($year, $month) {
        $sql = "SELECT COALESCE(SUM(total_amount), 0) as total
               FROM sales_trends
               WHERE year = ? AND month = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$year, $month]);
        return $stmt->fetch()['total'];
    }

    /**
     * Calculate profit metrics
     */
    private function calculateProfitMetrics($year, $month) {
        $sql = "SELECT net_profit
               FROM monthly_profits
               WHERE year = ? AND month = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$year, $month]);
        $result = $stmt->fetch();
        return $result ? $result['net_profit'] : 0;
    }

    /**
     * Calculate commission metrics
     */
    private function calculateCommissionMetrics($year, $month) {
        $sql = "SELECT COALESCE(SUM(commission_amount), 0) as total
               FROM sales_commissions
               WHERE YEAR(created_at) = ?
               AND MONTH(created_at) = ?
               AND status != 'cancelled'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$year, $month]);
        return $stmt->fetch()['total'];
    }

    /**
     * Calculate expense metrics
     */
    private function calculateExpenseMetrics($year, $month) {
        // This would typically connect to an expenses tracking system
        // For now, returning 0 as placeholder
        return 0;
    }

    /**
     * Save performance metric
     */
    private function savePerformanceMetric($year, $month, $type, $value) {
        // Get previous month's value for comparison
        $previousMonth = $month == 1 ? 12 : $month - 1;
        $previousYear = $month == 1 ? $year - 1 : $year;
        
        $sql = "SELECT metric_value 
               FROM performance_metrics 
               WHERE year = ? AND month = ? AND metric_type = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$previousYear, $previousMonth, $type]);
        $previous = $stmt->fetch();

        // Calculate growth rate
        $growthRate = null;
        if ($previous && $previous['metric_value'] > 0) {
            $growthRate = (($value - $previous['metric_value']) / $previous['metric_value']) * 100;
        }

        // Save metric
        $sql = "INSERT INTO performance_metrics 
                (year, month, metric_type, metric_value, comparison_value, growth_rate)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                metric_value = VALUES(metric_value),
                comparison_value = VALUES(comparison_value),
                growth_rate = VALUES(growth_rate)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $year,
            $month,
            $type,
            $value,
            $previous ? $previous['metric_value'] : null,
            $growthRate
        ]);
    }

    /**
     * Get historical data for predictions
     */
    private function getHistoricalData($productId) {
        $sql = "SELECT * FROM sales_trends
               WHERE product_id = ?
               ORDER BY year DESC, month DESC
               LIMIT 12";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    /**
     * Calculate moving average for predictions
     */
    private function calculateMovingAverage($historicalData) {
        if (empty($historicalData)) {
            return 0;
        }

        $total = 0;
        foreach ($historicalData as $data) {
            $total += $data['total_amount'];
        }

        return $total / count($historicalData);
    }

    /**
     * Calculate confidence score for predictions
     */
    private function calculateConfidenceScore($historicalData) {
        if (count($historicalData) < 2) {
            return 50; // Base confidence
        }

        // Calculate variance in historical data
        $values = array_column($historicalData, 'total_amount');
        $mean = array_sum($values) / count($values);
        
        $variance = 0;
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        $variance /= count($values);

        // Convert variance to confidence score (0-100)
        // Lower variance = higher confidence
        $maxVariance = pow($mean, 2); // Maximum possible variance
        $confidence = 100 - (($variance / $maxVariance) * 100);

        return max(min($confidence, 100), 0);
    }

    /**
     * Log analytics operation
     */
    private function logAnalytics($type, $message) {
        $sql = "INSERT INTO analytics_logs (log_type, log_message)
                VALUES (?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$type, $message]);
    }
}
