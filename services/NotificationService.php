<?php
class NotificationService {
    public function sendEmail($to, $subject, $message) {
        // Use PHP mail() or an external library like PHPMailer
        $headers = "From: no-reply@pospharma.com\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        return mail($to, $subject, $message, $headers);
    }

    public function sendWhatsApp($phoneNumber, $message) {
        // Integrate with WhatsApp API provider (e.g., Twilio, WhatsApp Business API)
        // This is a placeholder for actual API integration
        // Return true if sent successfully, false otherwise
        return true;
    }

    public function notifyOrderUpdate($orderId, $type, $message) {
        // Save notification record to DB
        require_once __DIR__ . '/../models/Notification.php';
        $notification = new Notification();
        $notification->order_id = $orderId;
        $notification->type = $type;
        $notification->status = 'pending';
        $notification->message = $message;
        if ($notification->create()) {
            // Send notification
            if ($type === 'email') {
                // Fetch user email by orderId (not implemented here)
                // $to = ...
                // $this->sendEmail($to, "Order Update", $message);
            } elseif ($type === 'whatsapp') {
                // Fetch user phone number by orderId (not implemented here)
                // $phoneNumber = ...
                // $this->sendWhatsApp($phoneNumber, $message);
            }
            // Update notification status to sent (not implemented here)
            return true;
        }
        return false;
    }
}
?>
