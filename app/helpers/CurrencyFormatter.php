<?php

class CurrencyFormatter {
    private static $instance = null;
    private $config;
    private $settingsModel;

    private function __construct() {
        $this->settingsModel = new Settings();

        // Try to get currency settings from database
        $currencySettings = $this->settingsModel->get('currency', null);
        if ($currencySettings && is_array($currencySettings)) {
            $this->config = $currencySettings;
        } else {
            // Fallback to static config file
            $configFile = __DIR__ . '/../../config/settings.php';
            if (file_exists($configFile)) {
                $config = require $configFile;
                if (isset($config['currency']) && is_array($config['currency'])) {
                    $this->config = $config['currency'];
                }
            }
        }

        if (!$this->config) {
            $this->config = [
                'code' => 'IDR',
                'symbol' => 'Rp',
                'decimal_separator' => ',',
                'thousand_separator' => '.',
                'decimal_places' => 0
            ];
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function format($number) {
        $formatted = number_format(
            $number,
            $this->config['decimal_places'],
            $this->config['decimal_separator'],
            $this->config['thousand_separator']
        );
        return $this->config['symbol'] . ' ' . $formatted;
    }

    public function parse($string) {
        // Remove currency symbol and any whitespace
        $clean = trim(str_replace($this->config['symbol'], '', $string));
        
        // Replace thousand separator with nothing and decimal separator with dot
        $normalized = str_replace(
            [$this->config['thousand_separator'], $this->config['decimal_separator']],
            ['', '.'],
            $clean
        );
        
        return (float) $normalized;
    }
}
