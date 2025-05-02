<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/StockMovement.php';
require_once __DIR__ . '/../services/NotificationService.php';

class StockController extends Controller {
    private $product;
    private $stockMovement;
    private $notification;

    protected $requiredPermissions = [
        'adjustStock' => 'manage_stock',
        'getStockMovements' => 'view_stock',
        'getLowStockProducts' => 'view_stock',
        'getStockValue' => 'view_stock_reports',
        'getStockTrends' => 'view_stock_reports',
        'validateStock' => 'view_stock'
    ];

    public function __construct() {
        parent::__construct();
        $this->product = new Product();
        $this->stockMovement = new StockMovement();
        $this->notification = new NotificationService();
    }

    public function adjustStock() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('manage_stock');
        if ($permCheck !== true) return $permCheck;

        $paramCheck = $this->validateRequiredParams(['product_id', 'quantity']);
        if ($paramCheck !== true) return $paramCheck;

        try {
            $this->beginTransaction();

            $product = $this->product->readOne($_POST['product_id']);
            if (!$product) {
                throw new Exception('Product not found');
            }

            $quantity = intval($_POST['quantity']);
            $type = $quantity >= 0 ? 'in' : 'out';
            
            // For stock out, validate available quantity
            if ($type === 'out' && abs($quantity) > $product['stock']) {
                throw new Exception('Insufficient stock available');
            }

            // Create stock movement
            $this->stockMovement->product_id = $_POST['product_id'];
            $this->stockMovement->type = $type;
            $this->stockMovement->quantity = abs($quantity);
            $this->stockMovement->reference_type = 'adjustment';
            $this->stockMovement->reference_id = null;
            $this->stockMovement->notes = $_POST['notes'] ?? 'Manual stock adjustment';

            if (!$this->stockMovement->create()) {
                throw new Exception('Failed to create stock movement');
            }

            // Check if stock is below threshold after adjustment
            $newStock = $product['stock'] + $quantity;
            if ($newStock <= $product['min_stock']) {
                // Send low stock notification
                if ($this->getSetting('notification', 'low_stock', 'true') === 'true') {
                    $this->notification->notifyOrderUpdate(
                        null,
                        'low_stock',
                        "Low stock alert for {$product['name']} (Current: {$newStock}, Minimum: {$product['min_stock']})"
                    );
                }
            }

            // Log the adjustment
            $this->logAction(
                'stock_adjusted',
                'stock_movements',
                $this->stockMovement->id,
                ['current_stock' => $product['stock']],
                [
                    'new_stock' => $newStock,
                    'adjustment' => $quantity,
                    'type' => $type
                ]
            );

            $this->commit();

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Stock adjusted successfully',
                'new_stock' => $newStock
            ]);

        } catch (Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    public function getStockMovements() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('view_stock');
        if ($permCheck !== true) return $permCheck;

        $paramCheck = $this->validateRequiredParams(['product_id'], 'GET');
        if ($paramCheck !== true) return $paramCheck;

        try {
            $dateRange = $this->getDateRange();
            
            $movements = $this->stockMovement->getProductMovements(
                $_GET['product_id'],
                $dateRange['start_date'] ?? null,
                $dateRange['end_date'] ?? null
            );

            $result = [];
            while ($row = $movements->fetch(PDO::FETCH_ASSOC)) {
                $result[] = [
                    'id' => $row['id'],
                    'type' => $row['type'],
                    'quantity' => $row['quantity'],
                    'reference_type' => $row['reference_type'],
                    'reference_id' => $row['reference_id'],
                    'notes' => $row['notes'],
                    'created_by' => $row['username'],
                    'created_at' => $this->formatDate($row['created_at'])
                ];
            }

            return $this->jsonResponse([
                'success' => true,
                'movements' => $result,
                'period' => $dateRange ?? null
            ]);

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function getLowStockProducts() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('view_stock');
        if ($permCheck !== true) return $permCheck;

        try {
            $threshold = $this->getSetting('inventory', 'low_stock_threshold', 10);
            $products = $this->stockMovement->getLowStockProducts($threshold);

            $result = [];
            while ($row = $products->fetch(PDO::FETCH_ASSOC)) {
                $result[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'category' => $row['category_name'],
                    'current_stock' => $row['stock'],
                    'min_stock' => $row['min_stock'],
                    'movement_count' => $row['movement_count']
                ];
            }

            return $this->jsonResponse([
                'success' => true,
                'threshold' => $threshold,
                'products' => $result
            ]);

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function getStockValue() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('view_stock_reports');
        if ($permCheck !== true) return $permCheck;

        try {
            $date = $_GET['date'] ?? date('Y-m-d');
            if (!$this->validateDate($date)) {
                return $this->jsonResponse(['error' => 'Invalid date format'], 400);
            }

            $report = $this->stockMovement->getStockValueReport($date);
            $result = [];

            while ($row = $report->fetch(PDO::FETCH_ASSOC)) {
                $row['total_value'] = $this->formatCurrency($row['total_value']);
                $result[] = $row;
            }

            $totals = [
                'total_products' => array_sum(array_column($result, 'product_count')),
                'total_units' => array_sum(array_column($result, 'total_units')),
                'total_value' => $this->formatCurrency(
                    array_sum(array_map(
                        function($value) {
                            return (float) str_replace(['IDR', ','], '', $value);
                        },
                        array_column($result, 'total_value')
                    ))
                )
            ];

            return $this->jsonResponse([
                'success' => true,
                'date' => $this->formatDate($date),
                'report' => $result,
                'summary' => $totals
            ]);

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function getStockTrends() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('view_stock_reports');
        if ($permCheck !== true) return $permCheck;

        try {
            $days = isset($_GET['days']) ? intval($_GET['days']) : 30;
            if ($days < 1 || $days > 365) {
                return $this->jsonResponse(['error' => 'Days must be between 1 and 365'], 400);
            }

            $trends = $this->stockMovement->getMovementTrends($days);
            $result = [];

            while ($row = $trends->fetch(PDO::FETCH_ASSOC)) {
                $result[] = [
                    'date' => $this->formatDate($row['date']),
                    'type' => $row['type'],
                    'movement_count' => $row['movement_count'],
                    'total_quantity' => $row['total_quantity']
                ];
            }

            return $this->jsonResponse([
                'success' => true,
                'period_days' => $days,
                'trends' => $result
            ]);

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function validateStock() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('view_stock');
        if ($permCheck !== true) return $permCheck;

        $paramCheck = $this->validateRequiredParams(['product_id', 'quantity']);
        if ($paramCheck !== true) return $paramCheck;

        try {
            $isValid = $this->stockMovement->validateStock(
                $_POST['product_id'],
                $_POST['quantity']
            );

            return $this->jsonResponse([
                'success' => true,
                'valid' => $isValid
            ]);

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
}
?>
