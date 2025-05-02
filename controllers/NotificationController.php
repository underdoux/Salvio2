<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/NotificationService.php';

class NotificationController extends Controller {
    private $notification;
    private $notificationService;
    private $order;
    private $user;

    protected $requiredPermissions = [
        'sendOrderNotification' => 'send_notifications',
        'getNotificationHistory' => 'view_notifications',
        'resendNotification' => 'resend_notifications'
    ];

    public function __construct() {
        parent::__construct();
        $this->notification = new Notification();
        $this->notificationService = new NotificationService();
        $this->order = new Order();
        $this->user = new User();
    }

    public function sendOrderNotification() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('send_notifications');
        if ($permCheck !== true) return $permCheck;

        $paramCheck = $this->validateRequiredParams(['order_id', 'type']);
        if ($paramCheck !== true) return $paramCheck;

        try {
            $this->beginTransaction();

            $order_id = $_POST['order_id'];
            $type = $_POST['type'];
            $message = $_POST['message'] ?? $this->getDefaultMessage($type, $order_id);

            // Get order details
            $order = $this->order->readOne($order_id);
            if (!$order) {
                throw new Exception('Order not found');
            }

            // Get user contact details
            $user = $this->user->readOne($order['user_id']);
            if (!$user) {
                throw new Exception('User not found');
            }

            // Check notification settings
            $notificationSettings = $this->getSetting('notification', null);
            $sent = false;
            $channels = [];

            // Send email if enabled
            if ($notificationSettings['email_enabled'] === 'true' && !empty($user['email'])) {
                $emailSent = $this->notificationService->sendEmail(
                    $user['email'],
                    "Order #{$order_id} Update",
                    $message
                );
                if ($emailSent) {
                    $channels[] = 'email';
                    $sent = true;
                }
            }

            // Send WhatsApp if enabled
            if ($notificationSettings['whatsapp_enabled'] === 'true' && !empty($user['phone'])) {
                $whatsappSent = $this->notificationService->sendWhatsApp(
                    $user['phone'],
                    $message
                );
                if ($whatsappSent) {
                    $channels[] = 'whatsapp';
                    $sent = true;
                }
            }

            if (!$sent) {
                throw new Exception('Failed to send notifications through any channel');
            }

            // Create notification record
            $this->notification->order_id = $order_id;
            $this->notification->user_id = $order['user_id'];
            $this->notification->type = $type;
            $this->notification->message = $message;
            $this->notification->channels = implode(',', $channels);
            $this->notification->status = 'sent';

            if (!$this->notification->create()) {
                throw new Exception('Failed to create notification record');
            }

            // Log notification
            $this->logAction(
                'notification_sent',
                'notifications',
                $this->notification->id,
                null,
                [
                    'order_id' => $order_id,
                    'type' => $type,
                    'channels' => $channels
                ]
            );

            $this->commit();

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Notification sent successfully',
                'channels' => $channels
            ]);

        } catch (Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    private function getDefaultMessage($type, $order_id) {
        $messages = [
            'new_order' => "Your order #{$order_id} has been received and is being processed.",
            'processing' => "Your order #{$order_id} is now being processed.",
            'shipped' => "Your order #{$order_id} has been shipped.",
            'delivered' => "Your order #{$order_id} has been delivered.",
            'payment_received' => "We have received payment for your order #{$order_id}.",
            'payment_pending' => "Payment is pending for your order #{$order_id}.",
            'order_delayed' => "There is a delay in processing your order #{$order_id}.",
            'low_stock' => "Some items in your order #{$order_id} are currently low in stock."
        ];

        return $messages[$type] ?? "Update regarding your order #{$order_id}";
    }

    public function getNotificationHistory() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('view_notifications');
        if ($permCheck !== true) return $permCheck;

        try {
            $filters = [
                'user_id' => $_GET['user_id'] ?? null,
                'order_id' => $_GET['order_id'] ?? null,
                'type' => $_GET['type'] ?? null
            ];

            $dateRange = $this->getDateRange();
            if ($dateRange) {
                $filters['start_date'] = $dateRange['start_date'];
                $filters['end_date'] = $dateRange['end_date'];
            }

            $notifications = $this->notification->getHistory(...array_values($filters));

            $result = [];
            while ($row = $notifications->fetch(PDO::FETCH_ASSOC)) {
                $result[] = [
                    'id' => $row['id'],
                    'order_id' => $row['order_id'],
                    'type' => $row['type'],
                    'message' => $row['message'],
                    'channels' => explode(',', $row['channels']),
                    'status' => $row['status'],
                    'created_at' => $this->formatDate($row['created_at'])
                ];
            }

            return $this->jsonResponse([
                'success' => true,
                'notifications' => $result,
                'filters' => $filters
            ]);

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function resendNotification() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('resend_notifications');
        if ($permCheck !== true) return $permCheck;

        $paramCheck = $this->validateRequiredParams(['notification_id']);
        if ($paramCheck !== true) return $paramCheck;

        try {
            $this->beginTransaction();

            // Get original notification
            $original = $this->notification->readOne($_POST['notification_id']);
            if (!$original) {
                throw new Exception('Notification not found');
            }

            // Get user contact details
            $user = $this->user->readOne($original['user_id']);
            if (!$user) {
                throw new Exception('User not found');
            }

            $sent = false;
            $channels = [];

            // Resend through specified channels or all original channels
            $requestedChannels = isset($_POST['channels']) ? 
                explode(',', $_POST['channels']) : 
                explode(',', $original['channels']);

            if (in_array('email', $requestedChannels) && !empty($user['email'])) {
                $emailSent = $this->notificationService->sendEmail(
                    $user['email'],
                    "Order #{$original['order_id']} Update",
                    $original['message']
                );
                if ($emailSent) {
                    $channels[] = 'email';
                    $sent = true;
                }
            }

            if (in_array('whatsapp', $requestedChannels) && !empty($user['phone'])) {
                $whatsappSent = $this->notificationService->sendWhatsApp(
                    $user['phone'],
                    $original['message']
                );
                if ($whatsappSent) {
                    $channels[] = 'whatsapp';
                    $sent = true;
                }
            }

            if (!$sent) {
                throw new Exception('Failed to resend notification');
            }

            // Create new notification record for the resend
            $this->notification->order_id = $original['order_id'];
            $this->notification->user_id = $original['user_id'];
            $this->notification->type = $original['type'];
            $this->notification->message = $original['message'];
            $this->notification->channels = implode(',', $channels);
            $this->notification->status = 'sent';
            $this->notification->parent_id = $original['id'];

            if (!$this->notification->create()) {
                throw new Exception('Failed to create resend notification record');
            }

            // Log resend
            $this->logAction(
                'notification_resent',
                'notifications',
                $this->notification->id,
                ['original_id' => $original['id']],
                ['channels' => $channels]
            );

            $this->commit();

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Notification resent successfully',
                'channels' => $channels
            ]);

        } catch (Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }
}
?>
