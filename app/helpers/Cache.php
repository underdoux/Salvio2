<?php

class Cache {
    private static $instance = null;
    private $db;
    private $auditLogger;
    private $compressionEnabled = true;
    private $statistics = [];
    private $monitoringData = [];

    private function __construct() {
        $this->db = Database::getInstance();
        $this->auditLogger = AuditLogger::getInstance();
        $this->initializeStatistics();
        $this->warmCache();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize cache statistics
     */
    private function initializeStatistics() {
        $this->statistics = [
            'hits' => 0,
            'misses' => 0,
            'sets' => 0,
            'deletes' => 0,
            'size' => 0,
            'compression_ratio' => 0,
            'memory_usage' => 0
        ];
    }

    /**
     * Warm up cache on startup
     */
    private function warmCache() {
        try {
            // Frequently accessed settings
            $frequentSettings = [
                'currency.code',
                'currency.symbol',
                'tax.rate',
                'commission.global_rate',
                'smtp.host',
                'smtp.port'
            ];

            foreach ($frequentSettings as $key) {
                $query = "SELECT value FROM settings WHERE `key` = ?";
                $result = $this->db->query($query, [$key]);
                if ($result) {
                    $this->set($key, $result[0]['value']);
                }
            }

            $this->auditLogger->log('cache_warmed', [
                'timestamp' => date('Y-m-d H:i:s'),
                'settings' => $frequentSettings
            ]);
        } catch (Exception $e) {
            $this->auditLogger->log('cache_warm_failed', [
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }

    /**
     * Get cached value
     */
    public function get($key) {
        $cacheFile = $this->getCacheFile($key);
        
        if (file_exists($cacheFile)) {
            $data = $this->readCache($cacheFile);
            if ($data && !$this->isExpired($data)) {
                $this->statistics['hits']++;
                return $data['value'];
            }
        }

        $this->statistics['misses']++;
        return null;
    }

    /**
     * Set cached value
     */
    public function set($key, $value, $ttl = 3600) {
        $cacheFile = $this->getCacheFile($key);
        $data = [
            'key' => $key,
            'value' => $value,
            'ttl' => $ttl,
            'created_at' => time(),
            'expires_at' => time() + $ttl
        ];

        if ($this->compressionEnabled) {
            $data = $this->compress($data);
        }

        if ($this->writeCache($cacheFile, $data)) {
            $this->statistics['sets']++;
            $this->updateMonitoring('set', $key, strlen(serialize($data)));
            return true;
        }

        return false;
    }

    /**
     * Delete cached value
     */
    public function delete($key) {
        $cacheFile = $this->getCacheFile($key);
        if (file_exists($cacheFile) && unlink($cacheFile)) {
            $this->statistics['deletes']++;
            $this->updateMonitoring('delete', $key);
            return true;
        }
        return false;
    }

    /**
     * Clear all cache
     */
    public function clear() {
        $cacheDir = $this->getCacheDir();
        array_map('unlink', glob("$cacheDir/*"));
        $this->initializeStatistics();
        $this->updateMonitoring('clear');
        return true;
    }

    /**
     * Compress data
     */
    private function compress($data) {
        if (!$this->compressionEnabled) {
            return $data;
        }

        $serialized = serialize($data);
        $compressed = gzcompress($serialized);
        
        if ($compressed === false) {
            return $data;
        }

        $this->statistics['compression_ratio'] = 
            round((strlen($serialized) - strlen($compressed)) / strlen($serialized) * 100, 2);

        return [
            'compressed' => true,
            'data' => $compressed
        ];
    }

    /**
     * Decompress data
     */
    private function decompress($data) {
        if (!isset($data['compressed']) || !$data['compressed']) {
            return $data;
        }

        $decompressed = gzuncompress($data['data']);
        if ($decompressed === false) {
            return null;
        }

        return unserialize($decompressed);
    }

    /**
     * Get cache statistics
     */
    public function getStatistics() {
        $this->statistics['memory_usage'] = $this->calculateMemoryUsage();
        $this->statistics['size'] = $this->calculateCacheSize();
        return $this->statistics;
    }

    /**
     * Get monitoring data
     */
    public function getMonitoringData() {
        return [
            'statistics' => $this->getStatistics(),
            'events' => $this->monitoringData,
            'health' => $this->checkCacheHealth()
        ];
    }

    /**
     * Update monitoring data
     */
    private function updateMonitoring($action, $key = null, $size = null) {
        $this->monitoringData[] = [
            'action' => $action,
            'key' => $key,
            'size' => $size,
            'timestamp' => time()
        ];

        // Keep only last 1000 events
        if (count($this->monitoringData) > 1000) {
            array_shift($this->monitoringData);
        }
    }

    /**
     * Check cache health
     */
    private function checkCacheHealth() {
        $health = [
            'status' => 'healthy',
            'issues' => []
        ];

        // Check hit ratio
        $total = $this->statistics['hits'] + $this->statistics['misses'];
        if ($total > 0) {
            $hitRatio = $this->statistics['hits'] / $total;
            if ($hitRatio < 0.8) {
                $health['issues'][] = 'Low hit ratio: ' . round($hitRatio * 100, 2) . '%';
                $health['status'] = 'warning';
            }
        }

        // Check memory usage
        $memoryUsage = $this->calculateMemoryUsage();
        if ($memoryUsage > 90) {
            $health['issues'][] = 'High memory usage: ' . $memoryUsage . '%';
            $health['status'] = 'critical';
        }

        return $health;
    }

    /**
     * Calculate memory usage
     */
    private function calculateMemoryUsage() {
        $cacheDir = $this->getCacheDir();
        $totalSpace = disk_free_space($cacheDir);
        $usedSpace = $this->calculateCacheSize();
        
        return $totalSpace > 0 ? 
            round(($usedSpace / $totalSpace) * 100, 2) : 0;
    }

    /**
     * Calculate total cache size
     */
    private function calculateCacheSize() {
        $cacheDir = $this->getCacheDir();
        $size = 0;
        
        foreach (glob("$cacheDir/*") as $file) {
            $size += filesize($file);
        }
        
        return $size;
    }

    /**
     * Get cache directory
     */
    private function getCacheDir() {
        $dir = __DIR__ . '/../../storage/cache';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return $dir;
    }

    /**
     * Get cache file path
     */
    private function getCacheFile($key) {
        return $this->getCacheDir() . '/' . md5($key) . '.cache';
    }

    /**
     * Check if cache is expired
     */
    private function isExpired($data) {
        return isset($data['expires_at']) && $data['expires_at'] < time();
    }

    /**
     * Read from cache file
     */
    private function readCache($file) {
        $data = file_get_contents($file);
        if ($data === false) {
            return null;
        }

        $data = unserialize($data);
        if ($this->compressionEnabled && isset($data['compressed'])) {
            $data = $this->decompress($data);
        }

        return $data;
    }

    /**
     * Write to cache file
     */
    private function writeCache($file, $data) {
        return file_put_contents($file, serialize($data)) !== false;
    }

    /**
     * Enable/disable compression
     */
    public function setCompression($enabled) {
        $this->compressionEnabled = $enabled;
    }
}
