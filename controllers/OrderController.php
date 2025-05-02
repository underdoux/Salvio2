<?php
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';

class OrderController {
    private $orderModel;
    private $orderItemModel;

    public function __construct() {
        $this->orderModel = new Order();
        $this->orderItemModel = new OrderItem();
    }

    public function list() {
        $orders = $this->orderModel->readAll();
        require __DIR__ . '/../views/order/list.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->orderModel->user_id = $_POST['user_id'] ?? null;
            $this->orderModel->status = 'new';
            $this->orderModel->total = $_POST['total'] ?? 0.0;
            $this->orderModel->tax_percentage = $_POST['tax_percentage'] ?? 0.0;
            $this->orderModel->payment_type = $_POST['payment_type'] ?? 'cash';

            if ($this->orderModel->create()) {
                $order_id = $this->orderModel->id;
                $items = $_POST['items'] ?? [];

                foreach ($items as $item) {
                    $this->orderItemModel->order_id = $order_id;
                    $this->orderItemModel->product_id = $item['product_id'];
                    $this->orderItemModel->quantity = $item['quantity'];
                    $this->orderItemModel->original_price = $item['original_price'];
                    $this->orderItemModel->adjusted_price = $item['adjusted_price'] ?? null;
                    $this->orderItemModel->adjustment_reason = $item['adjustment_reason'] ?? null;
                    $this->orderItemModel->create();
                }

                header("Location: /orders");
                exit();
            } else {
                $error = "Failed to create order.";
                require __DIR__ . '/../views/order/create.php';
            }
        } else {
            require __DIR__ . '/../views/order/create.php';
        }
    }

    public function view($id) {
        $order = $this->orderModel->readOne($id);
        $orderItems = $this->orderItemModel->readAllByOrder($id);
        require __DIR__ . '/../views/order/view.php';
    }

    public function updateStatus($id, $status) {
        $order = $this->orderModel->readOne($id);
        if ($order) {
            $this->orderModel->id = $id;
            $this->orderModel->status = $status;
            $this->orderModel->user_id = $order['user_id'];
            $this->orderModel->total = $order['total'];
            $this->orderModel->tax_percentage = $order['tax_percentage'];
            $this->orderModel->payment_type = $order['payment_type'];
            $this->orderModel->update();
            header("Location: /orders/view/$id");
            exit();
        } else {
            echo "Order not found.";
        }
    }
}
?>
