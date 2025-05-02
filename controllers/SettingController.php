<?php
require_once __DIR__ . '/Controller.php';

class SettingController extends Controller {
    protected $requiredPermissions = [
        'updateSettings' => 'manage_settings',
        'getSettings' => 'view_settings',
        'resetSettings' => 'manage_settings'
    ];

    public function __construct() {
        parent::__construct();
    }

    public function updateSettings() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('manage_settings');
        if ($permCheck !== true) return $permCheck;

        $paramCheck = $this->validateRequiredParams(['settings']);
        if ($paramCheck !== true) return $paramCheck;

        if (!is_array($_POST['settings'])) {
            return $this->jsonResponse(['error' => 'Settings must be an array'], 400);
        }

        try {
            $this->beginTransaction();

            $updated = [];
            $failed = [];

            foreach ($_POST['settings'] as $setting) {
                if (!isset($setting['category']) || !isset($setting['key']) || !isset($setting['value'])) {
                    $failed[] = $setting;
                    continue;
                }

                // Validate setting
                $error = $this->validateSetting($setting['category'], $setting['key'], $setting['value']);
                if ($error) {
                    $failed[] = array_merge($setting, ['error' => $error]);
                    continue;
                }

                // Get current value for audit log
                $currentValue = $this->setting->get($setting['category'], $setting['key']);

                // Update setting
                $success = $this->setting->update(
                    $setting['category'],
                    $setting['key'],
                    $setting['value']
                );

                if ($success) {
                    $updated[] = $setting;

                    // Log the change
                    $this->logAction(
                        'setting_updated',
                        'settings',
                        null,
                        ['value' => $currentValue],
                        [
                            'category' => $setting['category'],
                            'key' => $setting['key'],
                            'value' => $setting['value']
                        ]
                    );
                } else {
                    $failed[] = $setting;
                }
            }

            if (empty($failed)) {
                $this->commit();
                
                // Clear settings cache after successful update
                $this->setting->clearCache();

                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'All settings updated successfully',
                    'updated' => $updated
                ]);
            } else {
                $this->rollback();
                return $this->jsonResponse([
                    'error' => 'Some settings failed to update',
                    'updated' => $updated,
                    'failed' => $failed
                ], 400);
            }

        } catch (Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    public function getSettings() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('view_settings');
        if ($permCheck !== true) return $permCheck;

        try {
            $category = $_GET['category'] ?? null;

            if ($category) {
                $settings = $this->setting->getCategory($category);
            } else {
                // Get all settings grouped by category
                $settings = [
                    'system' => $this->setting->getCategory('system'),
                    'notification' => $this->setting->getCategory('notification'),
                    'commission' => $this->setting->getCategory('commission'),
                    'inventory' => $this->setting->getCategory('inventory'),
                    'email' => $this->setting->getCategory('email'),
                    'whatsapp' => $this->setting->getCategory('whatsapp'),
                    'tax' => $this->setting->getCategory('tax'),
                    'bpom' => $this->setting->getCategory('bpom')
                ];
            }

            return $this->jsonResponse([
                'success' => true,
                'settings' => $settings
            ]);

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function getDefaultSettings() {
        return $this->jsonResponse([
            'success' => true,
            'settings' => [
                'system' => [
                    'currency' => 'IDR',
                    'date_format' => 'Y-m-d',
                    'timezone' => 'Asia/Jakarta',
                    'decimal_separator' => '.',
                    'thousand_separator' => ','
                ],
                'notification' => [
                    'email_enabled' => 'true',
                    'whatsapp_enabled' => 'true',
                    'order_updates' => 'true',
                    'low_stock' => 'true',
                    'payments' => 'true',
                    'profit_share_notifications' => 'true'
                ],
                'commission' => [
                    'default_rate' => '5',
                    'min_amount' => '0',
                    'max_amount' => '1000000'
                ],
                'inventory' => [
                    'low_stock_threshold' => '10',
                    'enable_auto_order' => 'false',
                    'stock_alert_email' => ''
                ],
                'email' => [
                    'smtp_host' => '',
                    'smtp_port' => '587',
                    'smtp_user' => '',
                    'smtp_pass' => '',
                    'from_email' => 'no-reply@pospharma.com',
                    'from_name' => 'POS Pharma'
                ],
                'whatsapp' => [
                    'api_key' => '',
                    'api_secret' => '',
                    'from_number' => ''
                ],
                'tax' => [
                    'default_rate' => '0',
                    'enable_tax' => 'true'
                ],
                'bpom' => [
                    'auto_scrape_enabled' => 'false',
                    'cache_duration' => '86400'
                ]
            ]
        ]);
    }

    public function resetSettings() {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) return $authCheck;

        $permCheck = $this->checkPermission('manage_settings');
        if ($permCheck !== true) return $permCheck;

        $paramCheck = $this->validateRequiredParams(['category']);
        if ($paramCheck !== true) return $paramCheck;

        try {
            $this->beginTransaction();

            // Get current settings for audit log
            $currentSettings = $this->setting->getCategory($_POST['category']);

            // Delete all settings in the category
            $query = "DELETE FROM settings WHERE category = :category";
            $stmt = $this->setting->conn->prepare($query);
            $stmt->bindParam(':category', $_POST['category']);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to reset settings');
            }

            // Get default settings
            $defaultSettings = json_decode($this->getDefaultSettings(), true);
            $defaults = $defaultSettings['settings'][$_POST['category']] ?? [];

            // Restore default settings
            foreach ($defaults as $key => $value) {
                $this->setting->update($_POST['category'], $key, $value);
            }

            // Log the reset
            $this->logAction(
                'settings_reset',
                'settings',
                null,
                $currentSettings,
                $defaults
            );

            $this->commit();
            
            // Clear settings cache
            $this->setting->clearCache();

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Settings reset to defaults successfully',
                'settings' => $defaults
            ]);

        } catch (Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    private function validateSetting($category, $key, $value) {
        switch ($category) {
            case 'system':
                switch ($key) {
                    case 'timezone':
                        if (!in_array($value, DateTimeZone::listIdentifiers())) {
                            return 'Invalid timezone';
                        }
                        break;
                    case 'currency':
                        if (!preg_match('/^[A-Z]{3}$/', $value)) {
                            return 'Invalid currency code';
                        }
                        break;
                }
                break;

            case 'commission':
                switch ($key) {
                    case 'default_rate':
                    case 'min_amount':
                    case 'max_amount':
                        if (!$this->validateNumeric($value, 0)) {
                            return 'Must be a non-negative number';
                        }
                        break;
                }
                break;

            case 'email':
                switch ($key) {
                    case 'smtp_port':
                        if (!$this->validateNumeric($value, 1, 65535)) {
                            return 'Invalid port number';
                        }
                        break;
                    case 'from_email':
                        if (!$this->validateEmail($value)) {
                            return 'Invalid email format';
                        }
                        break;
                }
                break;

            case 'whatsapp':
                switch ($key) {
                    case 'from_number':
                        if (!$this->validatePhone($value)) {
                            return 'Invalid phone number format';
                        }
                        break;
                }
                break;

            case 'tax':
                switch ($key) {
                    case 'default_rate':
                        if (!$this->validateNumeric($value, 0, 100)) {
                            return 'Tax rate must be between 0 and 100';
                        }
                        break;
                }
                break;
        }

        return null;
    }
}
?>
