<?php

require_once __DIR__ . '/Logger.php';

class Mailer {
    private $from;
    private $adminEmails;
    private $headers;

    public function __construct() {
        $this->from = 'noreply@salviopos.com';
        $this->adminEmails = [
            'admin@salviopos.com',
            'finance@salviopos.com'
        ];
        
        $this->headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->from,
            'X-Mailer: PHP/' . phpversion()
        ];
    }

    public function sendProfitCalculationNotification($data) {
        try {
            Logger::log("Preparing profit calculation notification email");
            
            $subject = $this->getEmailSubject($data);
            $message = $this->getEmailBody($data);
            
            foreach ($this->adminEmails as $to) {
                $success = mail(
                    $to,
                    $subject,
                    $message,
                    implode("\r\n", $this->headers)
                );
                
                if ($success) {
                    Logger::log("Profit calculation notification sent to: {$to}");
                } else {
                    Logger::log("Failed to send profit calculation notification to: {$to}");
                }
            }
            
            return true;
        } catch (Exception $e) {
            Logger::log("Error sending profit calculation notification: " . $e->getMessage());
            return false;
        }
    }

    private function getEmailSubject($data) {
        if ($data['status'] === 'success') {
            return "Monthly Profit Calculation Completed Successfully - " . date('F Y');
        } else {
            return "Monthly Profit Calculation Failed - " . date('F Y');
        }
    }

    private function getEmailBody($data) {
        $date = date('F Y');
        
        if ($data['status'] === 'success') {
            return "
                <html>
                <body>
                    <h2>Monthly Profit Calculation Report - {$date}</h2>
                    <p>The profit calculation for {$date} has been completed successfully.</p>
                    
                    <h3>Summary:</h3>
                    <ul>
                        <li>Total Net Profit: Rp " . number_format($data['total_profit'], 2) . "</li>
                        <li>Number of Distributions: {$data['distribution_count']}</li>
                        <li>Execution Time: {$data['execution_time']} seconds</li>
                    </ul>
                    
                    <p>Please review the distributions in the admin dashboard.</p>
                    
                    <p>Best regards,<br>Salvio POS System</p>
                </body>
                </html>
            ";
        } else {
            return "
                <html>
                <body>
                    <h2>Monthly Profit Calculation Failed - {$date}</h2>
                    <p style='color: red;'>The profit calculation for {$date} has failed.</p>
                    
                    <h3>Error Details:</h3>
                    <p>{$data['message']}</p>
                    
                    <h3>Stack Trace:</h3>
                    <pre>{$data['trace']}</pre>
                    
                    <p>Please investigate and resolve the issue.</p>
                    
                    <p>Best regards,<br>Salvio POS System</p>
                </body>
                </html>
            ";
        }
    }
}
