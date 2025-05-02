<?php
require_once __DIR__ . '/../config/database.php';

class SettingsService {
    private $conn;
    private $cache;
    private $cacheExpiry = 3600; // 1 hour
    private $currentUser;

    public function __construct($currentUser = null) {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->currentUser = $currentUser;
        $this->initializeCache();
    }

    public function get($category, $name, $default = null) {
        $cacheKey = "setting:{$category}:{$name}";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $this->parseValue($cached);
        }

        $query = "SELECT value, type FROM settings WHERE category = ? AND name = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$category, $name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return $default;
        }

        $value = $this->parseValue($result['value'], $result['type']);
        $this->cache->set($cacheKey, $result['value'], $this->cacheExpiry);

        return $value;
    }

    public function set($category, $name, $value) {
        // Check permissions
        if (!$this->currentUser || !$this->currentUser->hasPermission('edit_settings')) {
            throw new Exception('Permission denied');
        }

        // Get current setting
        $query = "SELECT id, value, type, validation_rules FROM settings WHERE category = ? AND name = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$category, $name]);
        $setting = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$setting) {
            throw new Exception('Setting not found');
        }

        // Validate value
        $this->validateValue($value, $setting['type'], json_decode($setting['validation_rules'], true));

        // Format value for storage
        $formattedValue = $this->formatValue($value, $setting['type']);

        // Start transaction
        $this->conn->beginTransaction();

        try {
            // Update setting
            $query = "UPDATE settings SET value = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$formattedValue, $setting['id']]);

            // Log change
            $this->logChange($setting['id'], $setting['value'], $formattedValue);

            $this->conn->commit();

            // Clear cache
            $this->cache->delete("setting:{$category}:{$name}");

            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function getAll($category = null, $publicOnly = false) {
        $query = "SELECT category, name, value, type, description, is_public, requires_restart 
                 FROM settings";
        $params = [];

        if ($category) {
            $query .= " WHERE category = ?";
            $params[] = $category;
        }

        if ($publicOnly) {
            $query .= $category ? " AND" : " WHERE";
            $query .= " is_public = 1";
        }

        $query .= " ORDER BY category, name";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $settings = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!isset($settings[$row['category']])) {
                $settings[$row['category']] = [];
            }
            $row['value'] = $this->parseValue($row['value'], $row['type']);
            $settings[$row['category']][$row['name']] = $row;
        }

        return $settings;
    }

    public function bulkUpdate($settings) {
        if (!$this->currentUser || !$this->currentUser->hasPermission('edit_settings')) {
            throw new Exception('Permission denied');
        }

        $this->conn->beginTransaction();

        try {
            foreach ($settings as $category => $categorySettings) {
                foreach ($categorySettings as $name => $value) {
                    $this->set($category, $name, $value);
                }
            }

            $this->conn->commit();
            $this->cache->clear();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function getAuditLog($category = null, $name = null, $limit = 100) {
        $query = "SELECT 
                    sal.id,
                    s.category,
                    s.name,
                    sal.old_value,
                    sal.new_value,
                    u.username as changed_by,
                    sal.created_at
                FROM settings_audit_log sal
                JOIN settings s ON sal.setting_id = s.id
                JOIN users u ON sal.user_id = u.id
                WHERE 1=1";
        $params = [];

        if ($category) {
            $query .= " AND s.category = ?";
            $params[] = $category;
        }

        if ($name) {
            $query .= " AND s.name = ?";
            $params[] = $name;
        }

        $query .= " ORDER BY sal.created_at DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function validateValue($value, $type, $rules = null) {
        if (!$rules) return true;

        switch ($type) {
            case 'string':
                if (isset($rules['min_length']) && strlen($value) < $rules['min_length']) {
                    throw new Exception("Value must be at least {$rules['min_length']} characters");
                }
                if (isset($rules['max_length']) && strlen($value) > $rules['max_length']) {
                    throw new Exception("Value must be at most {$rules['max_length']} characters");
                }
                if (isset($rules['pattern']) && !preg_match("/{$rules['pattern']}/", $value)) {
                    throw new Exception("Value does not match required format");
                }
                if (isset($rules['type']) && $rules['type'] === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Invalid email format");
                }
                break;

            case 'integer':
                if (!is_numeric($value) || intval($value) != $value) {
                    throw new Exception("Value must be an integer");
                }
                if (isset($rules['min']) && $value < $rules['min']) {
                    throw new Exception("Value must be at least {$rules['min']}");
                }
                if (isset($rules['max']) && $value > $rules['max']) {
                    throw new Exception("Value must be at most {$rules['max']}");
                }
                break;

            case 'float':
                if (!is_numeric($value)) {
                    throw new Exception("Value must be a number");
                }
                if (isset($rules['min']) && $value < $rules['min']) {
                    throw new Exception("Value must be at least {$rules['min']}");
                }
                if (isset($rules['max']) && $value > $rules['max']) {
                    throw new Exception("Value must be at most {$rules['max']}");
                }
                break;

            case 'boolean':
                if (!is_bool($value) && $value !== 'true' && $value !== 'false') {
                    throw new Exception("Value must be true or false");
                }
                break;

            case 'json':
            case 'array':
                if (!is_array($value) && !is_string($value)) {
                    throw new Exception("Value must be valid JSON");
                }
                if (is_string($value)) {
                    json_decode($value);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception("Invalid JSON format");
                    }
                }
                break;
        }

        return true;
    }

    private function parseValue($value, $type = 'string') {
        switch ($type) {
            case 'integer':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'boolean':
                return $value === 'true' || $value === '1' || $value === true;
            case 'json':
            case 'array':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    private function formatValue($value, $type) {
        switch ($type) {
            case 'json':
            case 'array':
                return is_string($value) ? $value : json_encode($value);
            case 'boolean':
                return $value ? 'true' : 'false';
            default:
                return (string) $value;
        }
    }

    private function logChange($settingId, $oldValue, $newValue) {
        $query = "INSERT INTO settings_audit_log 
                (setting_id, user_id, old_value, new_value)
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $settingId,
            $this->currentUser->id,
            $oldValue,
            $newValue
        ]);
    }

    private function initializeCache() {
        $this->cache = new class {
            private $cache = [];

            public function get($key) {
                if (!isset($this->cache[$key])) return null;
                if ($this->cache[$key]['expires'] < time()) {
                    unset($this->cache[$key]);
                    return null;
                }
                return $this->cache[$key]['value'];
            }

            public function set($key, $value, $ttl) {
                $this->cache[$key] = [
                    'value' => $value,
                    'expires' => time() + $ttl
                ];
            }

            public function delete($key) {
                unset($this->cache[$key]);
            }

            public function clear() {
                $this->cache = [];
            }
        };
    }
}
?>
