<?php

class SettingsTemplate {
    private static $instance = null;
    private $db;
    private $validator;
    private $auditLogger;
    private $settingsManager;

    private function __construct() {
        $this->db = Database::getInstance();
        $this->validator = SettingsValidator::getInstance();
        $this->auditLogger = AuditLogger::getInstance();
        $this->settingsManager = SettingsManager::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Predefined templates
     */
    private $templates = [
        'default' => [
            'currency.code' => 'IDR',
            'currency.symbol' => 'Rp',
            'currency.decimals' => 2,
            'tax.enabled' => true,
            'tax.rate' => 11,
            'commission.enabled' => true,
            'commission.rate' => 5,
            'commission.period' => 'monthly'
        ],
        'minimal' => [
            'currency.code' => 'IDR',
            'currency.symbol' => 'Rp',
            'currency.decimals' => 0,
            'tax.enabled' => false,
            'commission.enabled' => false
        ],
        'enterprise' => [
            'currency.code' => 'IDR',
            'currency.symbol' => 'Rp',
            'currency.decimals' => 2,
            'tax.enabled' => true,
            'tax.rate' => 11,
            'commission.enabled' => true,
            'commission.rate' => 10,
            'commission.period' => 'weekly',
            'stock.tracking' => true,
            'stock.threshold' => 100,
            'stock.expiry_days' => 30
        ]
    ];

    /**
     * Apply template
     */
    public function applyTemplate($name, $userId) {
        if (!isset($this->templates[$name])) {
            throw new Exception("Template not found: {$name}");
        }

        try {
            // Create backup before applying template
            $this->createBackup("before_template_{$name}", $userId);

            // Apply template settings
            $this->settingsManager->bulkUpdate($this->templates[$name], $userId);

            $this->auditLogger->log('template_applied', [
                'template' => $name,
                'user_id' => $userId,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            return true;
        } catch (Exception $e) {
            $this->auditLogger->log('template_failed', [
                'template' => $name,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            throw $e;
        }
    }

    /**
     * Create backup
     */
    public function createBackup($name, $userId) {
        try {
            $settings = $this->settingsManager->getAllSettings();
            
            $backup = [
                'name' => $name,
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s'),
                'settings' => $settings
            ];

            $backupPath = $this->getBackupPath($name);
            if (!is_dir(dirname($backupPath))) {
                mkdir(dirname($backupPath), 0777, true);
            }

            file_put_contents(
                $backupPath,
                json_encode($backup, JSON_PRETTY_PRINT)
            );

            $this->auditLogger->log('backup_created', [
                'name' => $name,
                'user_id' => $userId,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            return true;
        } catch (Exception $e) {
            $this->auditLogger->log('backup_failed', [
                'name' => $name,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            throw $e;
        }
    }

    /**
     * Restore from backup
     */
    public function restoreBackup($name, $userId) {
        try {
            $backupPath = $this->getBackupPath($name);
            if (!file_exists($backupPath)) {
                throw new Exception("Backup not found: {$name}");
            }

            $backup = json_decode(file_get_contents($backupPath), true);
            if (!$backup || !isset($backup['settings'])) {
                throw new Exception("Invalid backup format");
            }

            // Create safety backup
            $this->createBackup("before_restore_{$name}", $userId);

            // Restore settings
            $this->settingsManager->bulkUpdate($backup['settings'], $userId);

            $this->auditLogger->log('backup_restored', [
                'name' => $name,
                'user_id' => $userId,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            return true;
        } catch (Exception $e) {
            $this->auditLogger->log('restore_failed', [
                'name' => $name,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            throw $e;
        }
    }

    /**
     * List available templates
     */
    public function listTemplates() {
        return array_keys($this->templates);
    }

    /**
     * List available backups
     */
    public function listBackups() {
        $backups = [];
        $backupDir = $this->getBackupPath('');
        
        if (is_dir($backupDir)) {
            foreach (glob($backupDir . '*.json') as $file) {
                $backup = json_decode(file_get_contents($file), true);
                if ($backup && isset($backup['name'])) {
                    $backups[] = [
                        'name' => $backup['name'],
                        'created_at' => $backup['created_at'],
                        'created_by' => $backup['created_by']
                    ];
                }
            }
        }

        return $backups;
    }

    /**
     * Get template details
     */
    public function getTemplate($name) {
        return $this->templates[$name] ?? null;
    }

    /**
     * Get backup path
     */
    private function getBackupPath($name) {
        return __DIR__ . '/../../storage/backups/settings/' . 
               ($name ? $name . '.json' : '');
    }

    /**
     * Create custom template
     */
    public function createTemplate($name, $settings, $userId) {
        if (isset($this->templates[$name])) {
            throw new Exception("Template already exists: {$name}");
        }

        // Validate all settings
        foreach ($settings as $key => $value) {
            $result = $this->validator->validate($key, $value);
            if ($result !== true) {
                throw new Exception("Invalid setting in template: {$key}");
            }
        }

        $this->templates[$name] = $settings;

        $this->auditLogger->log('template_created', [
            'name' => $name,
            'user_id' => $userId,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        return true;
    }

    /**
     * Delete backup
     */
    public function deleteBackup($name, $userId) {
        $backupPath = $this->getBackupPath($name);
        if (!file_exists($backupPath)) {
            throw new Exception("Backup not found: {$name}");
        }

        if (unlink($backupPath)) {
            $this->auditLogger->log('backup_deleted', [
                'name' => $name,
                'user_id' => $userId,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            return true;
        }

        return false;
    }
}
