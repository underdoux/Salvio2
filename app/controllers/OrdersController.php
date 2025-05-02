<?php

require_once __DIR__ . '/../helpers/Logger.php';

class OrdersController extends BaseController {
    private $orderModel;
    private $productModel;

    public function __construct() {
        parent::__construct();
        $this->orderModel = new Order();
        $this->productModel = new Product();
    }

    public function index() {
        Logger::log("User '{$_SESSION['user']['username']}' accessed orders list");
        
        $filters = [
            'status' => $_GET['status'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null
        ];

        $orders = $this->orderModel->getAll($filters);
        
        $this->render('orders/index', [
            'title' => 'Orders - Salvio POS',
            'orders' => $orders,
            'filters' => $filters
        ]);
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Validate discount if present
                if (!empty($_POST['discount'])) {
                    $this->validateDiscount($_POST['discount'], $_POST['discount_reason']);
                }

                $orderData = [
                    'customer_id' => $_POST['customer_id'],
                    'total_amount' => $this->calculateTotal($_POST['items']),
                    'discount' => $_POST['discount'] ?? 0,
                    'payment_type' => $_POST['payment_type'],
                    'items' => $this->validateItems($_POST['items'])
                ];

                $orderId = $this->orderModel->create($orderData);
                
                Logger::log("Order #{$orderId} created successfully with " . count($orderData['items']) . " items");
                
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'message' => "Order #{$orderId} created successfully"
                ];
                
                $this->redirect('/Salvio2/public/orders/view/' . $orderId);

            } catch (Exception $e) {
                Logger::log("Error creating order: " . $e->getMessage());
                $_SESSION['flash'] = [
                    'type' => 'danger',
                    'message' => $e->getMessage()
                ];
                $this->redirect('/Salvio2/public/orders/create');
            }
        }

        // Get available products for order creation
        $products = $this->productModel->getAll();
        
        $this->render('orders/create', [
            'title' => 'Create Order - Salvio POS',
            'products' => $products
        ]);
    }

    public function view($id) {
        $order = $this->orderModel->getById($id);
        
        if (!$order) {
            Logger::log("Attempt to view non-existent order #{$id}");
            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => 'Order not found'
            ];
            $this->redirect('/Salvio2/public/orders');
        }

        Logger::log("User '{$_SESSION['user']['username']}' viewed order #{$id}");
        
        $this->render('orders/view', [
            'title' => "Order #{$id} - Salvio POS",
            'order' => $order
        ]);
    }

    public function updateStatus($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/Salvio2/public/orders/view/' . $id);
        }

        try {
            $newStatus = $_POST['status'];
            $this->orderModel->updateStatus($id, $newStatus);
            
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => "Order status updated to {$newStatus}"
            ];

        } catch (Exception $e) {
            Logger::log("Error updating order #{$id} status: " . $e->getMessage());
            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => $e->getMessage()
            ];
        }

        $this->redirect('/Salvio2/public/orders/view/' . $id);
    }

    private function validateDiscount($discount, $reason) {
        $maxDiscount = 30; // 30% maximum discount allowed
        
        if ($discount > $maxDiscount) {
            throw new Exception("Maximum discount allowed is {$maxDiscount}%");
        }
        
        if (empty($reason)) {
            throw new Exception("Discount reason is required for discounts");
        }
    }

    private function calculateTotal($items) {
        $total = 0;
        foreach ($items as $item) {
            $total += $item['quantity'] * $item['unit_price'];
        }
        return $total;
    }

    private function validateItems($items) {
        if (empty($items)) {
            throw new Exception("Order must contain at least one item");
        }

        foreach ($items as &$item) {
            $product = $this->productModel->find($item['product_id']);
            
            if (!$product) {
                throw new Exception("Invalid product selected");
            }

            // Check stock availability for stocked products
            if ($product['stock_type'] === 'stocked') {
                $stock = $this->productModel->getStock($item['product_id']);
                if ($stock < $item['quantity']) {
                    throw new Exception("Insufficient stock for product {$product['name']}");
                }
            }

            $item['unit_price'] = $product['selling_price'];
        }

        return $items;
    }
}
