<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../services/BackupService.php';
require_once __DIR__ . '/../services/NotificationService.php';

class BackupController extends Controller {
    private $backupService;
    private $notificationService;

    public function __construct() {
        parent::__construct();
        $this->backupService = new BackupService($this->currentUser);
        $this->notificationService = new NotificationService();
    }

    public function index() {
        $this->requirePermission('view_backups');

        $query = "SELECT bl.*, rl.restored_by, rl.status as restore_status, rl.completed_at as last_restored
                 FROM backup_log bl
                 LEFT JOIN (
                     SELECT backup_id, restored_by, status, completed_at,
                            ROW_NUMBER() OVER (PARTITION BY backup_id ORDER BY completed_at DESC) as rn
                     FROM restore_log
                 ) rl ON bl.backup_id = rl.backup_id AND rl.rn = 1
                 ORDER BY bl.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $backups = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get backup schedules
        $query = "SELECT * FROM backup_schedule ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get storage locations
        $query = "SELECT * FROM backup_storage ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $storageLocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [
            'pageTitle' => 'Backup Management',
            'backups' => $backups,
            'schedules' => $schedules,
            'storageLocations' => $storageLocations
        ];

        require_once __DIR__ . '/../views/backup/index.php';
    }

    public function createBackup() {
        $this->requirePermission('manage_backups');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['type'])) {
                throw new Exception('Invalid request data');
            }

            $result = $this->backupService->createBackup($data['type']);

            // Notify admins
            $this->notificationService->sendAdminNotification('backup_created', [
                'type' => $data['type'],
                'filename' => $result['filename'],
                'size' => $this->formatSize($result['size']),
                'created_by' => $this->currentUser->username
            ]);

            $this->respondWithJson([
                'message' => 'Backup created successfully',
                'data' => $result
            ]);

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function restore() {
        $this->requirePermission('restore_backups');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['backup_id'])) {
                throw new Exception('Invalid request data');
            }

            // Get backup details
            $query = "SELECT * FROM backup_log WHERE backup_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$data['backup_id']]);
            $backup = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$backup) {
                throw new Exception('Backup not found');
            }

            // Get backup file path
            $query = "SELECT path FROM backup_locations 
                     WHERE backup_id = ? AND status = 'synced'
                     ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$data['backup_id']]);
            $location = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$location) {
                throw new Exception('Backup file not found');
            }

            $result = $this->backupService->restore($location['path'], [
                'skip_database' => $data['skip_database'] ?? false,
                'skip_files' => $data['skip_files'] ?? false
            ]);

            // Notify admins
            $this->notificationService->sendAdminNotification('backup_restored', [
                'backup_id' => $data['backup_id'],
                'restored_by' => $this->currentUser->username,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            $this->respondWithJson([
                'message' => 'Backup restored successfully',
                'data' => $result
            ]);

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function download() {
        $this->requirePermission('view_backups');

        try {
            $backupId = $_GET['id'] ?? null;
            if (!$backupId) {
                throw new Exception('Backup ID is required');
            }

            // Get backup details
            $query = "SELECT * FROM backup_log WHERE backup_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$backupId]);
            $backup = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$backup) {
                throw new Exception('Backup not found');
            }

            // Get file path
            $query = "SELECT path FROM backup_locations 
                     WHERE backup_id = ? AND status = 'synced'
                     ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$backupId]);
            $location = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$location || !file_exists($location['path'])) {
                throw new Exception('Backup file not found');
            }

            // Stream file to browser
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($location['path']) . '"');
            header('Content-Length: ' . filesize($location['path']));
            header('Cache-Control: no-cache');

            readfile($location['path']);
            exit;

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function updateSchedule() {
        $this->requirePermission('manage_backup_schedule');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['id'])) {
                throw new Exception('Invalid request data');
            }

            $query = "UPDATE backup_schedule SET
                    frequency = ?,
                    time_of_day = ?,
                    day_of_week = ?,
                    day_of_month = ?,
                    retention_days = ?,
                    is_active = ?
                    WHERE id = ?";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $data['frequency'],
                $data['time_of_day'],
                $data['day_of_week'],
                $data['day_of_month'],
                $data['retention_days'],
                $data['is_active'] ? 1 : 0,
                $data['id']
            ]);

            $this->respondWithJson([
                'message' => 'Backup schedule updated successfully'
            ]);

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function addStorage() {
        $this->requirePermission('manage_backups');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['name']) || !isset($data['type'])) {
                throw new Exception('Invalid request data');
            }

            $query = "INSERT INTO backup_storage 
                    (name, type, config, created_by)
                    VALUES (?, ?, ?, ?)";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $data['name'],
                $data['type'],
                json_encode($data['config']),
                $this->currentUser->username
            ]);

            $this->respondWithJson([
                'message' => 'Storage location added successfully',
                'id' => $this->conn->lastInsertId()
            ]);

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function deleteBackup() {
        $this->requirePermission('manage_backups');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['backup_id'])) {
                throw new Exception('Invalid request data');
            }

            // Get backup locations
            $query = "SELECT * FROM backup_locations WHERE backup_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$data['backup_id']]);
            $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Delete physical files
            foreach ($locations as $location) {
                if (file_exists($location['path'])) {
                    unlink($location['path']);
                }
            }

            // Delete database records
            $this->conn->beginTransaction();

            try {
                // Delete locations
                $query = "DELETE FROM backup_locations WHERE backup_id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$data['backup_id']]);

                // Delete restore logs
                $query = "DELETE FROM restore_log WHERE backup_id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$data['backup_id']]);

                // Delete backup log
                $query = "DELETE FROM backup_log WHERE backup_id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$data['backup_id']]);

                $this->conn->commit();

                $this->respondWithJson([
                    'message' => 'Backup deleted successfully'
                ]);

            } catch (Exception $e) {
                $this->conn->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    private function formatSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
?>
