<?php

class SettingsController extends BaseController {
    private $settingsManager;
    private $validator;
    private $auditLogger;

    public function __construct() {
        parent::__construct();
        $this->settingsManager = SettingsManager::getInstance();
        $this->validator = Validator::getInstance();
        $this->auditLogger = AuditLogger::getInstance();
    }

    /**
     * Display settings index
     */
    public function index() {
        $this->render('settings/index', [
            'settings' => $this->getAllSettings()
        ]);
    }

    /**
     * Display bulk settings management
     */
    public function bulk() {
        $settings = $this->getAllSettings();
        
        // Add dependencies information
        foreach ($settings as $key => &$setting) {
            $setting['dependencies'] = $this->settingsManager->getDependencies($key);
        }

        $this->render('settings/bulk', [
            'settings' => $settings
        ]);
    }

    /**
     * Handle bulk settings update
     */
    public function bulkUpdate() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                throw new Exception('Invalid request data');
            }

            $this->settingsManager->bulkUpdate($data, $_SESSION['user_id']);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Export settings
     */
    public function export() {
        try {
            $format = $_GET['format'] ?? 'json';
            
            if (!in_array($format, ['json', 'csv'])) {
                throw new Exception('Invalid export format');
            }

            $data = $this->settingsManager->exportSettings($format);
            
            // Set appropriate headers
            $contentType = $format === 'json' ? 'application/json' : 'text/csv';
            header("Content-Type: {$contentType}");
            header('Content-Disposition: attachment; filename="settings.' . $format . '"');
            
            echo $data;
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Import settings
     */
    public function import() {
        try {
            if (!isset($_FILES['file'])) {
                throw new Exception('No file uploaded');
            }

            $file = $_FILES['file'];
            $format = $_POST['format'] ?? 'json';
            
            if (!in_array($format, ['json', 'csv'])) {
                throw new Exception('Invalid import format');
            }

            $data = file_get_contents($file['tmp_name']);
            $this->settingsManager->importSettings($data, $format, $_SESSION['user_id']);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Edit setting
     */
    public function edit($key) {
        $setting = $this->getSetting($key);
        
        if (!$setting) {
            $this->redirect('/settings');
            return;
        }

        // Add dependencies information
        $setting['dependencies'] = $this->settingsManager->getDependencies($key);

        $this->render('settings/edit', [
            'setting' => $setting
        ]);
    }

    /**
     * Update setting
     */
    public function update($key) {
        try {
            $value = $_POST['value'] ?? null;
            
            if ($value === null) {
                throw new Exception('No value provided');
            }

            // Validate dependencies
            $errors = $this->settingsManager->validateDependencies($key, $value);
            if (!empty($errors)) {
                throw new Exception(implode(", ", $errors));
            }

            // Validate value
            if (!$this->validator->validate($key, $value)) {
                throw new Exception('Invalid value');
            }

            $this->settingsManager->bulkUpdate([$key => $value], $_SESSION['user_id']);
            
            $this->redirect('/settings');
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            $this->redirect("/settings/edit/{$key}");
        }
    }

    /**
     * Get all settings
     */
    private function getAllSettings() {
        $query = "SELECT * FROM settings ORDER BY `key`";
        return Database::getInstance()->query($query);
    }

    /**
     * Get single setting
     */
    private function getSetting($key) {
        $query = "SELECT * FROM settings WHERE `key` = ?";
        $result = Database::getInstance()->query($query, [$key]);
        return $result[0] ?? null;
    }
}
