<?php

class CacheMonitor {
    private static $instance = null;
    private $db;
    private $cache;
    private $auditLogger;

    private function __construct() {
        $this->db = Database::getInstance();
        $this->cache = Cache::getInstance();
        $this->auditLogger = AuditLogger::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get cache statistics
     */
    public function getStatistics() {
        $stats = [
            'hits' => $this->getHitCount(),
            'misses' => $this->getMissCount(),
            'hit_ratio' => $this->calculateHitRatio(),
            'memory' => $this->getMemoryUsage(),
            'compression' => $this->getCompressionStats(),
            'items' => $this->getCacheItems(),
            'performance' => $this->getPerformanceMetrics()
        ];

        $this->logStatistics($stats);
        return $stats;
    }

    /**
     * Get cache hit count
     */
    private function getHitCount() {
        $sql = "SELECT COUNT(*) as count FROM cache_access_logs WHERE hit = 1";
        return $this->db->query($sql)[0]['count'];
    }

    /**
     * Get cache miss count
     */
    private function getMissCount() {
        $sql = "SELECT COUNT(*) as count FROM cache_access_logs WHERE hit = 0";
        return $this->db->query($sql)[0]['count'];
    }

    /**
     * Calculate hit ratio
     */
    private function calculateHitRatio() {
        $hits = $this->getHitCount();
        $total = $hits + $this->getMissCount();
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }

    /**
     * Get memory usage
     */
    private function getMemoryUsage() {
        $cacheDir = __DIR__ . '/../../storage/cache';
        $totalSpace = disk_free_space($cacheDir);
        $usedSpace = $this->calculateDirSize($cacheDir);
        
        return [
            'total' => $totalSpace,
            'used' => $usedSpace,
            'free' => $totalSpace - $usedSpace,
            'percentage' => $totalSpace > 0 ? 
                round(($usedSpace / $totalSpace) * 100, 2) : 0
        ];
    }

    /**
     * Get compression statistics
     */
    private function getCompressionStats() {
        $sql = "SELECT 
                AVG(compression_ratio) as avg_ratio,
                MIN(compression_ratio) as min_ratio,
                MAX(compression_ratio) as max_ratio,
                COUNT(*) as total_items
                FROM cache_items 
                WHERE compressed = 1";
        
        return $this->db->query($sql)[0];
    }

    /**
     * Get cache items
     */
    private function getCacheItems() {
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN expired = 1 THEN 1 ELSE 0 END) as expired,
                AVG(size) as avg_size
                FROM cache_items";
        
        return $this->db->query($sql)[0];
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics() {
        $sql = "SELECT 
                AVG(response_time) as avg_response,
                MIN(response_time) as min_response,
                MAX(response_time) as max_response
                FROM cache_access_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        
        return $this->db->query($sql)[0];
    }

    /**
     * Log statistics
     */
    private function logStatistics($stats) {
        $sql = "INSERT INTO cache_statistics 
                (hit_ratio, memory_used, avg_compression, total_items, created_at)
                VALUES (?, ?, ?, ?, NOW())";
        
        $this->db->query($sql, [
            $stats['hit_ratio'],
            $stats['memory']['percentage'],
            $stats['compression']['avg_ratio'],
            $stats['items']['total']
        ]);
    }

    /**
     * Calculate directory size
     */
    private function calculateDirSize($dir) {
        $size = 0;
        foreach (glob("$dir/*") as $file) {
            $size += is_file($file) ? filesize($file) : $this->calculateDirSize($file);
        }
        return $size;
    }

    /**
     * Manage cache replication
     */
    public function manageReplication() {
        $nodes = $this->getReplicationNodes();
        $status = [];

        foreach ($nodes as $node) {
            $status[$node['id']] = $this->replicateToNode($node);
        }

        $this->logReplicationStatus($status);
        return $status;
    }

    /**
     * Get replication nodes
     */
    private function getReplicationNodes() {
        $sql = "SELECT * FROM cache_nodes WHERE active = 1";
        return $this->db->query($sql);
    }

    /**
     * Replicate to node
     */
    private function replicateToNode($node) {
        try {
            // Get items to replicate
            $items = $this->getItemsForReplication($node['last_sync']);

            // Prepare replication data
            $data = [
                'items' => $items,
                'timestamp' => time(),
                'source' => gethostname()
            ];

            // Send data to node
            $success = $this->sendToNode($node, $data);

            if ($success) {
                $this->updateNodeSync($node['id']);
            }

            return [
                'success' => $success,
                'items_count' => count($items),
                'timestamp' => time()
            ];
        } catch (Exception $e) {
            $this->auditLogger->log('cache_replication_failed', [
                'node' => $node['id'],
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get items for replication
     */
    private function getItemsForReplication($lastSync) {
        $sql = "SELECT * FROM cache_items 
                WHERE updated_at > ? 
                AND needs_replication = 1";
        
        return $this->db->query($sql, [$lastSync]);
    }

    /**
     * Send data to node
     */
    private function sendToNode($node, $data) {
        // Implementation would depend on your network setup
        // This is a placeholder for the actual network communication
        return true;
    }

    /**
     * Update node sync time
     */
    private function updateNodeSync($nodeId) {
        $sql = "UPDATE cache_nodes 
                SET last_sync = NOW() 
                WHERE id = ?";
        
        $this->db->query($sql, [$nodeId]);
    }

    /**
     * Log replication status
     */
    private function logReplicationStatus($status) {
        foreach ($status as $nodeId => $result) {
            $sql = "INSERT INTO cache_replication_logs 
                    (node_id, success, items_count, error_message, created_at)
                    VALUES (?, ?, ?, ?, NOW())";
            
            $this->db->query($sql, [
                $nodeId,
                $result['success'] ? 1 : 0,
                $result['items_count'] ?? 0,
                $result['error'] ?? null
            ]);
        }
    }

    /**
     * Monitor cache health
     */
    public function monitorHealth() {
        $metrics = [
            'memory' => $this->checkMemoryHealth(),
            'performance' => $this->checkPerformanceHealth(),
            'replication' => $this->checkReplicationHealth(),
            'errors' => $this->checkErrorRate()
        ];

        $this->logHealthCheck($metrics);
        $this->triggerAlerts($metrics);

        return $metrics;
    }

    /**
     * Check memory health
     */
    private function checkMemoryHealth() {
        $memory = $this->getMemoryUsage();
        
        return [
            'status' => $memory['percentage'] < 90 ? 'healthy' : 'critical',
            'usage' => $memory['percentage'],
            'threshold' => 90
        ];
    }

    /**
     * Check performance health
     */
    private function checkPerformanceHealth() {
        $metrics = $this->getPerformanceMetrics();
        
        return [
            'status' => $metrics['avg_response'] < 100 ? 'healthy' : 'warning',
            'response_time' => $metrics['avg_response'],
            'threshold' => 100
        ];
    }

    /**
     * Check replication health
     */
    private function checkReplicationHealth() {
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed
                FROM cache_replication_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        
        $result = $this->db->query($sql)[0];
        $failRate = $result['total'] > 0 ? 
            ($result['failed'] / $result['total']) * 100 : 0;
        
        return [
            'status' => $failRate < 5 ? 'healthy' : 'critical',
            'fail_rate' => $failRate,
            'threshold' => 5
        ];
    }

    /**
     * Check error rate
     */
    private function checkErrorRate() {
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN error_message IS NOT NULL THEN 1 ELSE 0 END) as errors
                FROM cache_access_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        
        $result = $this->db->query($sql)[0];
        $errorRate = $result['total'] > 0 ? 
            ($result['errors'] / $result['total']) * 100 : 0;
        
        return [
            'status' => $errorRate < 1 ? 'healthy' : 'warning',
            'error_rate' => $errorRate,
            'threshold' => 1
        ];
    }

    /**
     * Log health check
     */
    private function logHealthCheck($metrics) {
        $sql = "INSERT INTO cache_health_logs 
                (memory_status, performance_status, replication_status, error_status, created_at)
                VALUES (?, ?, ?, ?, NOW())";
        
        $this->db->query($sql, [
            $metrics['memory']['status'],
            $metrics['performance']['status'],
            $metrics['replication']['status'],
            $metrics['errors']['status']
        ]);
    }

    /**
     * Trigger alerts
     */
    private function triggerAlerts($metrics) {
        foreach ($metrics as $type => $metric) {
            if ($metric['status'] === 'critical') {
                $this->sendAlert($type, $metric);
            }
        }
    }

    /**
     * Send alert
     */
    private function sendAlert($type, $metric) {
        $this->auditLogger->log('cache_alert', [
            'type' => $type,
            'status' => $metric['status'],
            'details' => $metric
        ]);
    }
}
