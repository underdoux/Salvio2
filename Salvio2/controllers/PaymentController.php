<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../services/NotificationService.php';

class PaymentController extends Controller {
    private $payment;
    private $order;
    private $notification;

    protected $requiredPermissions = [
        'processPayment' => 'manage_payments',
        'voidPayment' => 'void_payments',
        'getPaymentHistory' => 'view_payments',
        'getPaymentSummary' => 'view_payment_reports'
    ];

    public function __construct() {
        parent::__construct();
        $this->payment = new Payment();
        $this->order = new Order();
        $this->notification = new NotificationService();
    }

    public function processPayment() {
        // Check authentication and permissions
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('manage_payments');
        if ($permCheck !== true) return $permCheck;

        // Validate required parameters
        $paramCheck = $this->validateRequiredParams(['order_id', 'amount', 'payment_method']);
        if ($paramCheck !== true) return $paramCheck;

        try {
            // Start transaction
            $this->beginTransaction();

            $this->payment->order_id = $_POST['order_id'];
            $this->payment->amount = $_POST['amount'];
            $this->payment->payment_method = $_POST['payment_method'];
            $this->payment->payment_date = $_POST['payment_date'] ?? date('Y-m-d');
            $this->payment->notes = $_POST['notes'] ?? '';

            // Validate payment amount
            $order = $this->order->readOne($this->payment->order_id);
            if (!$order) {
                throw new Exception('Order not found');
            }

            $totalPaid = $this->payment->getTotalPaidAmount($this->payment->order_id);
            $remainingAmount = $order['total'] - $totalPaid;

            if ($this->payment->amount > $remainingAmount) {
                throw new Exception('Payment amount exceeds remaining balance');
            }

            // Process payment
            if (!$this->payment->create()) {
                throw new Exception('Failed to process payment');
            }

            // Update order status if fully paid
            if (($totalPaid + $this->payment->amount) >= $order['total']) {
                $this->order->id = $this->payment->order_id;
                $this->order->payment_status = 'completed';
                $this->order->update();
            }

            // Log the payment
            $this->logAction(
                'payment_processed',
                'payments',
                $this->payment->id,
                null,
                [
                    'order_id' => $this->payment->order_id,
                    'amount' => $this->payment->amount,
                    'method' => $this->payment->payment_method
                ]
            );

            // Send notification if enabled
            if ($this->getSetting('notification', 'payment_notifications', 'true') === 'true') {
                $this->notification->notifyOrderUpdate(
                    $this->payment->order_id,
                    'payment',
                    "Payment of " . $this->formatCurrency($this->payment->amount) . " received for order #{$this->payment->order_id}"
                );
            }

            $this->commit();

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Payment processed successfully',
                'payment_id' => $this->payment->id,
                'remaining_balance' => $this->formatCurrency($remainingAmount - $this->payment->amount)
            ]);

        } catch (Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    public function getPaymentHistory() {
        // Check authentication and permissions
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('view_payments');
        if ($permCheck !== true) return $permCheck;

        // Validate required parameters
        $paramCheck = $this->validateRequiredParams(['order_id'], 'GET');
        if ($paramCheck !== true) return $paramCheck;

        try {
            $payments = $this->payment->readByOrder($_GET['order_id']);
            $result = [];

            while ($row = $payments->fetch(PDO::FETCH_ASSOC)) {
                $result[] = [
                    'id' => $row['id'],
                    'amount' => $this->formatCurrency($row['amount']),
                    'payment_date' => $this->formatDate($row['payment_date']),
                    'payment_method' => $row['payment_method'],
                    'notes' => $row['notes'],
                    'created_at' => $this->formatDate($row['created_at'])
                ];
            }

            return $this->jsonResponse([
                'success' => true,
                'payments' => $result
            ]);

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function voidPayment() {
        // Check authentication and permissions
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('void_payments');
        if ($permCheck !== true) return $permCheck;

        // Validate required parameters
        $paramCheck = $this->validateRequiredParams(['payment_id']);
        if ($paramCheck !== true) return $paramCheck;

        try {
            $this->beginTransaction();

            // Get payment details before deletion
            $payment = $this->payment->readOne($_POST['payment_id']);
            if (!$payment) {
                throw new Exception('Payment not found');
            }

            // Delete payment
            if (!$this->payment->delete($_POST['payment_id'])) {
                throw new Exception('Failed to void payment');
            }

            // Update order payment status
            $totalPaid = $this->payment->getTotalPaidAmount($payment['order_id']);
            $order = $this->order->readOne($payment['order_id']);
            
            $this->order->id = $payment['order_id'];
            $this->order->payment_status = $totalPaid > 0 ? 'partial' : 'pending';
            $this->order->update();

            // Log the void
            $this->logAction(
                'payment_voided',
                'payments',
                $_POST['payment_id'],
                $payment,
                null
            );

            $this->commit();

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Payment voided successfully'
            ]);

        } catch (Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    public function getPaymentSummary() {
        // Check authentication and permissions
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('view_payment_reports');
        if ($permCheck !== true) return $permCheck;

        try {
            $dateRange = $this->getDateRange();
            if (!$dateRange) {
                return $this->jsonResponse(['error' => 'Invalid date range'], 400);
            }

            $query = "SELECT 
                        DATE(payment_date) as date,
                        payment_method,
                        COUNT(*) as count,
                        SUM(amount) as total_amount
                     FROM payments
                     WHERE payment_date BETWEEN :start_date AND :end_date
                     GROUP BY DATE(payment_date), payment_method
                     ORDER BY date DESC, payment_method";

            $stmt = $this->payment->conn->prepare($query);
            $stmt->bindParam(':start_date', $dateRange['start_date']);
            $stmt->bindParam(':end_date', $dateRange['end_date']);
            $stmt->execute();

            $summary = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $row['total_amount'] = $this->formatCurrency($row['total_amount']);
                $row['date'] = $this->formatDate($row['date']);
                $summary[] = $row;
            }

            return $this->jsonResponse([
                'success' => true,
                'summary' => $summary,
                'period' => $dateRange
            ]);

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
}
?>
