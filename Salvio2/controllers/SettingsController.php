<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../services/SettingsService.php';

class SettingsController extends Controller {
    private $settingsService;

    public function __construct() {
        parent::__construct();
        $this->settingsService = new SettingsService($this->currentUser);
    }

    public function index() {
        $this->requirePermission('view_settings');
        
        $settings = $this->settingsService->getAll(
            null,
            !$this->currentUser->hasPermission('edit_settings')
        );

        require_once __DIR__ . '/../views/settings/index.php';
    }

    public function update() {
        $this->requirePermission('edit_settings');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception('Invalid request data');
            }

            $this->settingsService->bulkUpdate($data);

            $this->respondWithJson([
                'message' => 'Settings updated successfully',
                'requires_restart' => $this->checkIfRestartRequired($data)
            ]);

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function getAuditLog() {
        $this->requirePermission('view_settings');

        try {
            $category = $_GET['category'] ?? null;
            $name = $_GET['name'] ?? null;
            $limit = min((int)($_GET['limit'] ?? 100), 1000);

            $logs = $this->settingsService->getAuditLog($category, $name, $limit);
            $this->respondWithJson($logs);

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function restartSystem() {
        $this->requirePermission('edit_settings');

        try {
            // Log the restart request
            $this->logAction('system_restart', [
                'user_id' => $this->currentUser->id,
                'reason' => 'Settings change'
            ]);

            // Send notification to other admins
            $this->notifyAdmins('system_restart', [
                'user' => $this->currentUser->username,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            // Execute restart command
            $this->executeRestart();

            $this->respondWithJson([
                'message' => 'System restart initiated'
            ]);

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    private function checkIfRestartRequired($changedSettings) {
        foreach ($changedSettings as $category => $settings) {
            foreach ($settings as $name => $value) {
                $setting = $this->settingsService->get($category, $name);
                if ($setting['requires_restart'] ?? false) {
                    return true;
                }
            }
        }
        return false;
    }

    private function executeRestart() {
        // Get current process ID
        $pid = getmypid();

        // Create restart marker
        $markerFile = __DIR__ . '/../storage/restart.marker';
        file_put_contents($markerFile, date('Y-m-d H:i:s'));

        // Schedule restart after response is sent
        register_shutdown_function(function() use ($pid, $markerFile) {
            // Wait for the response to be sent
            sleep(1);

            // Execute restart command
            if (PHP_OS === 'WINNT') {
                // Windows
                pclose(popen('start /B php artisan system:restart', 'r'));
            } else {
                // Linux/Unix
                exec('nohup php artisan system:restart > /dev/null 2>&1 &');
            }
        });
    }

    private function notifyAdmins($type, $data) {
        $notificationService = new NotificationService();
        $notificationService->sendAdminNotification($type, $data);
    }

    private function logAction($action, $data) {
        $auditLog = new AuditLog();
        $auditLog->create([
            'user_id' => $data['user_id'],
            'action' => $action,
            'details' => json_encode($data),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
    }

    public function export() {
        $this->requirePermission('edit_settings');

        try {
            $settings = $this->settingsService->getAll();
            
            // Remove sensitive data
            foreach ($settings as &$category) {
                foreach ($category as $name => &$setting) {
                    if (strpos($name, 'password') !== false || 
                        strpos($name, 'secret') !== false || 
                        strpos($name, 'key') !== false) {
                        $setting['value'] = '********';
                    }
                }
            }

            $filename = 'settings_export_' . date('Y-m-d_His') . '.json';
            
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            echo json_encode($settings, JSON_PRETTY_PRINT);
            exit;

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }

    public function import() {
        $this->requirePermission('edit_settings');

        try {
            if (!isset($_FILES['settings_file'])) {
                throw new Exception('No file uploaded');
            }

            $file = $_FILES['settings_file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('File upload failed');
            }

            $content = file_get_contents($file['tmp_name']);
            $settings = json_decode($content, true);

            if (!$settings) {
                throw new Exception('Invalid settings file format');
            }

            // Validate settings before importing
            foreach ($settings as $category => $categorySettings) {
                foreach ($categorySettings as $name => $setting) {
                    if (!isset($setting['value'])) {
                        throw new Exception("Invalid setting format for {$category}.{$name}");
                    }
                }
            }

            // Import settings
            $this->settingsService->bulkUpdate($settings);

            $this->respondWithJson([
                'message' => 'Settings imported successfully',
                'requires_restart' => $this->checkIfRestartRequired($settings)
            ]);

        } catch (Exception $e) {
            $this->respondWithError($e->getMessage());
        }
    }
}
?>
