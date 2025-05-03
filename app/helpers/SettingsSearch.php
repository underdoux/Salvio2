<?php

class SettingsSearch {
    private static $instance = null;
    private $db;
    private $cache;

    private function __construct() {
        $this->db = Database::getInstance();
        $this->cache = Cache::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Search settings
     */
    public function search($query, $filters = []) {
        $cacheKey = 'settings_search_' . md5($query . serialize($filters));
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        $sql = "SELECT s.*, g.name as group_name 
                FROM settings s 
                LEFT JOIN setting_groups g ON s.group_id = g.id 
                WHERE 1=1";
        $params = [];

        // Search in key and description
        if ($query) {
            $sql .= " AND (s.key LIKE ? OR s.description LIKE ?)";
            $params[] = "%{$query}%";
            $params[] = "%{$query}%";
        }

        // Apply filters
        if (!empty($filters['group'])) {
            $sql .= " AND g.name = ?";
            $params[] = $filters['group'];
        }

        if (!empty($filters['type'])) {
            $sql .= " AND s.type = ?";
            $params[] = $filters['type'];
        }

        $results = $this->db->query($sql, $params);
        
        // Cache results for 5 minutes
        $this->cache->set($cacheKey, $results, 300);
        
        return $results;
    }

    /**
     * Get setting groups
     */
    public function getGroups() {
        $cacheKey = 'setting_groups';
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        $sql = "SELECT * FROM setting_groups ORDER BY name";
        $groups = $this->db->query($sql);
        
        $this->cache->set($cacheKey, $groups, 3600);
        
        return $groups;
    }

    /**
     * Bulk edit settings
     */
    public function bulkEdit($settings, $userId) {
        try {
            $this->db->beginTransaction();

            foreach ($settings as $key => $value) {
                $sql = "UPDATE settings SET value = ?, updated_by = ?, updated_at = NOW() WHERE `key` = ?";
                $this->db->query($sql, [$value, $userId, $key]);
            }

            $this->db->commit();
            $this->cache->clear('settings_');
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Get settings by group
     */
    public function getByGroup($groupName) {
        $cacheKey = 'settings_group_' . $groupName;
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        $sql = "SELECT s.* 
                FROM settings s 
                JOIN setting_groups g ON s.group_id = g.id 
                WHERE g.name = ?
                ORDER BY s.key";
        
        $settings = $this->db->query($sql, [$groupName]);
        
        $this->cache->set($cacheKey, $settings, 3600);
        
        return $settings;
    }

    /**
     * Create setting group
     */
    public function createGroup($name, $description, $userId) {
        $sql = "INSERT INTO setting_groups (name, description, created_by) VALUES (?, ?, ?)";
        $this->db->query($sql, [$name, $description, $userId]);
        
        $this->cache->delete('setting_groups');
        return $this->db->lastInsertId();
    }

    /**
     * Move settings to group
     */
    public function moveToGroup($settingKeys, $groupId, $userId) {
        try {
            $this->db->beginTransaction();

            $sql = "UPDATE settings SET group_id = ?, updated_by = ?, updated_at = NOW() WHERE `key` IN (?)";
            $this->db->query($sql, [$groupId, $userId, implode(',', $settingKeys)]);

            $this->db->commit();
            $this->cache->clear('settings_');
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Search templates
     */
    public function searchTemplates($query) {
        $sql = "SELECT * FROM setting_templates WHERE name LIKE ? OR description LIKE ?";
        return $this->db->query($sql, ["%{$query}%", "%{$query}%"]);
    }

    /**
     * Get template details
     */
    public function getTemplate($id) {
        $sql = "SELECT * FROM setting_templates WHERE id = ?";
        return $this->db->query($sql, [$id])[0] ?? null;
    }

    /**
     * Create template from current settings
     */
    public function createTemplate($name, $description, $settingKeys, $userId) {
        try {
            $this->db->beginTransaction();

            // Create template
            $sql = "INSERT INTO setting_templates (name, description, created_by) VALUES (?, ?, ?)";
            $this->db->query($sql, [$name, $description, $userId]);
            $templateId = $this->db->lastInsertId();

            // Get current settings
            $sql = "SELECT * FROM settings WHERE `key` IN (?)";
            $settings = $this->db->query($sql, [implode(',', $settingKeys)]);

            // Save template settings
            foreach ($settings as $setting) {
                $sql = "INSERT INTO setting_template_values (template_id, setting_key, value) VALUES (?, ?, ?)";
                $this->db->query($sql, [$templateId, $setting['key'], $setting['value']]);
            }

            $this->db->commit();
            return $templateId;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Apply template
     */
    public function applyTemplate($templateId, $userId) {
        try {
            $this->db->beginTransaction();

            // Get template settings
            $sql = "SELECT * FROM setting_template_values WHERE template_id = ?";
            $settings = $this->db->query($sql, [$templateId]);

            // Apply settings
            foreach ($settings as $setting) {
                $sql = "UPDATE settings SET value = ?, updated_by = ?, updated_at = NOW() WHERE `key` = ?";
                $this->db->query($sql, [$setting['value'], $userId, $setting['setting_key']]);
            }

            $this->db->commit();
            $this->cache->clear('settings_');
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
