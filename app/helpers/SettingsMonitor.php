<?php

class SettingsMonitor {
    private static $instance = null;
    private $db;
    private $cache;
    private $auditLogger;
    private $validator;

    private function __construct() {
        $this->db = Database::getInstance();
        $this->cache = Cache::getInstance();
        $this->auditLogger = AuditLogger::getInstance();
        $this->validator = SettingsValidator::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get settings dashboard data
     */
    public function getDashboardData() {
        $cacheKey = 'settings_dashboard';
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        $data = [
            'validation' => $this->getValidationMetrics(),
            'usage' => $this->getUsageMetrics(),
            'changes' => $this->getChangeMetrics(),
            'performance' => $this->getPerformanceMetrics(),
            'automation' => $this->getAutomationStatus()
        ];

        $this->cache->set($cacheKey, $data, 300); // Cache for 5 minutes
        return $data;
    }

    /**
     * Get validation metrics
     */
    private function getValidationMetrics() {
        $sql = "SELECT 
                    COUNT(*) as total_validations,
                    SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed,
                    SUM(CASE WHEN passed = 0 THEN 1 ELSE 0 END) as failed
                FROM setting_validations 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        
        $results = $this->db->query($sql)[0];
        
        return [
            'total' => $results['total_validations'],
            'passed' => $results['passed'],
            'failed' => $results['failed'],
            'success_rate' => $results['total_validations'] > 0 ? 
                round(($results['passed'] / $results['total_validations']) * 100, 2) : 0,
            'recent_failures' => $this->getRecentValidationFailures()
        ];
    }

    /**
     * Get recent validation failures
     */
    private function getRecentValidationFailures() {
        $sql = "SELECT s.key, v.error_message, v.created_at
                FROM setting_validations v
                JOIN settings s ON v.setting_id = s.id
                WHERE v.passed = 0
                AND v.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY v.created_at DESC
                LIMIT 5";
        
        return $this->db->query($sql);
    }

    /**
     * Get usage metrics
     */
    private function getUsageMetrics() {
        return [
            'most_accessed' => $this->getMostAccessedSettings(),
            'least_accessed' => $this->getLeastAccessedSettings(),
            'access_patterns' => $this->getAccessPatterns(),
            'cache_hits' => $this->getCacheMetrics()
        ];
    }

    /**
     * Get most accessed settings
     */
    private function getMostAccessedSettings() {
        $sql = "SELECT s.key, COUNT(*) as access_count
                FROM setting_access_logs l
                JOIN settings s ON l.setting_id = s.id
                WHERE l.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY s.key
                ORDER BY access_count DESC
                LIMIT 5";
        
        return $this->db->query($sql);
    }

    /**
     * Get change metrics
     */
    private function getChangeMetrics() {
        $sql = "SELECT 
                    COUNT(*) as total_changes,
                    COUNT(DISTINCT setting_id) as settings_changed,
                    COUNT(DISTINCT user_id) as users_made_changes
                FROM setting_change_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        
        $results = $this->db->query($sql)[0];
        
        return [
            'total_changes' => $results['total_changes'],
            'settings_changed' => $results['settings_changed'],
            'users_made_changes' => $results['users_made_changes'],
            'recent_changes' => $this->getRecentChanges()
        ];
    }

    /**
     * Get recent changes
     */
    private function getRecentChanges() {
        $sql = "SELECT s.key, u.username, c.old_value, c.new_value, c.created_at
                FROM setting_change_logs c
                JOIN settings s ON c.setting_id = s.id
                JOIN users u ON c.user_id = u.id
                ORDER BY c.created_at DESC
                LIMIT 5";
        
        return $this->db->query($sql);
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics() {
        return [
            'response_times' => $this->getResponseTimes(),
            'cache_performance' => $this->getCachePerformance(),
            'validation_times' => $this->getValidationTimes(),
            'database_metrics' => $this->getDatabaseMetrics()
        ];
    }

    /**
     * Get automation status
     */
    private function getAutomationStatus() {
        return [
            'scheduled_tasks' => $this->getScheduledTasks(),
            'automation_rules' => $this->getAutomationRules(),
            'recent_executions' => $this->getRecentAutomationExecutions()
        ];
    }

    /**
     * Generate settings report
     */
    public function generateReport($type = 'daily', $format = 'html') {
        $data = [
            'period' => $type,
            'generated_at' => date('Y-m-d H:i:s'),
            'metrics' => $this->getDashboardData(),
            'validation_summary' => $this->getValidationSummary(),
            'change_history' => $this->getChangeHistory(),
            'performance_data' => $this->getPerformanceData()
        ];

        switch ($format) {
            case 'json':
                return json_encode($data);
            case 'csv':
                return $this->generateCsvReport($data);
            case 'pdf':
                return $this->generatePdfReport($data);
            default:
                return $this->generateHtmlReport($data);
        }
    }

    /**
     * Validate all settings
     */
    public function validateAllSettings() {
        $sql = "SELECT * FROM settings";
        $settings = $this->db->query($sql);
        $results = [];

        foreach ($settings as $setting) {
            $results[$setting['key']] = [
                'valid' => $this->validator->validate($setting['key'], $setting['value']),
                'errors' => $this->validator->getErrors()
            ];
        }

        return $results;
    }

    /**
     * Execute automated tasks
     */
    public function executeAutomatedTasks() {
        $tasks = $this->getScheduledTasks();
        $results = [];

        foreach ($tasks as $task) {
            if ($this->shouldExecuteTask($task)) {
                $results[$task['name']] = $this->executeTask($task);
            }
        }

        return $results;
    }

    /**
     * Check if task should be executed
     */
    private function shouldExecuteTask($task) {
        $lastRun = $this->getTaskLastRun($task['id']);
        $now = time();

        return !$lastRun || ($now - $lastRun) >= $task['interval'];
    }

    /**
     * Execute specific task
     */
    private function executeTask($task) {
        try {
            switch ($task['type']) {
                case 'validation':
                    $result = $this->validateAllSettings();
                    break;
                case 'cleanup':
                    $result = $this->cleanupOldData();
                    break;
                case 'backup':
                    $result = $this->backupSettings();
                    break;
                case 'report':
                    $result = $this->generateReport($task['params']['type']);
                    break;
                default:
                    throw new Exception("Unknown task type: {$task['type']}");
            }

            $this->logTaskExecution($task['id'], true, null);
            return ['success' => true, 'result' => $result];
        } catch (Exception $e) {
            $this->logTaskExecution($task['id'], false, $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Log task execution
     */
    private function logTaskExecution($taskId, $success, $error = null) {
        $sql = "INSERT INTO automation_execution_logs 
                (task_id, success, error_message, created_at) 
                VALUES (?, ?, ?, NOW())";
        
        $this->db->query($sql, [$taskId, $success ? 1 : 0, $error]);
    }

    /**
     * Get scheduled tasks
     */
    private function getScheduledTasks() {
        $sql = "SELECT * FROM automation_tasks WHERE active = 1";
        return $this->db->query($sql);
    }

    /**
     * Get automation rules
     */
    private function getAutomationRules() {
        $sql = "SELECT * FROM automation_rules WHERE active = 1";
        return $this->db->query($sql);
    }

    /**
     * Get recent automation executions
     */
    private function getRecentAutomationExecutions() {
        $sql = "SELECT t.name, e.success, e.error_message, e.created_at
                FROM automation_execution_logs e
                JOIN automation_tasks t ON e.task_id = t.id
                ORDER BY e.created_at DESC
                LIMIT 10";
        
        return $this->db->query($sql);
    }

    /**
     * Clean up old data
     */
    private function cleanupOldData() {
        $tables = [
            'setting_validations' => '30 DAY',
            'setting_access_logs' => '90 DAY',
            'setting_change_logs' => '365 DAY',
            'automation_execution_logs' => '90 DAY'
        ];

        foreach ($tables as $table => $retention) {
            $sql = "DELETE FROM $table WHERE created_at < DATE_SUB(NOW(), INTERVAL $retention)";
            $this->db->query($sql);
        }

        return true;
    }

    /**
     * Backup settings
     */
    private function backupSettings() {
        $sql = "SELECT * FROM settings";
        $settings = $this->db->query($sql);
        
        $backup = [
            'timestamp' => date('Y-m-d H:i:s'),
            'settings' => $settings
        ];

        $filename = 'storage/backups/settings_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($filename, json_encode($backup, JSON_PRETTY_PRINT));
        
        return $filename;
    }
}
