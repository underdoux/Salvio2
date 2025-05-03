<?php

require_once __DIR__ . '/Logger.php';

class DataValidator {
    public static function validateProfitCalculation($data) {
        try {
            Logger::log("Validating profit calculation data");
            $errors = [];

            // Validate total sales
            if (!isset($data['total_sales']) || !is_numeric($data['total_sales']) || $data['total_sales'] < 0) {
                $errors[] = "Invalid total sales amount";
            }

            // Validate product costs
            if (!isset($data['total_product_cost']) || !is_numeric($data['total_product_cost']) || $data['total_product_cost'] < 0) {
                $errors[] = "Invalid total product cost";
            }

            // Validate commissions
            if (!isset($data['total_commissions']) || !is_numeric($data['total_commissions']) || $data['total_commissions'] < 0) {
                $errors[] = "Invalid total commissions";
            }

            // Validate expenses
            if (!isset($data['total_expenses']) || !is_numeric($data['total_expenses']) || $data['total_expenses'] < 0) {
                $errors[] = "Invalid total expenses";
            }

            // Validate net profit calculation
            $calculatedNetProfit = $data['total_sales'] - $data['total_product_cost'] - 
                                 $data['total_commissions'] - $data['total_expenses'];
            if (!isset($data['net_profit']) || abs($data['net_profit'] - $calculatedNetProfit) > 0.01) {
                $errors[] = "Net profit calculation mismatch";
            }

            // Validate period
            if (!isset($data['period']) || !self::isValidPeriod($data['period'])) {
                $errors[] = "Invalid period format";
            }

            // Check for duplicate period
            if (self::isDuplicatePeriod($data['period'])) {
                $errors[] = "Profit calculation already exists for this period";
            }

            // Validate distributions total percentage
            if (!self::validateDistributionPercentages($data['distributions'] ?? [])) {
                $errors[] = "Distribution percentages do not total 100%";
            }

            if (!empty($errors)) {
                Logger::log("Validation errors found: " . implode(", ", $errors));
                return [
                    'valid' => false,
                    'errors' => $errors
                ];
            }

            Logger::log("Profit calculation data validated successfully");
            return [
                'valid' => true,
                'data' => $data
            ];

        } catch (Exception $e) {
            Logger::log("Error in profit calculation validation: " . $e->getMessage());
            return [
                'valid' => false,
                'errors' => ["System error during validation"]
            ];
        }
    }

    private static function isValidPeriod($period) {
        // Check if period is in YYYY-MM format
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period);
    }

    private static function isDuplicatePeriod($period) {
        global $db;
        $sql = "SELECT COUNT(*) as count FROM monthly_profits WHERE period = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$period]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
    }

    private static function validateDistributionPercentages($distributions) {
        $total = 0;
        foreach ($distributions as $dist) {
            if (!isset($dist['percentage']) || !is_numeric($dist['percentage']) || $dist['percentage'] < 0) {
                return false;
            }
            $total += $dist['percentage'];
        }
        return abs($total - 100) < 0.01; // Allow for small floating point differences
    }

    public static function validateInvestorData($data) {
        try {
            Logger::log("Validating investor data");
            $errors = [];

            // Validate investor ID
            if (!isset($data['investor_id']) || !is_numeric($data['investor_id'])) {
                $errors[] = "Invalid investor ID";
            }

            // Validate percentage
            if (!isset($data['percentage']) || !is_numeric($data['percentage']) || 
                $data['percentage'] < 0 || $data['percentage'] > 100) {
                $errors[] = "Invalid percentage value";
            }

            // Validate amount
            if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] < 0) {
                $errors[] = "Invalid amount";
            }

            // Validate status
            $validStatuses = ['pending', 'approved', 'paid'];
            if (!isset($data['status']) || !in_array($data['status'], $validStatuses)) {
                $errors[] = "Invalid status";
            }

            if (!empty($errors)) {
                Logger::log("Investor data validation errors: " . implode(", ", $errors));
                return [
                    'valid' => false,
                    'errors' => $errors
                ];
            }

            Logger::log("Investor data validated successfully");
            return [
                'valid' => true,
                'data' => $data
            ];

        } catch (Exception $e) {
            Logger::log("Error in investor data validation: " . $e->getMessage());
            return [
                'valid' => false,
                'errors' => ["System error during validation"]
            ];
        }
    }

    public static function validateOrderData($data) {
        try {
            Logger::log("Validating order data");
            $errors = [];

            // Validate order items
            if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
                $errors[] = "Order must contain at least one item";
            } else {
                foreach ($data['items'] as $item) {
                    if (!self::validateOrderItem($item)) {
                        $errors[] = "Invalid order item data";
                        break;
                    }
                }
            }

            // Validate total amount
            if (!isset($data['total_amount']) || !is_numeric($data['total_amount']) || $data['total_amount'] <= 0) {
                $errors[] = "Invalid total amount";
            }

            // Validate order date
            if (!isset($data['order_date']) || !strtotime($data['order_date'])) {
                $errors[] = "Invalid order date";
            }

            // Validate status
            $validStatuses = ['pending', 'processing', 'completed', 'cancelled'];
            if (!isset($data['status']) || !in_array($data['status'], $validStatuses)) {
                $errors[] = "Invalid order status";
            }

            if (!empty($errors)) {
                Logger::log("Order validation errors: " . implode(", ", $errors));
                return [
                    'valid' => false,
                    'errors' => $errors
                ];
            }

            Logger::log("Order data validated successfully");
            return [
                'valid' => true,
                'data' => $data
            ];

        } catch (Exception $e) {
            Logger::log("Error in order validation: " . $e->getMessage());
            return [
                'valid' => false,
                'errors' => ["System error during validation"]
            ];
        }
    }

    private static function validateOrderItem($item) {
        return isset($item['product_id']) && is_numeric($item['product_id']) &&
               isset($item['quantity']) && is_numeric($item['quantity']) && $item['quantity'] > 0 &&
               isset($item['price']) && is_numeric($item['price']) && $item['price'] >= 0;
    }
}
