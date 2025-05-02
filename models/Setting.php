<?php
require_once __DIR__ . '/../config/database.php';

class Setting {
    private $conn;
    private $table_name = "settings";

    public $id;
    public $category;
    public $key_name;
    public $value;
    public $created_at;

    // Cache for settings to avoid multiple database queries
    private static $settings_cache = [];

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
            (category, key_name, value) 
            VALUES (:category, :key_name, :value)
            ON DUPLICATE KEY UPDATE value = :value";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':category', $this->category);
        $stmt->bindParam(':key_name', $this->key_name);
        $stmt->bindParam(':value', $this->value);

        if ($stmt->execute()) {
            // Update cache
            self::$settings_cache[$this->category][$this->key_name] = $this->value;
            return true;
        }
        return false;
    }

    public function get($category, $key_name, $default = null) {
        // Check cache first
        if (isset(self::$settings_cache[$category][$key_name])) {
            return self::$settings_cache[$category][$key_name];
        }

        $query = "SELECT value FROM " . $this->table_name . " 
                 WHERE category = :category AND key_name = :key_name 
                 LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':key_name', $key_name);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Store in cache
        self::$settings_cache[$category][$key_name] = $row ? $row['value'] : $default;
        
        return $row ? $row['value'] : $default;
    }

    public function getCategory($category) {
        // Check cache first
        if (isset(self::$settings_cache[$category])) {
            return self::$settings_cache[$category];
        }

        $query = "SELECT key_name, value FROM " . $this->table_name . " 
                 WHERE category = :category";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category', $category);
        $stmt->execute();
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['key_name']] = $row['value'];
        }
        
        // Store in cache
        self::$settings_cache[$category] = $settings;
        
        return $settings;
    }

    public function update($category, $key_name, $value) {
        $this->category = $category;
        $this->key_name = $key_name;
        $this->value = $value;
        return $this->create(); // Uses ON DUPLICATE KEY UPDATE
    }

    public function delete($category, $key_name) {
        $query = "DELETE FROM " . $this->table_name . " 
                 WHERE category = :category AND key_name = :key_name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':key_name', $key_name);
        
        if ($stmt->execute()) {
            // Update cache
            if (isset(self::$settings_cache[$category])) {
                unset(self::$settings_cache[$category][$key_name]);
            }
            return true;
        }
        return false;
    }

    public function clearCache() {
        self::$settings_cache = [];
    }

    // Utility methods for common settings

    public function getCurrency() {
        return $this->get('system', 'currency', 'USD');
    }

    public function getDateFormat() {
        return $this->get('system', 'date_format', 'Y-m-d');
    }

    public function getTimeZone() {
        return $this->get('system', 'timezone', 'UTC');
    }

    public function getMaxDiscountPercentage() {
        return floatval($this->get('sales', 'max_discount_percentage', '10'));
    }

    public function getDefaultCommissionRate() {
        return floatval($this->get('commission', 'default_rate', '5'));
    }

    public function getDefaultTaxRate() {
        return floatval($this->get('tax', 'default_rate', '0'));
    }

    public function getNotificationSettings() {
        return [
            'email_enabled' => $this->get('notification', 'email_enabled', 'true') === 'true',
            'whatsapp_enabled' => $this->get('notification', 'whatsapp_enabled', 'true') === 'true',
            'order_update_notifications' => $this->get('notification', 'order_updates', 'true') === 'true',
            'low_stock_notifications' => $this->get('notification', 'low_stock', 'true') === 'true',
            'payment_notifications' => $this->get('notification', 'payments', 'true') === 'true'
        ];
    }

    public function getEmailSettings() {
        return [
            'smtp_host' => $this->get('email', 'smtp_host', ''),
            'smtp_port' => $this->get('email', 'smtp_port', '587'),
            'smtp_user' => $this->get('email', 'smtp_user', ''),
            'smtp_pass' => $this->get('email', 'smtp_pass', ''),
            'from_email' => $this->get('email', 'from_email', 'no-reply@pospharma.com'),
            'from_name' => $this->get('email', 'from_name', 'POS Pharma')
        ];
    }

    public function getWhatsAppSettings() {
        return [
            'api_key' => $this->get('whatsapp', 'api_key', ''),
            'api_secret' => $this->get('whatsapp', 'api_secret', ''),
            'from_number' => $this->get('whatsapp', 'from_number', '')
        ];
    }
}
?>
