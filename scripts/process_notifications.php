<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/NotificationQueue.php';
require_once __DIR__ . '/../services/NotificationService.php';

class NotificationProcessor {
    private $queue;
    private $notificationService;
    private $processLimit;
    private $sleepTime;
    private $running;
    private $verbose;

    public function __construct($processLimit = 50, $sleepTime = 5, $verbose = false) {
        $this->queue = new NotificationQueue();
        $this->notificationService = new NotificationService();
        $this->processLimit = $processLimit;
        $this->sleepTime = $sleepTime;
        $this->verbose = $verbose;
        $this->running = true;
    }

    public function run() {
        $this->log("Notification processor started");
        
        // Set up signal handlers
        pcntl_signal(SIGTERM, [$this, 'handleSignal']);
        pcntl_signal(SIGINT, [$this, 'handleSignal']);

        while ($this->running) {
            // Process signals
            pcntl_signal_dispatch();

            try {
                $notifications = $this->queue->getPendingNotifications();
                
                if (empty($notifications)) {
                    $this->log("No pending notifications, sleeping for {$this->sleepTime} seconds");
                    sleep($this->sleepTime);
                    continue;
                }

                $this->log("Processing " . count($notifications) . " notifications");

                foreach ($notifications as $notification) {
                    if (!$this->running) break;

                    $this->processNotification($notification);
                }

            } catch (Exception $e) {
                $this->log("Error processing notifications: " . $e->getMessage(), 'ERROR');
                sleep($this->sleepTime);
            }
        }

        $this->log("Notification processor stopped");
    }

    private function processNotification($notification) {
        $this->log("Processing notification ID: {$notification['id']}");

        try {
            // Mark as processing
            $this->queue->markAsProcessing($notification['id']);

            // Send notification based on channel
            switch ($notification['channel']) {
                case 'email':
                    $this->notificationService->sendEmail(
                        $notification['recipient'],
                        $notification['type'],
                        json_decode($notification['content'], true)
                    );
                    break;

                case 'whatsapp':
                    $this->notificationService->sendWhatsApp(
                        $notification['recipient'],
                        $notification['type'],
                        json_decode($notification['content'], true)
                    );
                    break;

                default:
                    throw new Exception("Unknown notification channel: {$notification['channel']}");
            }

            // Mark as completed
            $this->queue->markAsCompleted($notification['id']);
            $this->log("Successfully processed notification ID: {$notification['id']}");

        } catch (Exception $e) {
            $this->log("Failed to process notification ID: {$notification['id']} - " . $e->getMessage(), 'ERROR');
            $this->queue->markAsFailed($notification['id'], $e->getMessage());
        }
    }

    public function handleSignal($signal) {
        $this->log("Received signal: $signal");
        $this->running = false;
    }

    private function log($message, $level = 'INFO') {
        $datetime = date('Y-m-d H:i:s');
        $logMessage = "[$datetime] [$level] $message";
        
        if ($this->verbose) {
            echo $logMessage . PHP_EOL;
        }
        
        error_log($logMessage);
    }
}

// Parse command line arguments
$options = getopt('', ['limit:', 'sleep:', 'verbose::']);
$processLimit = isset($options['limit']) ? (int)$options['limit'] : 50;
$sleepTime = isset($options['sleep']) ? (int)$options['sleep'] : 5;
$verbose = isset($options['verbose']);

// Create and run processor
$processor = new NotificationProcessor($processLimit, $sleepTime, $verbose);
$processor->run();

?>
