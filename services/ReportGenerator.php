<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportGenerator {
    private $conn;
    private $reportTypes = [
        'sales',
        'inventory',
        'commissions',
        'profits',
        'payments',
        'customers',
        'products'
    ];

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function generateReport($type, $params = []) {
        if (!in_array($type, $this->reportTypes)) {
            throw new Exception("Invalid report type: $type");
        }

        $method = "generate" . ucfirst($type) . "Report";
        return $this->$method($params);
    }

    private function generateSalesReport($params) {
        $query = "SELECT 
                    o.id,
                    o.order_number,
                    o.created_at,
                    o.status,
                    o.payment_status,
                    o.total,
                    o.tax_amount,
                    u.name as sales_person,
                    COUNT(oi.id) as total_items,
                    GROUP_CONCAT(p.name) as products
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE 1=1";

        // Apply filters
        if (!empty($params['start_date'])) {
            $query .= " AND o.created_at >= :start_date";
        }
        if (!empty($params['end_date'])) {
            $query .= " AND o.created_at <= :end_date";
        }
        if (!empty($params['status'])) {
            $query .= " AND o.status = :status";
        }
        if (!empty($params['payment_status'])) {
            $query .= " AND o.payment_status = :payment_status";
        }
        if (!empty($params['user_id'])) {
            $query .= " AND o.user_id = :user_id";
        }

        $query .= " GROUP BY o.id ORDER BY o.created_at DESC";

        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        if (!empty($params['start_date'])) $stmt->bindParam(':start_date', $params['start_date']);
        if (!empty($params['end_date'])) $stmt->bindParam(':end_date', $params['end_date']);
        if (!empty($params['status'])) $stmt->bindParam(':status', $params['status']);
        if (!empty($params['payment_status'])) $stmt->bindParam(':payment_status', $params['payment_status']);
        if (!empty($params['user_id'])) $stmt->bindParam(':user_id', $params['user_id']);

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate summary
        $summary = [
            'total_orders' => count($data),
            'total_revenue' => array_sum(array_column($data, 'total')),
            'total_tax' => array_sum(array_column($data, 'tax_amount')),
            'average_order_value' => count($data) > 0 ? array_sum(array_column($data, 'total')) / count($data) : 0,
            'status_breakdown' => $this->getStatusBreakdown($data, 'status'),
            'payment_status_breakdown' => $this->getStatusBreakdown($data, 'payment_status')
        ];

        // Generate Excel file
        $spreadsheet = new Spreadsheet();
        
        // Summary Sheet
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Summary');
        
        $this->writeSummarySheet($summarySheet, $summary, $params);
        
        // Details Sheet
        $spreadsheet->createSheet();
        $detailsSheet = $spreadsheet->getSheet(1);
        $detailsSheet->setTitle('Details');
        
        $this->writeDetailsSheet($detailsSheet, $data);

        // Charts Sheet
        $spreadsheet->createSheet();
        $chartsSheet = $spreadsheet->getSheet(2);
        $chartsSheet->setTitle('Charts');
        
        $this->writeChartsSheet($chartsSheet, $data);

        // Save file
        $writer = new Xlsx($spreadsheet);
        $filename = "sales_report_" . date('Y-m-d_His') . ".xlsx";
        $filepath = __DIR__ . "/../storage/reports/" . $filename;
        $writer->save($filepath);

        return [
            'filename' => $filename,
            'filepath' => $filepath,
            'summary' => $summary
        ];
    }

    private function generateInventoryReport($params) {
        $query = "SELECT 
                    p.id,
                    p.name,
                    p.stock,
                    p.price,
                    c.name as category,
                    p.by_order,
                    COUNT(oi.id) as times_ordered,
                    SUM(oi.quantity) as total_quantity_ordered,
                    MAX(o.created_at) as last_ordered,
                    (p.stock * p.price) as stock_value
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN order_items oi ON p.id = oi.product_id
                LEFT JOIN orders o ON oi.order_id = o.id
                WHERE 1=1";

        // Apply filters
        if (!empty($params['category_id'])) {
            $query .= " AND p.category_id = :category_id";
        }
        if (isset($params['low_stock']) && $params['low_stock']) {
            $query .= " AND p.stock <= p.min_stock";
        }
        if (isset($params['by_order'])) {
            $query .= " AND p.by_order = :by_order";
        }

        $query .= " GROUP BY p.id ORDER BY p.stock ASC";

        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        if (!empty($params['category_id'])) $stmt->bindParam(':category_id', $params['category_id']);
        if (isset($params['by_order'])) $stmt->bindParam(':by_order', $params['by_order'], PDO::PARAM_BOOL);

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate summary
        $summary = [
            'total_products' => count($data),
            'total_stock_value' => array_sum(array_column($data, 'stock_value')),
            'low_stock_items' => count(array_filter($data, function($item) {
                return $item['stock'] <= 0;
            })),
            'by_order_items' => count(array_filter($data, function($item) {
                return $item['by_order'] == 1;
            })),
            'category_breakdown' => $this->getCategoryBreakdown($data)
        ];

        // Generate Excel file
        $spreadsheet = new Spreadsheet();
        
        // Summary Sheet
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Summary');
        
        $this->writeInventorySummarySheet($summarySheet, $summary, $params);
        
        // Details Sheet
        $spreadsheet->createSheet();
        $detailsSheet = $spreadsheet->getSheet(1);
        $detailsSheet->setTitle('Details');
        
        $this->writeInventoryDetailsSheet($detailsSheet, $data);

        // Save file
        $writer = new Xlsx($spreadsheet);
        $filename = "inventory_report_" . date('Y-m-d_His') . ".xlsx";
        $filepath = __DIR__ . "/../storage/reports/" . $filename;
        $writer->save($filepath);

        return [
            'filename' => $filename,
            'filepath' => $filepath,
            'summary' => $summary
        ];
    }

    private function generateCommissionsReport($params) {
        $query = "SELECT 
                    c.id,
                    c.amount,
                    c.status,
                    c.created_at,
                    c.payment_date,
                    u.name as sales_person,
                    o.order_number,
                    o.total as order_total,
                    c.rate as commission_rate
                FROM commissions c
                LEFT JOIN users u ON c.user_id = u.id
                LEFT JOIN orders o ON c.order_id = o.id
                WHERE 1=1";

        // Apply filters
        if (!empty($params['start_date'])) {
            $query .= " AND c.created_at >= :start_date";
        }
        if (!empty($params['end_date'])) {
            $query .= " AND c.created_at <= :end_date";
        }
        if (!empty($params['status'])) {
            $query .= " AND c.status = :status";
        }
        if (!empty($params['user_id'])) {
            $query .= " AND c.user_id = :user_id";
        }

        $query .= " ORDER BY c.created_at DESC";

        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        if (!empty($params['start_date'])) $stmt->bindParam(':start_date', $params['start_date']);
        if (!empty($params['end_date'])) $stmt->bindParam(':end_date', $params['end_date']);
        if (!empty($params['status'])) $stmt->bindParam(':status', $params['status']);
        if (!empty($params['user_id'])) $stmt->bindParam(':user_id', $params['user_id']);

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate summary
        $summary = [
            'total_commissions' => count($data),
            'total_amount' => array_sum(array_column($data, 'amount')),
            'average_commission' => count($data) > 0 ? array_sum(array_column($data, 'amount')) / count($data) : 0,
            'status_breakdown' => $this->getStatusBreakdown($data, 'status'),
            'sales_person_breakdown' => $this->getSalesPersonBreakdown($data)
        ];

        // Generate Excel file
        $spreadsheet = new Spreadsheet();
        
        // Summary Sheet
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Summary');
        
        $this->writeCommissionSummarySheet($summarySheet, $summary, $params);
        
        // Details Sheet
        $spreadsheet->createSheet();
        $detailsSheet = $spreadsheet->getSheet(1);
        $detailsSheet->setTitle('Details');
        
        $this->writeCommissionDetailsSheet($detailsSheet, $data);

        // Save file
        $writer = new Xlsx($spreadsheet);
        $filename = "commissions_report_" . date('Y-m-d_His') . ".xlsx";
        $filepath = __DIR__ . "/../storage/reports/" . $filename;
        $writer->save($filepath);

        return [
            'filename' => $filename,
            'filepath' => $filepath,
            'summary' => $summary
        ];
    }

    private function generateProfitsReport($params) {
        $query = "SELECT 
                    pd.id,
                    pd.period_start,
                    pd.period_end,
                    pd.total_revenue,
                    pd.total_costs,
                    pd.net_profit,
                    pd.status,
                    pd.distributed_at,
                    pd.created_at,
                    COUNT(ps.id) as total_shares,
                    GROUP_CONCAT(CONCAT(i.name, ': ', ps.amount)) as share_breakdown
                FROM profit_distributions pd
                LEFT JOIN profit_shares ps ON pd.id = ps.distribution_id
                LEFT JOIN investors i ON ps.investor_id = i.id
                WHERE 1=1";

        // Apply filters
        if (!empty($params['start_date'])) {
            $query .= " AND pd.period_start >= :start_date";
        }
        if (!empty($params['end_date'])) {
            $query .= " AND pd.period_end <= :end_date";
        }
        if (!empty($params['status'])) {
            $query .= " AND pd.status = :status";
        }

        $query .= " GROUP BY pd.id ORDER BY pd.period_start DESC";

        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        if (!empty($params['start_date'])) $stmt->bindParam(':start_date', $params['start_date']);
        if (!empty($params['end_date'])) $stmt->bindParam(':end_date', $params['end_date']);
        if (!empty($params['status'])) $stmt->bindParam(':status', $params['status']);

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate summary
        $summary = [
            'total_distributions' => count($data),
            'total_revenue' => array_sum(array_column($data, 'total_revenue')),
            'total_costs' => array_sum(array_column($data, 'total_costs')),
            'total_profit' => array_sum(array_column($data, 'net_profit')),
            'average_profit_margin' => $this->calculateAverageProfitMargin($data),
            'status_breakdown' => $this->getStatusBreakdown($data, 'status')
        ];

        // Generate Excel file
        $spreadsheet = new Spreadsheet();
        
        // Summary Sheet
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Summary');
        
        $this->writeProfitSummarySheet($summarySheet, $summary, $params);
        
        // Details Sheet
        $spreadsheet->createSheet();
        $detailsSheet = $spreadsheet->getSheet(1);
        $detailsSheet->setTitle('Details');
        
        $this->writeProfitDetailsSheet($detailsSheet, $data);

        // Save file
        $writer = new Xlsx($spreadsheet);
        $filename = "profits_report_" . date('Y-m-d_His') . ".xlsx";
        $filepath = __DIR__ . "/../storage/reports/" . $filename;
        $writer->save($filepath);

        return [
            'filename' => $filename,
            'filepath' => $filepath,
            'summary' => $summary
        ];
    }

    private function getStatusBreakdown($data, $field) {
        $breakdown = [];
        foreach ($data as $item) {
            $status = $item[$field];
            if (!isset($breakdown[$status])) {
                $breakdown[$status] = 0;
            }
            $breakdown[$status]++;
        }
        return $breakdown;
    }

    private function getCategoryBreakdown($data) {
        $breakdown = [];
        foreach ($data as $item) {
            $category = $item['category'] ?? 'Uncategorized';
            if (!isset($breakdown[$category])) {
                $breakdown[$category] = 0;
            }
            $breakdown[$category]++;
        }
        return $breakdown;
    }

    private function getSalesPersonBreakdown($data) {
        $breakdown = [];
        foreach ($data as $item) {
            $person = $item['sales_person'];
            if (!isset($breakdown[$person])) {
                $breakdown[$person] = [
                    'count' => 0,
                    'amount' => 0
                ];
            }
            $breakdown[$person]['count']++;
            $breakdown[$person]['amount'] += $item['amount'];
        }
        return $breakdown;
    }

    private function calculateAverageProfitMargin($data) {
        if (empty($data)) return 0;
        
        $margins = array_map(function($item) {
            return $item['total_revenue'] > 0 ? 
                ($item['net_profit'] / $item['total_revenue']) * 100 : 0;
        }, $data);
        
        return array_sum($margins) / count($margins);
    }
}
?>
