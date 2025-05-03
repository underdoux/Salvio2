<?php

require_once __DIR__ . '/Notification.php';

class Commission extends BaseModel {
    private $notification;

    public function __construct() {
        parent::__construct();
        $this->notification = new Notification();
    }

    // ... (keep existing calculation and reporting methods) ...

    public function updateCommissionStatus($commissionId, $status) {
        try {
            $this->db->beginTransaction();

            // Get commission details before update
            $sql = "SELECT c.*, u.email, CONCAT(u.first_name, ' ', u.last_name) as recipient_name 
                    FROM commissions c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.id = ?";
            $commission = $this->db->query($sql, [$commissionId])->fetch();

            if (!$commission) {
                throw new Exception("Commission not found");
            }

            // Update status
            $sql = "UPDATE commissions SET status = ? WHERE id = ?";
            $this->db->query($sql, [$status, $commissionId]);

            // Create notification
            $notificationData = [
                'user_id' => $commission['user_id'],
                'type' => 'commission_status',
                'title' => 'Commission Status Updated',
                'message' => "Your commission of $" . number_format($commission['amount'], 2) . 
                           " has been marked as " . strtoupper($status),
                'reference_type' => 'commission',
                'reference_id' => $commissionId
            ];
            
            $this->notification->createNotification($notificationData);

            // Send email notification
            $emailSubject = "Commission Status Update";
            $emailMessage = "Dear {$commission['recipient_name']},\n\n" .
                          "Your commission of $" . number_format($commission['amount'], 2) . 
                          " has been marked as " . strtoupper($status) . ".\n\n" .
                          "Please log in to your account for more details.\n\n" .
                          "Best regards,\nSalvio POS Team";

            $this->notification->sendEmailNotification(
                $commission['user_id'],
                $emailSubject,
                $emailMessage
            );

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function recordPayment($commissionId, $data) {
        try {
            $this->db->beginTransaction();

            // Get commission details
            $sql = "SELECT c.*, u.email, CONCAT(u.first_name, ' ', u.last_name) as recipient_name 
                    FROM commissions c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.id = ?";
            $commission = $this->db->query($sql, [$commissionId])->fetch();

            if (!$commission) {
                throw new Exception("Commission not found");
            }

            // Insert payment record
            $sql = "INSERT INTO commission_payments (
                        commission_id,
                        amount,
                        payment_date,
                        payment_method,
                        reference_number,
                        notes,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, NOW())";

            $params = [
                $commissionId,
                $data['amount'],
                $data['payment_date'],
                $data['payment_method'],
                $data['reference_number'],
                $data['notes'] ?? null
            ];

            $this->db->query($sql, $params);
            $paymentId = $this->db->lastInsertId();

            // Update commission status
            $this->updateCommissionStatus($commissionId, 'paid');

            // Create notification
            $notificationData = [
                'user_id' => $commission['user_id'],
                'type' => 'commission_payment',
                'title' => 'Commission Payment Recorded',
                'message' => "A payment of $" . number_format($data['amount'], 2) . 
                           " has been recorded for your commission",
                'reference_type' => 'commission_payment',
                'reference_id' => $paymentId
            ];
            
            $this->notification->createNotification($notificationData);

            // Send email notification
            $emailSubject = "Commission Payment Recorded";
            $emailMessage = "Dear {$commission['recipient_name']},\n\n" .
                          "A payment of $" . number_format($data['amount'], 2) . 
                          " has been recorded for your commission.\n\n" .
                          "Payment Details:\n" .
                          "- Method: {$data['payment_method']}\n" .
                          "- Reference: {$data['reference_number']}\n" .
                          "- Date: {$data['payment_date']}\n\n" .
                          "Please log in to your account for more details.\n\n" .
                          "Best regards,\nSalvio POS Team";

            $this->notification->sendEmailNotification(
                $commission['user_id'],
                $emailSubject,
                $emailMessage
            );

            $this->db->commit();
            return $paymentId;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function calculateOrderCommission($orderId) {
        try {
            $result = parent::calculateOrderCommission($orderId);

            // Get commission details
            $sql = "SELECT c.*, u.email, CONCAT(u.first_name, ' ', u.last_name) as recipient_name 
                    FROM commissions c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.id = ?";
            $commission = $this->db->query($sql, [$result['commission_id']])->fetch();

            // Create notification for new commission
            $notificationData = [
                'user_id' => $commission['user_id'],
                'type' => 'new_commission',
                'title' => 'New Commission Generated',
                'message' => "A new commission of $" . number_format($commission['amount'], 2) . 
                           " has been generated for Order #{$orderId}",
                'reference_type' => 'commission',
                'reference_id' => $result['commission_id']
            ];
            
            $this->notification->createNotification($notificationData);

            // Send email notification
            $emailSubject = "New Commission Generated";
            $emailMessage = "Dear {$commission['recipient_name']},\n\n" .
                          "A new commission of $" . number_format($commission['amount'], 2) . 
                          " has been generated for Order #{$orderId}.\n\n" .
                          "Please log in to your account to view the details.\n\n" .
                          "Best regards,\nSalvio POS Team";

            $this->notification->sendEmailNotification(
                $commission['user_id'],
                $emailSubject,
                $emailMessage
            );

            return $result;

        } catch (Exception $e) {
            throw $e;
        }
    }
}
