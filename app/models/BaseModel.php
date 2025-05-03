<?php

require_once __DIR__ . '/../helpers/CurrencyFormatter.php';
require_once __DIR__ . '/../helpers/Database.php';

class BaseModel {
    protected $db;
    protected static $currencyFormatter;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        if (!self::$currencyFormatter) {
            self::$currencyFormatter = CurrencyFormatter::getInstance();
        }
    }

    /**
     * Format amount to IDR
     */
    protected function formatCurrency($amount, $includeSymbol = true) {
        return self::$currencyFormatter->format($amount, $includeSymbol);
    }

    /**
     * Parse IDR string to number
     */
    protected function parseCurrency($formatted) {
        return self::$currencyFormatter->parse($formatted);
    }
}
