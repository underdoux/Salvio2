<?php

class SettingsDependency {
    private static $instance = null;
    private $db;
    private $validator;
    private $auditLogger;

    private function __construct() {
        $this->db = Database::getInstance();
        $this->validator = SettingsValidator::getInstance();
        $this->auditLogger = AuditLogger::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Add a dependency between settings
     */
    public function addDependency($settingId, $dependsOnId, $type, $value = null) {
        try {
            $sql = "INSERT INTO setting_dependencies 
                    (setting_id, depends_on_setting_id, condition_type, condition_value)
                    VALUES (?, ?, ?, ?)";
            
            $this->db->query($sql, [$settingId, $dependsOnId, $type, $value]);
            
            $this->auditLogger->log('dependency_added', [
                'setting_id' => $settingId,
                'depends_on' => $dependsOnId,
                'type' => $type
            ]);

            return true;
        } catch (Exception $e) {
            $this->auditLogger->log('dependency_add_failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Validate dependencies for a setting
     */
    public function validateDependencies($settingId, $value) {
        $dependencies = $this->getDependencies($settingId);
        $valid = true;

        foreach ($dependencies as $dep) {
            $dependentValue = $this->getSettingValue($dep['depends_on_setting_id']);
            
            switch ($dep['condition_type']) {
                case 'required':
                    if (empty($dependentValue)) {
                        $valid = false;
                    }
                    break;
                    
                case 'conflicts':
                    if ($dependentValue == $dep['condition_value']) {
                        $valid = false;
                    }
                    break;
                    
                case 'affects':
                    $this->updateAffectedSetting($dep['depends_on_setting_id'], $value);
                    break;
            }
        }

        return $valid;
    }

    /**
     * Get dependencies for a setting
     */
    private function getDependencies($settingId) {
        $sql = "SELECT * FROM setting_dependencies WHERE setting_id = ?";
        return $this->db->query($sql, [$settingId]);
    }

    /**
     * Get setting value
     */
    private function getSettingValue($settingId) {
        $sql = "SELECT value FROM settings WHERE id = ?";
        $result = $this->db->query($sql, [$settingId]);
        return $result[0]['value'] ?? null;
    }

    /**
     * Update affected setting
     */
    private function updateAffectedSetting($settingId, $triggerValue) {
        // Implementation would depend on specific business rules
        // This is a placeholder for the actual logic
    }

    /**
     * Perform bulk update of settings
     */
    public function bulkUpdate($settings, $userId) {
        $this->db->beginTransaction();
        
        try {
            $total = count($settings);
            $success = 0;
            $errors = [];

            foreach ($settings as $setting) {
                if ($this->validateDependencies($setting['id'], $setting['value'])) {
                    if ($this->updateSetting($setting['id'], $setting['value'])) {
                        $success++;
                    } else {
                        $errors[] = "Failed to update setting {$setting['id']}";
                    }
                } else {
                    $errors[] = "Dependency validation failed for setting {$setting['id']}";
                }
            }

            $this->logBulkUpdate($userId, $total, $success, $errors);
            
            if (empty($errors)) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollback();
            $this->auditLogger->log('bulk_update_failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Import settings from file
     */
    public function importSettings($file, $userId) {
        try {
            $settings = json_decode(file_get_contents($file), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON format');
            }

            $total = count($settings);
            $success = 0;
            $errors = [];

            $this->db->beginTransaction();

            foreach ($settings as $setting) {
                if ($this->validateSetting($setting)) {
                    if ($this->importSetting($setting)) {
                        $success++;
                    } else {
                        $errors[] = "Failed to import setting {$setting['name']}";
                    }
                } else {
                    $errors[] = "Validation failed for setting {$setting['name']}";
                }
            }

            $this->logImport($userId, basename($file), $total, $success, $errors);

            if (empty($errors)) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollback();
            $this->auditLogger->log('import_failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Export settings to file
     */
    public function exportSettings($format = 'json') {
        $sql = "SELECT * FROM settings";
        $settings = $this->db->query($sql);

        switch ($format) {
            case 'json':
                return json_encode($settings, JSON_PRETTY_PRINT);
            case 'csv':
                return $this->exportToCsv($settings);
            default:
                throw new Exception('Unsupported export format');
        }
    }

    /**
     * Create setting preset
     */
    public function createPreset($name, $description, $settings) {
        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO setting_presets (name, description) VALUES (?, ?)";
            $this->db->query($sql, [$name, $description]);
            $presetId = $this->db->lastInsertId();

            foreach ($settings as $settingId => $value) {
                $sql = "INSERT INTO setting_preset_values 
                        (preset_id, setting_id, value) VALUES (?, ?, ?)";
                $this->db->query($sql, [$presetId, $settingId, $value]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            $this->auditLogger->log('preset_creation_failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Apply preset to settings
     */
    public function applyPreset($presetId) {
        try {
            $this->db->beginTransaction();

            $sql = "SELECT * FROM setting_preset_values WHERE preset_id = ?";
            $values = $this->db->query($sql, [$presetId]);

            foreach ($values as $value) {
                $sql = "UPDATE settings SET value = ? WHERE id = ?";
                $this->db->query($sql, [$value['value'], $value['setting_id']]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            $this->auditLogger->log('preset_apply_failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Log bulk update
     */
    private function logBulkUpdate($userId, $total, $success, $errors) {
        $sql = "INSERT INTO setting_bulk_update_logs 
                (user_id, settings_count, success_count, error_details)
                VALUES (?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $userId,
            $total,
            $success,
            !empty($errors) ? json_encode($errors) : null
        ]);
    }

    /**
     * Log import
     */
    private function logImport($userId, $fileName, $total, $success, $errors) {
        $sql = "INSERT INTO setting_import_logs 
                (user_id, file_name, status, settings_count, success_count, error_details)
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $status = empty($errors) ? 'success' : 
                 ($success > 0 ? 'partial' : 'failed');

        $this->db->query($sql, [
            $userId,
            $fileName,
            $status,
            $total,
            $success,
            !empty($errors) ? json_encode($errors) : null
        ]);
    }

    /**
     * Export to CSV format
     */
    private function exportToCsv($settings) {
        $output = fopen('php://temp', 'r+');
        
        // Header
        fputcsv($output, ['id', 'name', 'value', 'type']);
        
        // Data
        foreach ($settings as $setting) {
            fputcsv($output, [
                $setting['id'],
                $setting['name'],
                $setting['value'],
                $setting['type']
            ]);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Validate setting
     */
    private function validateSetting($setting) {
        return $this->validator->validate($setting);
    }

    /**
     * Update single setting
     */
    private function updateSetting($id, $value) {
        $sql = "UPDATE settings SET value = ? WHERE id = ?";
        return $this->db->query($sql, [$value, $id]);
    }

    /**
     * Import single setting
     */
    private function importSetting($setting) {
        $sql = "INSERT INTO settings (name, value, type) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE value = ?, type = ?";
        
        return $this->db->query($sql, [
            $setting['name'],
            $setting['value'],
            $setting['type'],
            $setting['value'],
            $setting['type']
        ]);
    }
}
