<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/BackupService.php';
require_once __DIR__ . '/../services/NotificationService.php';

class BackupRunner {
    private $db;
    private $backupService;
    private $notificationService;
    private $logFile;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->backupService = new BackupService();
        $this->notificationService = new NotificationService();
        $this->logFile = __DIR__ . '/../storage/logs/backup.log';

        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0777, true);
        }
    }

    public function run() {
        $this->log("Starting backup process");

        try {
            // Get due schedules
            $schedules = $this->getDueSchedules();
            if (empty($schedules)) {
                $this->log("No backups scheduled for now");
                return;
            }

            foreach ($schedules as $schedule) {
                $this->log("Processing schedule {$schedule['id']} ({$schedule['type']})");

                try {
                    // Create backup
                    $result = $this->backupService->createBackup($schedule['type']);

                    // Update schedule
                    $this->updateSchedule($schedule['id']);

                    // Clean old backups
                    $this->cleanOldBackups($schedule);

                    // Notify admins
                    $this->notifySuccess($schedule, $result);

                    $this->log("Backup completed successfully for schedule {$schedule['id']}");

                } catch (Exception $e) {
                    $this->log("Error processing schedule {$schedule['id']}: " . $e->getMessage(), 'ERROR');
                    $this->notifyError($schedule, $e->getMessage());
                }
            }

        } catch (Exception $e) {
            $this->log("Critical error: " . $e->getMessage(), 'ERROR');
            $this->notifyError(null, $e->getMessage());
        }
    }

    private function getDueSchedules() {
        $query = "SELECT * FROM backup_schedule 
                 WHERE is_active = 1 
                 AND (next_run IS NULL OR next_run <= NOW())";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function updateSchedule($scheduleId) {
        $query = "UPDATE backup_schedule 
                 SET last_run = NOW(),
                     next_run = CASE
                         WHEN frequency = 'daily' THEN
                             DATE_ADD(NOW(), INTERVAL 1 DAY)
                         WHEN frequency = 'weekly' THEN
                             DATE_ADD(NOW(), INTERVAL 1 WEEK)
                         WHEN frequency = 'monthly' THEN
                             DATE_ADD(NOW(), INTERVAL 1 MONTH)
                     END
                 WHERE id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$scheduleId]);
    }

    private function cleanOldBackups($schedule) {
        $query = "SELECT bl.* 
                 FROM backup_log bl
                 WHERE bl.type = ?
                 AND bl.created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                 AND NOT EXISTS (
                     SELECT 1 FROM restore_log rl 
                     WHERE rl.backup_id = bl.backup_id
                 )";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $schedule['type'],
            $schedule['retention_days']
        ]);
        $oldBackups = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($oldBackups as $backup) {
            try {
                $this->log("Cleaning old backup: {$backup['backup_id']}");
                
                // Delete backup files
                $query = "SELECT * FROM backup_locations WHERE backup_id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$backup['backup_id']]);
                $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($locations as $location) {
                    if (file_exists($location['path'])) {
                        unlink($location['path']);
                    }
                }

                // Delete database records
                $this->db->beginTransaction();

                $stmt = $this->db->prepare("DELETE FROM backup_locations WHERE backup_id = ?");
                $stmt->execute([$backup['backup_id']]);

                $stmt = $this->db->prepare("DELETE FROM backup_log WHERE backup_id = ?");
                $stmt->execute([$backup['backup_id']]);

                $this->db->commit();

            } catch (Exception $e) {
                $this->db->rollBack();
                $this->log("Error cleaning backup {$backup['backup_id']}: " . $e->getMessage(), 'ERROR');
            }
        }
    }

    private function notifySuccess($schedule, $result) {
        $this->notificationService->sendAdminNotification('backup_completed', [
            'schedule_id' => $schedule['id'],
            'type' => $schedule['type'],
            'filename' => $result['filename'],
            'size' => $this->formatSize($result['size']),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    private function notifyError($schedule, $error) {
        $data = [
            'error' => $error,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($schedule) {
            $data['schedule_id'] = $schedule['id'];
            $data['type'] = $schedule['type'];
        }

        $this->notificationService->sendAdminNotification('backup_failed', $data);
    }

    private function formatSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);

        if ($level === 'ERROR') {
            error_log($logMessage);
        }
    }
}

// Run backup process
$runner = new BackupRunner();
$runner->run();
?>
