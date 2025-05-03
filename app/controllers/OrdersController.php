<?php

class OrdersController extends BaseController {
    protected $requiresAuth = true;
    private $orderModel;
    private $productModel;
    private $settingsModel;

    public function __construct() {
        parent::__construct();
        $this->orderModel = new Order();
        $this->productModel = new Product();
        $this->settingsModel = new Settings();
    }

    public function index() {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 10;

            $filters = [
                'search' => $_GET['search'] ?? null,
                'status' => $_GET['status'] ?? null,
                'payment_status' => $_GET['payment_status'] ?? null,
                'customer_type' => $_GET['customer_type'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null
            ];

            $orders = $this->orderModel->getAll($filters, $page, $limit);

            return $this->render('orders/index', [
                'title' => 'Manage Orders',
                'orders' => $orders,
                'filters' => $filters,
                'currentPage' => $page
            ]);
        } catch (Exception $e) {
            Logger::log("Error in orders index: " . $e->getMessage(), 'ERROR');
            return $this->render('orders/index', [
                'title' => 'Manage Orders',
                'error' => 'An error occurred while loading orders.'
            ]);
        }
    }

    public function create() {
        if ($this->isPost()) {
            try {
                $data = [
                    'customer_name' => $this->getPost('customer_name'),
                    'customer_type' => $this->getPost('customer_type'),
                    'customer_address' => $this->getPost('customer_address'),
                    'customer_phone' => $this->getPost('customer_phone'),
                    'payment_type' => $this->getPost('payment_type'),
                    'notes' => $this->getPost('notes'),
                    'created_by' => $_SESSION['user_id'],
                    'items' => $this->validateOrderItems($_POST['items'] ?? []),
                    'discount_amount' => $this->getPost('discount_amount', 0),
                    'discount_reason' => $this->getPost('discount_reason')
                ];

                // Validate order-level discount
                $orderDiscount = $this->getPost('discount_amount', 0);
                if ($orderDiscount > 0) {
                    $maxDiscountAmount = $this->settingsModel->get('max_discount_amount', 10000000);
                    if ($orderDiscount > $maxDiscountAmount) {
                        throw new Exception("Order discount amount exceeds maximum allowed (" . CurrencyFormatter::getInstance()->format($maxDiscountAmount) . ")");
                    }
                    if (empty($this->getPost('discount_reason'))) {
                        throw new Exception("Discount reason is required for order-level discount");
                    }
                }

                // Add shipping information if provided
                if (!empty($_POST['shipping'])) {
                    $data['shipping'] = [
                        'shipping_method' => $_POST['shipping']['method'],
                        'shipping_cost' => $_POST['shipping']['cost'],
                        'notes' => $_POST['shipping']['notes']
                    ];
                }

                $orderId = $this->orderModel->create($data);
                
                if ($this->isAjax()) {
                    return $this->json(['success' => true, 'order_id' => $orderId]);
                }
                
                $this->redirect('/orders/view/' . $orderId);
            } catch (Exception $e) {
                Logger::log("Error creating order: " . $e->getMessage(), 'ERROR');
                if ($this->isAjax()) {
                    return $this->json(['error' => $e->getMessage()], 400);
                }
                return $this->render('orders/create', [
                    'title' => 'Create Order',
                    'products' => $this->productModel->getAll(),
                    'error' => $e->getMessage(),
                    'data' => $data ?? []
                ]);
            }
        }

        // Get max discount settings for the view
        $maxDiscountPercent = $this->settingsModel->get('max_discount_percent', 50);
        $maxDiscountAmount = $this->settingsModel->get('max_discount_amount', 10000000);

        return $this->render('orders/create', [
            'title' => 'Create Order',
            'products' => $this->productModel->getAll(),
            'maxDiscountPercent' => $maxDiscountPercent,
            'maxDiscountAmount' => $maxDiscountAmount
        ]);
    }

    public function view($id) {
        try {
            $order = $this->orderModel->getById($id);
            if (!$order) {
                throw new Exception("Order not found");
            }

            return $this->render('orders/view', [
                'title' => "Order #{$order['order_number']}",
                'order' => $order
            ]);
        } catch (Exception $e) {
            Logger::log("Error viewing order {$id}: " . $e->getMessage(), 'ERROR');
            $this->redirect('/orders');
        }
    }

    public function updateStatus($id) {
        try {
            if (!$this->isPost()) {
                throw new Exception("Invalid request method");
            }

            $status = $this->getPost('status');
            $notes = $this->getPost('notes');

            $data = [
                'order_status' => $status,
                'status_notes' => $notes
            ];

            $this->orderModel->update($id, $data);
            
            if ($this->isAjax()) {
                return $this->json(['success' => true]);
            }
            
            $this->redirect('/orders/view/' . $id);
        } catch (Exception $e) {
            Logger::log("Error updating order status {$id}: " . $e->getMessage(), 'ERROR');
            if ($this->isAjax()) {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            $this->redirect('/orders/view/' . $id);
        }
    }

    public function addPayment($id) {
        try {
            if (!$this->isPost()) {
                throw new Exception("Invalid request method");
            }

            $paymentData = [
                'amount' => $this->getPost('amount'),
                'payment_date' => $this->getPost('payment_date'),
                'payment_method' => $this->getPost('payment_method'),
                'reference_number' => $this->getPost('reference_number'),
                'notes' => $this->getPost('notes')
            ];

            $this->orderModel->addPayment($id, $paymentData);
            
            if ($this->isAjax()) {
                return $this->json(['success' => true]);
            }
            
            $this->redirect('/orders/view/' . $id);
        } catch (Exception $e) {
            Logger::log("Error adding payment for order {$id}: " . $e->getMessage(), 'ERROR');
            if ($this->isAjax()) {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            $this->redirect('/orders/view/' . $id);
        }
    }

    public function updateShipping($id) {
        try {
            if (!$this->isPost()) {
                throw new Exception("Invalid request method");
            }

            $shippingData = [
                'shipping_date' => $this->getPost('shipping_date'),
                'tracking_number' => $this->getPost('tracking_number'),
                'shipping_method' => $this->getPost('shipping_method'),
                'shipping_cost' => $this->getPost('shipping_cost'),
                'notes' => $this->getPost('notes')
            ];

            $data = ['shipping' => $shippingData];
            $this->orderModel->update($id, $data);
            
            if ($this->isAjax()) {
                return $this->json(['success' => true]);
            }
            
            $this->redirect('/orders/view/' . $id);
        } catch (Exception $e) {
            Logger::log("Error updating shipping for order {$id}: " . $e->getMessage(), 'ERROR');
            if ($this->isAjax()) {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            $this->redirect('/orders/view/' . $id);
        }
    }

    private function validateOrderItems($items) {
        if (empty($items)) {
            throw new Exception("Order must have at least one item");
        }

        $validatedItems = [];
        foreach ($items as $item) {
            // Validate product exists
            $product = $this->productModel->getById($item['product_id']);
            if (!$product) {
                throw new Exception("Invalid product selected");
            }

            // Validate quantity
            if ($item['quantity'] <= 0) {
                throw new Exception("Invalid quantity for product {$product['name']}");
            }

            // Check stock if product is stocked
            if ($product['stock_type'] === 'stocked' && $item['quantity'] > $product['current_stock']) {
                throw new Exception("Insufficient stock for product {$product['name']}");
            }

            // Get max discount settings
            $maxDiscountPercent = $this->settingsModel->get('max_discount_percent', 50);
            $maxDiscountAmount = $this->settingsModel->get('max_discount_amount', 10000000);

            // Validate item discount
            if (!empty($item['discount_percent'])) {
                if ($item['discount_percent'] < 0 || $item['discount_percent'] > $maxDiscountPercent) {
                    throw new Exception("Discount percentage for product {$product['name']} exceeds maximum allowed ({$maxDiscountPercent}%)");
                }
                if (empty($item['discount_reason'])) {
                    throw new Exception("Discount reason required for product {$product['name']}");
                }

                // Calculate and validate discount amount
                $discountAmount = ($product['selling_price'] * $item['quantity'] * $item['discount_percent']) / 100;
                if ($discountAmount > $maxDiscountAmount) {
                    throw new Exception("Discount amount for product {$product['name']} exceeds maximum allowed (" . CurrencyFormatter::getInstance()->format($maxDiscountAmount) . ")");
                }
            }

            $validatedItems[] = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $product['selling_price'],
                'discount_percent' => $item['discount_percent'] ?? 0,
                'discount_reason' => $item['discount_reason'] ?? null
            ];
        }

        return $validatedItems;
    }

    private function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
