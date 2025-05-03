<?php

class Notification {
    private $db;
    private $settingsModel;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->settingsModel = new Settings();
    }

    public function createNotification($data) {
        try {
            $sql = "INSERT INTO notifications (
                        user_id,
                        type,
                        title,
                        message,
                        reference_type,
                        reference_id,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['user_id'],
                $data['type'],
                $data['title'],
                $data['message'],
                $data['reference_type'],
                $data['reference_id']
            ]);

            // Send email notification if email is configured
            $this->sendEmailNotification(
                $data['user_id'],
                $data['title'],
                $data['message']
            );

            return $this->db->lastInsertId();

        } catch (Exception $e) {
            Logger::log("Error creating notification: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    public function sendEmailNotification($userId, $subject, $message) {
        try {
            // Get user email
            $sql = "SELECT email FROM users WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $email = $stmt->fetchColumn();

            if (!$email) {
                Logger::log("No email found for user #{$userId}", 'WARNING');
                return false;
            }

            // Get email settings from database
            $smtpSettings = $this->settingsModel->get('smtp', null);
            $mailSettings = $this->settingsModel->get('mail', [
                'from_address' => 'noreply@example.com',
                'reply_to' => 'support@example.com'
            ]);

            // Basic email headers
            $headers = [
                'From: ' . $mailSettings['from_address'],
                'Reply-To: ' . $mailSettings['reply_to'],
                'X-Mailer: PHP/' . phpversion(),
                'Content-Type: text/html; charset=UTF-8'
            ];

            // If SMTP is configured, use it
            if ($smtpSettings && $smtpSettings['enabled']) {
                return $this->sendSMTPEmail(
                    $email,
                    $subject,
                    $this->formatEmailMessage($message),
                    $smtpSettings,
                    $mailSettings
                );
            }

            // Fallback to PHP mail()
            $result = mail(
                $email,
                $subject,
                $this->formatEmailMessage($message),
                implode("\r\n", $headers)
            );

            if ($result) {
                Logger::log("Email notification sent to {$email}");
                return true;
            } else {
                Logger::log("Failed to send email notification to {$email}", 'ERROR');
                return false;
            }

        } catch (Exception $e) {
            Logger::log("Error sending email notification: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    private function sendSMTPEmail($to, $subject, $message, $smtpSettings, $mailSettings) {
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $smtpSettings['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtpSettings['username'];
            $mail->Password = $smtpSettings['password'];
            $mail->SMTPSecure = $smtpSettings['encryption'];
            $mail->Port = $smtpSettings['port'];

            // Recipients
            $mail->setFrom($mailSettings['from_address']);
            $mail->addAddress($to);
            $mail->addReplyTo($mailSettings['reply_to']);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;

            $mail->send();
            Logger::log("SMTP email sent to {$to}");
            return true;

        } catch (Exception $e) {
            Logger::log("SMTP Error: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    private function formatEmailMessage($message) {
        // Basic HTML template for email
        return "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { padding: 20px; }
                    .footer { margin-top: 20px; font-size: 12px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    {$message}
                    <div class='footer'>
                        This is an automated message, please do not reply.
                    </div>
                </div>
            </body>
            </html>
        ";
    }
}
