<?php

class SettingsManager {
    private static $instance = null;
    private $db;
    private $validator;
    private $auditLogger;
    private $cache;

    private function __construct() {
        $this->db = Database::getInstance();
        $this->validator = Validator::getInstance();
        $this->auditLogger = AuditLogger::getInstance();
        $this->cache = Cache::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Define setting dependencies
     */
    private $dependencies = [
        'smtp' => [
            'email.host',
            'email.port',
            'email.encryption',
            'email.username',
            'email.password'
        ],
        'tax' => [
            'tax.enabled',
            'tax.rate',
            'tax.number_format'
        ],
        'commission' => [
            'commission.global_rate',
            'commission.category_rates',
            'commission.product_rates'
        ],
        'payment' => [
            'payment.gateway',
            'payment.api_key',
            'payment.secret'
        ]
    ];

    /**
     * Get dependent settings
     */
    public function getDependencies($key) {
        foreach ($this->dependencies as $group => $settings) {
            if (in_array($key, $settings)) {
                return array_diff($settings, [$key]);
            }
        }
        return [];
    }

    /**
     * Validate dependencies before update
     */
    public function validateDependencies($key, $value) {
        $dependencies = $this->getDependencies($key);
        $errors = [];

        foreach ($dependencies as $dep) {
            $depValue = $this->getSetting($dep);
            if (!$this->validateDependencyPair($key, $value, $dep, $depValue)) {
                $errors[] = "Invalid dependency: {$dep}";
            }
        }

        return $errors;
    }

    /**
     * Bulk update settings
     */
    public function bulkUpdate($settings, $userId) {
        try {
            $this->db->beginTransaction();

            foreach ($settings as $key => $value) {
                // Validate dependencies
                $errors = $this->validateDependencies($key, $value);
                if (!empty($errors)) {
                    throw new Exception(implode(", ", $errors));
                }

                // Validate value
                if (!$this->validator->validate($key, $value)) {
                    throw new Exception("Invalid value for {$key}");
                }

                // Update setting
                $this->updateSetting($key, $value, $userId);
            }

            $this->db->commit();
            $this->cache->clear('settings');
            return true;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Export settings
     */
    public function exportSettings($format = 'json') {
        $settings = $this->getAllSettings();
        
        switch ($format) {
            case 'json':
                return json_encode($settings, JSON_PRETTY_PRINT);
            
            case 'csv':
                $csv = "key,value,type\n";
                foreach ($settings as $key => $data) {
                    $csv .= "{$key},{$data['value']},{$data['type']}\n";
                }
                return $csv;
            
            default:
                throw new Exception("Unsupported format: {$format}");
        }
    }

    /**
     * Import settings
     */
    public function importSettings($data, $format = 'json', $userId) {
        try {
            $settings = $this->parseImportData($data, $format);
            return $this->bulkUpdate($settings, $userId);
        } catch (Exception $e) {
            throw new Exception("Import failed: " . $e->getMessage());
        }
    }

    /**
     * Parse import data
     */
    private function parseImportData($data, $format) {
        switch ($format) {
            case 'json':
                $settings = json_decode($data, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Invalid JSON format");
                }
                return $settings;
            
            case 'csv':
                $settings = [];
                $rows = str_getcsv($data, "\n");
                foreach ($rows as $i => $row) {
                    if ($i === 0) continue; // Skip header
                    $cols = str_getcsv($row);
                    if (count($cols) >= 2) {
                        $settings[$cols[0]] = [
                            'value' => $cols[1],
                            'type' => $cols[2] ?? 'string'
                        ];
                    }
                }
                return $settings;
            
            default:
                throw new Exception("Unsupported format: {$format}");
        }
    }

    /**
     * Get all settings
     */
    private function getAllSettings() {
        $query = "SELECT * FROM settings";
        return $this->db->query($query);
    }

    /**
     * Get single setting
     */
    private function getSetting($key) {
        $query = "SELECT value FROM settings WHERE key = ?";
        $result = $this->db->query($query, [$key]);
        return $result[0]['value'] ?? null;
    }

    /**
     * Update single setting
     */
    private function updateSetting($key, $value, $userId) {
        $query = "UPDATE settings SET value = ?, updated_at = NOW(), updated_by = ? WHERE key = ?";
        $this->db->query($query, [$value, $userId, $key]);
        
        $this->auditLogger->logSettingChange($key, $this->getSetting($key), $value, [
            'user_id' => $userId,
            'action' => 'update'
        ]);
    }

    /**
     * Validate dependency pair
     */
    private function validateDependencyPair($key1, $value1, $key2, $value2) {
        // Add specific validation rules for different dependency types
        switch ($key1) {
            case 'email.encryption':
                if ($value1 === 'ssl' && $value2 !== '465') {
                    return false;
                }
                break;

            case 'tax.enabled':
                if ($value1 && empty($value2)) {
                    return false;
                }
                break;

            // Add more dependency validation rules as needed
        }

        return true;
    }
}
