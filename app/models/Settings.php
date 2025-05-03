<?php

class Settings extends BaseModel {
    protected $table = 'settings';
    private $encryptionKey;
    private $cache;
    private $cacheTTL = 3600; // 1 hour default TTL

    // Define frequently accessed settings that should be cached
    private $cachedSettings = [
        // Currency settings (used in all transactions)
        'currency',
        'max_discount_percent',
        'max_discount_amount',
        
        // Product settings (used in inventory)
        'product.sku_format',
        'product.require_bpom',
        'product.categories',
        'product.storage_conditions',
        
        // Inventory thresholds (used in stock checks)
        'inventory.low_stock_threshold',
        'inventory.critical_stock_threshold',
        'inventory.auto_order_threshold',
        
        // Document formats (used in all transactions)
        'document.invoice_format',
        'document.po_format',
        'document.receipt_format',
        'document.header_info',
        
        // Customer settings (used in orders)
        'customer.credit_limit_default',
        'customer.payment_terms_default',
        
        // Quality control (used in receiving)
        'quality.temperature_range',
        'quality.humidity_range',
        'quality.inspection_checklist'
    ];

    // Define cache TTLs for different setting types
    private $cacheTTLs = [
        'currency' => 86400,        // 24 hours for currency settings
        'product' => 3600,         // 1 hour for product settings
        'inventory' => 300,        // 5 minutes for inventory settings
        'document' => 86400,       // 24 hours for document settings
        'customer' => 3600,        // 1 hour for customer settings
        'quality' => 1800         // 30 minutes for quality settings
    ];

    public function __construct() {
        parent::__construct();
        $this->encryptionKey = getenv('SETTINGS_ENCRYPTION_KEY') ?: $this->generateEncryptionKey();
        $this->cache = Cache::getInstance();
        
        // Warm up cache for frequently accessed settings
        if (!$this->cache->get('settings.warmed')) {
            $this->warmCache();
        }
    }

    public function get($key, $default = null) {
        // Check if this setting should be cached
        if (in_array($key, $this->cachedSettings)) {
            // Get TTL based on setting type
            $ttl = $this->getCacheTTL($key);
            
            return $this->cache->tags(['settings'])->remember(
                "setting.{$key}",
                $ttl,
                function() use ($key, $default) {
                    return $this->getFromDatabase($key, $default);
                }
            );
        }

        return $this->getFromDatabase($key, $default);
    }

    private function getFromDatabase($key, $default = null) {
        $sql = "SELECT `value`, `type` FROM {$this->table} WHERE `key` = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return $default;
        }

        $value = $this->shouldBeDecrypted($key) ? 
                $this->decrypt($row['value']) : 
                $row['value'];

        return $this->castValue($value, $row['type']);
    }

    public function set($key, $value, $type = 'string', $description = null) {
        try {
            // Validate and sanitize input
            $value = Validator::sanitize($value, $type);
            if (!Validator::validateSetting($this->getSettingType($key), $key, $value)) {
                throw new Exception("Invalid value for setting {$key}");
            }

            // Encrypt sensitive values
            if ($this->shouldBeEncrypted($key)) {
                $value = $this->encrypt($value);
            }

            // Update database
            $success = $this->updateDatabase($key, $value, $type, $description);

            if ($success) {
                // Invalidate cache for this setting
                if (in_array($key, $this->cachedSettings)) {
                    $this->invalidateCache($key);
                }

                // Log the change
                Logger::log("Setting '{$key}' updated by user " . ($_SESSION['user_id'] ?? 'system'), 'INFO');
                return true;
            }

            return false;
        } catch (Exception $e) {
            Logger::log("Error updating setting {$key}: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    private function updateDatabase($key, $value, $type, $description) {
        $existing = $this->getRaw($key);
        if ($existing) {
            $sql = "UPDATE {$this->table} SET `value` = ?, `type` = ?, `description` = ?, updated_at = CURRENT_TIMESTAMP WHERE `key` = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$value, $type, $description, $key]);
        } else {
            $sql = "INSERT INTO {$this->table} (`key`, `value`, `type`, `description`) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$key, $value, $type, $description]);
        }
    }

    private function warmCache() {
        foreach ($this->cachedSettings as $key) {
            $this->get($key);
        }
        $this->cache->set('settings.warmed', true, 3600); // Check again in 1 hour
    }

    private function invalidateCache($key) {
        // Invalidate specific setting
        $this->cache->tags(['settings'])->delete("setting.{$key}");
        
        // Invalidate related settings based on type
        $type = explode('.', $key)[0];
        $relatedSettings = array_filter($this->cachedSettings, function($setting) use ($type) {
            return strpos($setting, $type . '.') === 0;
        });
        
        foreach ($relatedSettings as $related) {
            $this->cache->tags(['settings'])->delete("setting.{$related}");
        }
    }

    private function getCacheTTL($key) {
        $type = explode('.', $key)[0];
        return $this->cacheTTLs[$type] ?? $this->cacheTTL;
    }

    public function clearCache() {
        return $this->cache->tags(['settings'])->flush();
    }

    private function getSettingType($key) {
        $parts = explode('.', $key);
        return $parts[0];
    }

    private function shouldBeEncrypted($key) {
        return in_array($key, [
            'smtp.password',
            'whatsapp_api.api_secret',
            'payment_gateway.secret_key'
        ]);
    }

    private function shouldBeDecrypted($key) {
        return $this->shouldBeEncrypted($key);
    }

    private function encrypt($value) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($value, 'aes-256-cbc', $this->encryptionKey, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    private function decrypt($value) {
        $data = base64_decode($value);
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        return openssl_decrypt($encrypted, 'aes-256-cbc', $this->encryptionKey, 0, $iv);
    }

    private function generateEncryptionKey() {
        $key = bin2hex(random_bytes(32));
        Logger::log("New encryption key generated for settings. Please set SETTINGS_ENCRYPTION_KEY in environment.", 'WARNING');
        return $key;
    }

    private function castValue($value, $type) {
        switch ($type) {
            case 'int':
            case 'integer':
                return (int)$value;
            case 'float':
            case 'double':
                return (float)$value;
            case 'bool':
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    public function getRaw($key) {
        $sql = "SELECT * FROM {$this->table} WHERE `key` = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$key]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
