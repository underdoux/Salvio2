<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/NotificationLog.php';
require_once __DIR__ . '/../models/NotificationQueue.php';

class NotificationCleanup {
    private $notificationLog;
    private $notificationQueue;
    private $verbose;
    private $dryRun;

    public function __construct($verbose = false, $dryRun = false) {
        $this->notificationLog = new NotificationLog();
        $this->notificationQueue = new NotificationQueue();
        $this->verbose = $verbose;
        $this->dryRun = $dryRun;
    }

    public function run() {
        $this->log("Starting notification cleanup process");
        
        if ($this->dryRun) {
            $this->log("DRY RUN MODE - No actual deletions will be performed");
        }

        try {
            // Clean up old notification logs
            $this->cleanupLogs();

            // Clean up completed/failed queue entries
            $this->cleanupQueue();

            // Generate cleanup report
            $this->generateReport();

        } catch (Exception $e) {
            $this->log("Error during cleanup: " . $e->getMessage(), 'ERROR');
            exit(1);
        }

        $this->log("Cleanup process completed successfully");
    }

    private function cleanupLogs() {
        $this->log("Cleaning up notification logs...");

        try {
            // Get count before cleanup
            $beforeCount = $this->getLogCount();

            if (!$this->dryRun) {
                // Delete logs older than 90 days
                $deleted = $this->notificationLog->deleteOldLogs(90);
            }

            // Get count after cleanup
            $afterCount = $this->getLogCount();
            $deletedCount = $beforeCount - $afterCount;

            $this->log("Notification logs cleanup complete:");
            $this->log("- Before: $beforeCount records");
            $this->log("- After: $afterCount records");
            $this->log("- Deleted: $deletedCount records");

        } catch (Exception $e) {
            throw new Exception("Failed to cleanup notification logs: " . $e->getMessage());
        }
    }

    private function cleanupQueue() {
        $this->log("Cleaning up notification queue...");

        try {
            // Get count before cleanup
            $beforeCount = $this->getQueueCount();

            if (!$this->dryRun) {
                // Delete completed/failed entries older than 30 days
                $deleted = $this->notificationQueue->cleanupOldRecords(30);
            }

            // Get count after cleanup
            $afterCount = $this->getQueueCount();
            $deletedCount = $beforeCount - $afterCount;

            $this->log("Notification queue cleanup complete:");
            $this->log("- Before: $beforeCount records");
            $this->log("- After: $afterCount records");
            $this->log("- Deleted: $deletedCount records");

        } catch (Exception $e) {
            throw new Exception("Failed to cleanup notification queue: " . $e->getMessage());
        }
    }

    private function generateReport() {
        $this->log("\nGenerating cleanup report...");

        // Get current queue stats
        $queueStats = $this->notificationQueue->getQueueStats();
        
        $this->log("\nCurrent Queue Status:");
        $this->log("- Total entries: {$queueStats['total']}");
        $this->log("- Pending: {$queueStats['pending']}");
        $this->log("- Processing: {$queueStats['processing']}");
        $this->log("- Completed: {$queueStats['completed']}");
        $this->log("- Failed: {$queueStats['failed']}");
        
        if ($queueStats['avg_processing_time']) {
            $avgTime = round($queueStats['avg_processing_time'], 2);
            $this->log("- Average processing time: {$avgTime} seconds");
        }

        // Get failure statistics
        $failureStats = $this->notificationQueue->getFailureStats();
        if (!empty($failureStats)) {
            $this->log("\nRecent Failures:");
            foreach ($failureStats as $stat) {
                $this->log("- {$stat['error_message']} ({$stat['count']} occurrences)");
                $this->log("  Last occurred: {$stat['last_occurrence']}");
            }
        }

        // Get queue statistics by channel
        $channelStats = $this->notificationQueue->getQueueByChannel();
        if (!empty($channelStats)) {
            $this->log("\nChannel Statistics:");
            foreach ($channelStats as $stat) {
                $this->log("- {$stat['channel']}:");
                $this->log("  Total: {$stat['total']}");
                $this->log("  Success Rate: " . 
                    round(($stat['completed'] / $stat['total']) * 100, 2) . "%");
                if ($stat['avg_processing_time']) {
                    $avgTime = round($stat['avg_processing_time'], 2);
                    $this->log("  Avg. Processing Time: {$avgTime} seconds");
                }
            }
        }
    }

    private function getLogCount() {
        $database = new Database();
        $conn = $database->getConnection();
        $stmt = $conn->query("SELECT COUNT(*) as count FROM notification_logs");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    private function getQueueCount() {
        $database = new Database();
        $conn = $database->getConnection();
        $stmt = $conn->query("SELECT COUNT(*) as count FROM notification_queue");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
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
$options = getopt('', ['verbose::', 'dry-run::']);
$verbose = isset($options['verbose']);
$dryRun = isset($options['dry-run']);

// Create and run cleanup
$cleanup = new NotificationCleanup($verbose, $dryRun);
$cleanup->run();

?>
