<?php

class SettingsValidator {
    private static $instance = null;
    private $db;
    private $auditLogger;

    private function __construct() {
        $this->db = Database::getInstance();
        $this->auditLogger = AuditLogger::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Validation rules by setting type
     */
    private $validationRules = [
        'currency' => [
            'code' => [
                'pattern' => '/^[A-Z]{3}$/',
                'message' => 'Currency code must be 3 uppercase letters'
            ],
            'symbol' => [
                'pattern' => '/^[^\s]{1,3}$/',
                'message' => 'Symbol must be 1-3 non-space characters'
            ],
            'decimals' => [
                'min' => 0,
                'max' => 4,
                'message' => 'Decimal places must be between 0 and 4'
            ]
        ],
        'email' => [
            'host' => [
                'pattern' => '/^[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}$/',
                'message' => 'Invalid email host format'
            ],
            'port' => [
                'min' => 1,
                'max' => 65535,
                'message' => 'Port must be between 1 and 65535'
            ],
            'encryption' => [
                'values' => ['tls', 'ssl', 'none'],
                'message' => 'Invalid encryption type'
            ]
        ],
        'tax' => [
            'rate' => [
                'min' => 0,
                'max' => 100,
                'message' => 'Tax rate must be between 0 and 100'
            ],
            'number_format' => [
                'pattern' => '/^[A-Z]{2}\d{2,3}$/',
                'message' => 'Invalid tax number format'
            ]
        ],
        'commission' => [
            'rate' => [
                'min' => 0,
                'max' => 50,
                'message' => 'Commission rate must be between 0 and 50'
            ],
            'period' => [
                'values' => ['daily', 'weekly', 'monthly'],
                'message' => 'Invalid commission period'
            ]
        ],
        'payment' => [
            'terms' => [
                'min' => 0,
                'max' => 90,
                'message' => 'Payment terms must be between 0 and 90 days'
            ],
            'methods' => [
                'values' => ['cash', 'credit', 'transfer'],
                'message' => 'Invalid payment method'
            ]
        ],
        'stock' => [
            'threshold' => [
                'min' => 0,
                'message' => 'Stock threshold must be non-negative'
            ],
            'expiry_days' => [
                'min' => 1,
                'message' => 'Expiry days must be positive'
            ]
        ]
    ];

    /**
     * Value constraints by setting
     */
    private $valueConstraints = [
        'smtp.encryption' => [
            'dependencies' => [
                'smtp.port' => [
                    'ssl' => 465,
                    'tls' => 587
                ]
            ]
        ],
        'tax.enabled' => [
            'requires' => ['tax.rate', 'tax.number_format']
        ],
        'commission.enabled' => [
            'requires' => ['commission.rate', 'commission.period']
        ],
        'stock.tracking' => [
            'requires' => ['stock.threshold', 'stock.expiry_days']
        ]
    ];

    /**
     * Validate setting value
     */
    public function validate($key, $value, $context = []) {
        $type = $this->getSettingType($key);
        
        if (!isset($this->validationRules[$type])) {
            return true; // No specific rules
        }

        $rules = $this->validationRules[$type];
        $field = explode('.', $key)[1];

        if (!isset($rules[$field])) {
            return true; // No rules for this field
        }

        $rule = $rules[$field];
        $errors = [];

        // Pattern validation
        if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
            $errors[] = $rule['message'];
        }

        // Range validation
        if (isset($rule['min']) && $value < $rule['min']) {
            $errors[] = $rule['message'];
        }
        if (isset($rule['max']) && $value > $rule['max']) {
            $errors[] = $rule['message'];
        }

        // Value list validation
        if (isset($rule['values']) && !in_array($value, $rule['values'])) {
            $errors[] = $rule['message'];
        }

        // Constraint validation
        if (isset($this->valueConstraints[$key])) {
            $constraint = $this->valueConstraints[$key];
            
            // Dependency constraints
            if (isset($constraint['dependencies'])) {
                foreach ($constraint['dependencies'] as $depKey => $depValues) {
                    if (isset($context[$depKey]) && isset($depValues[$value])) {
                        if ($context[$depKey] != $depValues[$value]) {
                            $errors[] = "Invalid value for {$depKey} with {$key}={$value}";
                        }
                    }
                }
            }

            // Required fields
            if (isset($constraint['requires']) && $value === true) {
                foreach ($constraint['requires'] as $reqKey) {
                    if (!isset($context[$reqKey]) || empty($context[$reqKey])) {
                        $errors[] = "{$reqKey} is required when {$key} is enabled";
                    }
                }
            }
        }

        if (!empty($errors)) {
            $this->logValidationError($key, $value, $errors);
            return $errors;
        }

        return true;
    }

    /**
     * Get setting type from key
     */
    private function getSettingType($key) {
        return explode('.', $key)[0];
    }

    /**
     * Log validation error
     */
    private function logValidationError($key, $value, $errors) {
        $this->auditLogger->logValidation($key, $value, false, [
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get validation rules for setting
     */
    public function getRules($key) {
        $type = $this->getSettingType($key);
        $field = explode('.', $key)[1];
        
        if (isset($this->validationRules[$type][$field])) {
            return $this->validationRules[$type][$field];
        }
        
        return null;
    }

    /**
     * Get constraints for setting
     */
    public function getConstraints($key) {
        return $this->valueConstraints[$key] ?? null;
    }
}
