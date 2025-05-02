<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class NotificationService {
    private $mailer;
    private $whatsappClient;
    private $settings;

    public function __construct() {
        $this->initializeMailer();
        $this->initializeWhatsApp();
        $this->loadSettings();
    }

    private function initializeMailer() {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $_ENV['SMTP_HOST'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $_ENV['SMTP_USERNAME'];
            $this->mailer->Password = $_ENV['SMTP_PASSWORD'];
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $_ENV['SMTP_PORT'];
            
            // Default settings
            $this->mailer->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $this->mailer->isHTML(true);
        } catch (Exception $e) {
            error_log("Error initializing mailer: " . $e->getMessage());
        }
    }

    private function initializeWhatsApp() {
        $this->whatsappClient = new WhatsAppClient(
            $_ENV['WHATSAPP_API_KEY'],
            $_ENV['WHATSAPP_API_SECRET']
        );
    }

    private function loadSettings() {
        $settingModel = new Setting();
        $this->settings = [
            'notifications_enabled' => $settingModel->get('notifications_enabled', true),
            'email_notifications' => $settingModel->get('email_notifications', true),
            'whatsapp_notifications' => $settingModel->get('whatsapp_notifications', true),
            'notification_templates' => $settingModel->get('notification_templates', [])
        ];
    }

    public function sendOrderNotification($order, $type) {
        if (!$this->settings['notifications_enabled']) return;

        $template = $this->getTemplate('order_' . $type);
        if (!$template) return;

        $data = $this->prepareOrderData($order);
        
        // Send to customer
        if ($order->customer_email && $this->settings['email_notifications']) {
            $this->sendEmail(
                $order->customer_email,
                $this->replacePlaceholders($template['email']['subject'], $data),
                $this->replacePlaceholders($template['email']['body'], $data)
            );
        }

        if ($order->customer_phone && $this->settings['whatsapp_notifications']) {
            $this->sendWhatsApp(
                $order->customer_phone,
                $this->replacePlaceholders($template['whatsapp']['message'], $data)
            );
        }

        // Send to admins
        $this->notifyAdmins('order_' . $type, $data);
    }

    public function sendCommissionNotification($commission, $type) {
        if (!$this->settings['notifications_enabled']) return;

        $template = $this->getTemplate('commission_' . $type);
        if (!$template) return;

        $data = $this->prepareCommissionData($commission);
        
        // Send to sales person
        if ($commission->user_email && $this->settings['email_notifications']) {
            $this->sendEmail(
                $commission->user_email,
                $this->replacePlaceholders($template['email']['subject'], $data),
                $this->replacePlaceholders($template['email']['body'], $data)
            );
        }

        if ($commission->user_phone && $this->settings['whatsapp_notifications']) {
            $this->sendWhatsApp(
                $commission->user_phone,
                $this->replacePlaceholders($template['whatsapp']['message'], $data)
            );
        }

        // Send to admins
        $this->notifyAdmins('commission_' . $type, $data);
    }

    public function sendProfitDistributionNotification($distribution, $type) {
        if (!$this->settings['notifications_enabled']) return;

        $template = $this->getTemplate('profit_' . $type);
        if (!$template) return;

        $data = $this->prepareProfitData($distribution);
        
        // Send to investors
        foreach ($distribution->shares as $share) {
            $investorData = array_merge($data, [
                'investor_name' => $share->investor_name,
                'share_percentage' => $share->percentage,
                'share_amount' => $share->amount
            ]);

            if ($share->investor_email && $this->settings['email_notifications']) {
                $this->sendEmail(
                    $share->investor_email,
                    $this->replacePlaceholders($template['email']['subject'], $investorData),
                    $this->replacePlaceholders($template['email']['body'], $investorData)
                );
            }

            if ($share->investor_phone && $this->settings['whatsapp_notifications']) {
                $this->sendWhatsApp(
                    $share->investor_phone,
                    $this->replacePlaceholders($template['whatsapp']['message'], $investorData)
                );
            }
        }

        // Send to admins
        $this->notifyAdmins('profit_' . $type, $data);
    }

    private function sendEmail($to, $subject, $body) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $this->renderEmailTemplate($body);
            $this->mailer->send();

            $this->logNotification('email', $to, $subject);
        } catch (Exception $e) {
            error_log("Failed to send email to {$to}: " . $e->getMessage());
            throw new Exception("Failed to send email notification");
        }
    }

    private function sendWhatsApp($to, $message) {
        try {
            $response = $this->whatsappClient->sendMessage([
                'to' => $this->formatPhoneNumber($to),
                'message' => $message
            ]);

            if (!$response->success) {
                throw new Exception($response->message);
            }

            $this->logNotification('whatsapp', $to, $message);
        } catch (Exception $e) {
            error_log("Failed to send WhatsApp message to {$to}: " . $e->getMessage());
            throw new Exception("Failed to send WhatsApp notification");
        }
    }

    private function notifyAdmins($type, $data) {
        $userModel = new User();
        $admins = $userModel->getAdminsWithNotificationPreference($type);

        foreach ($admins as $admin) {
            $template = $this->getTemplate($type . '_admin');
            if (!$template) continue;

            if ($admin->email && $admin->email_notifications) {
                $this->sendEmail(
                    $admin->email,
                    $this->replacePlaceholders($template['email']['subject'], $data),
                    $this->replacePlaceholders($template['email']['body'], $data)
                );
            }

            if ($admin->phone && $admin->whatsapp_notifications) {
                $this->sendWhatsApp(
                    $admin->phone,
                    $this->replacePlaceholders($template['whatsapp']['message'], $data)
                );
            }
        }
    }

    private function getTemplate($type) {
        return $this->settings['notification_templates'][$type] ?? null;
    }

    private function prepareOrderData($order) {
        return [
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'total_amount' => $this->formatCurrency($order->total),
            'status' => $this->getStatusLabel($order->status),
            'created_at' => $this->formatDate($order->created_at),
            'items_count' => count($order->items),
            'payment_status' => $this->getPaymentStatusLabel($order->payment_status),
            'tracking_url' => $this->generateTrackingUrl($order->id)
        ];
    }

    private function prepareCommissionData($commission) {
        return [
            'sales_person' => $commission->user_name,
            'order_number' => $commission->order_number,
            'commission_amount' => $this->formatCurrency($commission->amount),
            'status' => $this->getStatusLabel($commission->status),
            'created_at' => $this->formatDate($commission->created_at),
            'payment_date' => $commission->payment_date ? $this->formatDate($commission->payment_date) : 'Not yet paid'
        ];
    }

    private function prepareProfitData($distribution) {
        return [
            'period' => $this->formatPeriod($distribution->period_start, $distribution->period_end),
            'total_profit' => $this->formatCurrency($distribution->net_profit),
            'status' => $this->getStatusLabel($distribution->status),
            'calculated_at' => $this->formatDate($distribution->calculated_at),
            'distributed_at' => $distribution->distributed_at ? $this->formatDate($distribution->distributed_at) : 'Not yet distributed'
        ];
    }

    private function replacePlaceholders($template, $data) {
        foreach ($data as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        return $template;
    }

    private function renderEmailTemplate($body) {
        ob_start();
        include __DIR__ . '/../views/emails/layout.php';
        return ob_get_clean();
    }

    private function formatPhoneNumber($phone) {
        // Remove any non-numeric characters
        $number = preg_replace('/[^0-9]/', '', $phone);
        
        // Ensure number starts with country code
        if (substr($number, 0, 2) !== '62') {
            $number = '62' . ltrim($number, '0');
        }
        
        return $number;
    }

    private function formatCurrency($amount) {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    private function formatDate($date) {
        return date('j F Y H:i', strtotime($date));
    }

    private function formatPeriod($start, $end) {
        $startDate = date('j F Y', strtotime($start));
        $endDate = date('j F Y', strtotime($end));
        return "{$startDate} - {$endDate}";
    }

    private function getStatusLabel($status) {
        $labels = [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'paid' => 'Paid',
            'distributed' => 'Distributed'
        ];
        return $labels[$status] ?? ucfirst($status);
    }

    private function getPaymentStatusLabel($status) {
        $labels = [
            'pending' => 'Pending',
            'partial' => 'Partially Paid',
            'paid' => 'Paid',
            'overdue' => 'Overdue',
            'refunded' => 'Refunded'
        ];
        return $labels[$status] ?? ucfirst($status);
    }

    private function generateTrackingUrl($orderId) {
        return $_ENV['APP_URL'] . '/orders/' . $orderId;
    }

    private function logNotification($channel, $recipient, $content) {
        $log = new NotificationLog();
        $log->create([
            'channel' => $channel,
            'recipient' => $recipient,
            'content' => $content,
            'sent_at' => date('Y-m-d H:i:s')
        ]);
    }
}

class WhatsAppClient {
    private $apiKey;
    private $apiSecret;
    private $baseUrl = 'https://graph.facebook.com/v17.0';

    public function __construct($apiKey, $apiSecret) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    public function sendMessage($data) {
        $url = "{$this->baseUrl}/messages";
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $data['to'],
            'type' => 'text',
            'text' => [
                'body' => $data['message']
            ]
        ];

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("WhatsApp API request failed with status {$httpCode}");
        }

        return json_decode($response);
    }
}
