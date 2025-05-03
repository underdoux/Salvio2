<?php

require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Commission.php';

class OrdersController extends BaseController {
    protected $requiresAuth = true;
    protected $order;
    protected $commission;

    public function __construct() {
        parent::__construct();
        $this->order = new Order();
        $this->commission = new Commission();
    }

    public function updateStatus($id) {
        try {
            $status = $_POST['status'] ?? null;
            
            if (!$status) {
                throw new Exception('Status is required');
            }

            // Update order status
            $this->order->updateStatus($id, $status);

            // If order is completed, calculate commission
            if ($status === 'completed') {
                $commissionResult = $this->commission->calculateOrderCommission($id);
                
                // Log the commission calculation
                Logger::log("Commission calculated for Order #{$id}. Amount: " . 
                          number_format($commissionResult['total_amount'], 2));
            }

            $this->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'commission_calculated' => ($status === 'completed')
            ]);

        } catch (Exception $e) {
            Logger::log("Error updating order status: " . $e->getMessage(), 'ERROR');
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function index() {
        $data = [
            'title' => 'Orders',
            'description' => 'Manage orders and track their status',
            'orders' => $this->order->getAllOrders()
        ];
        $this->render('orders/index', $data);
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $orderData = $_POST;
                $orderId = $this->order->createOrder($orderData);
                
                $this->json([
                    'success' => true,
                    'message' => 'Order created successfully',
                    'order_id' => $orderId
                ]);
            } catch (Exception $e) {
                Logger::log("Error creating order: " . $e->getMessage(), 'ERROR');
                $this->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }
        } else {
            $data = [
                'title' => 'Create Order',
                'description' => 'Create a new order'
            ];
            $this->render('orders/create', $data);
        }
    }

    public function view($id) {
        $order = $this->order->getOrder($id);
        if (!$order) {
            http_response_code(404);
            die('Order not found');
        }

        $data = [
            'title' => "Order #{$id}",
            'description' => 'View order details',
            'order' => $order,
            'items' => $this->order->getOrderItems($id)
        ];

        // If order is completed, get commission details
        if ($order['status'] === 'completed') {
            $data['commission'] = $this->commission->getCommissionsByOrder($id);
        }

        $this->render('orders/view', $data);
    }
}
