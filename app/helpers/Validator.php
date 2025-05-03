<?php

class Validator {
    // Common validation patterns
    private static $patterns = [
        'email' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
        'url' => '/^https?:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}/',
        'phone' => '/^[0-9\-\+\(\)\s]{8,20}$/',
        'date' => '/^\d{4}-\d{2}-\d{2}$/',
        'time' => '/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/',
        'color' => '/^#[0-9a-fA-F]{6}$/',
        'ipv4' => '/^(\d{1,3}\.){3}\d{1,3}$/',
        'timezone' => '/^[A-Za-z_]+\/[A-Za-z_]+$/',
        'bpom_number' => '/^[A-Z]{3}[0-9]{9}$/',
        'sku' => '/^[A-Z0-9\-]{3,20}$/',
        'percentage' => '/^(100(\.0{1,2})?|\d{1,2}(\.\d{1,2})?)$/'
    ];

    // Setting type specific validation rules
    private static $settingRules = [
        'currency' => [
            'code' => [
                'pattern' => '/^[A-Z]{3}$/',
                'message' => 'Currency code must be 3 uppercase letters'
            ],
            'symbol' => [
                'pattern' => '/^[^\s]{1,3}$/',
                'message' => 'Currency symbol must be 1-3 characters'
            ],
            'decimal_separator' => [
                'pattern' => '/^[.,]$/',
                'message' => 'Decimal separator must be . or ,'
            ],
            'thousand_separator' => [
                'pattern' => '/^[., ]$/',
                'message' => 'Thousand separator must be ., space or ,'
            ],
            'decimal_places' => [
                'pattern' => '/^[0-4]$/',
                'message' => 'Decimal places must be between 0 and 4'
            ]
        ],
        'product' => [
            'sku_format' => [
                'pattern' => '/^[A-Z\-\{\}]+$/',
                'message' => 'SKU format must contain only uppercase letters, hyphens, and placeholders'
            ],
            'require_bpom' => [
                'type' => 'boolean',
                'message' => 'BPOM requirement must be true or false'
            ],
            'expiry_warning_days' => [
                'pattern' => '/^[1-9][0-9]{0,2}$/',
                'message' => 'Expiry warning days must be between 1 and 999'
            ],
            'categories' => [
                'type' => 'json_array',
                'message' => 'Categories must be a valid JSON array'
            ],
            'storage_conditions' => [
                'type' => 'json_array',
                'message' => 'Storage conditions must be a valid JSON array'
            ]
        ],
        'inventory' => [
            'low_stock_threshold' => [
                'pattern' => '/^[1-9][0-9]*$/',
                'message' => 'Low stock threshold must be a positive number'
            ],
            'critical_stock_threshold' => [
                'pattern' => '/^[1-9][0-9]*$/',
                'message' => 'Critical stock threshold must be a positive number'
            ],
            'auto_order_threshold' => [
                'pattern' => '/^[1-9][0-9]*$/',
                'message' => 'Auto order threshold must be a positive number'
            ],
            'batch_tracking' => [
                'type' => 'boolean',
                'message' => 'Batch tracking must be true or false'
            ],
            'expiry_tracking' => [
                'type' => 'boolean',
                'message' => 'Expiry tracking must be true or false'
            ]
        ],
        'quality' => [
            'temperature_range' => [
                'type' => 'json_object',
                'validator' => function($value) {
                    $data = json_decode($value, true);
                    return isset($data['min'], $data['max'], $data['unit']) &&
                           is_numeric($data['min']) && is_numeric($data['max']) &&
                           $data['min'] < $data['max'] &&
                           in_array($data['unit'], ['C', 'F']);
                },
                'message' => 'Temperature range must include min, max (numeric) and unit (C/F)'
            ],
            'humidity_range' => [
                'type' => 'json_object',
                'validator' => function($value) {
                    $data = json_decode($value, true);
                    return isset($data['min'], $data['max'], $data['unit']) &&
                           is_numeric($data['min']) && is_numeric($data['max']) &&
                           $data['min'] < $data['max'] &&
                           $data['unit'] === '%';
                },
                'message' => 'Humidity range must include min, max (numeric) and unit (%)'
            ],
            'quarantine_period' => [
                'pattern' => '/^[1-9][0-9]{0,1}$/',
                'message' => 'Quarantine period must be between 1 and 99 days'
            ]
        ],
        'customer' => [
            'credit_limit_default' => [
                'pattern' => '/^\d+(\.\d{1,2})?$/',
                'message' => 'Credit limit must be a positive number with up to 2 decimal places'
            ],
            'payment_terms_default' => [
                'pattern' => '/^[1-9][0-9]{0,2}$/',
                'message' => 'Payment terms must be between 1 and 999 days'
            ],
            'require_license' => [
                'type' => 'boolean',
                'message' => 'License requirement must be true or false'
            ],
            'license_expiry_warning' => [
                'pattern' => '/^[1-9][0-9]{0,2}$/',
                'message' => 'License expiry warning must be between 1 and 999 days'
            ]
        ],
        'supplier' => [
            'evaluation_period' => [
                'pattern' => '/^[1-9][0-9]{0,2}$/',
                'message' => 'Evaluation period must be between 1 and 999 days'
            ],
            'minimum_order_value' => [
                'pattern' => '/^\d+(\.\d{1,2})?$/',
                'message' => 'Minimum order value must be a positive number with up to 2 decimal places'
            ],
            'quality_threshold' => [
                'pattern' => '/^(100(\.0{1,2})?|[0-9]{1,2}(\.\d{1,2})?)$/',
                'message' => 'Quality threshold must be between 0 and 100'
            ]
        ],
        'document' => [
            'invoice_format' => [
                'pattern' => '/^[A-Z0-9\-\/\{\}]+$/',
                'message' => 'Invoice format must contain only uppercase letters, numbers, hyphens, and placeholders'
            ],
            'po_format' => [
                'pattern' => '/^[A-Z0-9\-\/\{\}]+$/',
                'message' => 'PO format must contain only uppercase letters, numbers, hyphens, and placeholders'
            ],
            'receipt_format' => [
                'pattern' => '/^[A-Z0-9\-\/\{\}]+$/',
                'message' => 'Receipt format must contain only uppercase letters, numbers, hyphens, and placeholders'
            ],
            'header_info' => [
                'type' => 'json_object',
                'validator' => function($value) {
                    $data = json_decode($value, true);
                    return isset($data['company_name'], $data['address'], 
                               $data['phone'], $data['email']) &&
                           filter_var($data['email'], FILTER_VALIDATE_EMAIL);
                },
                'message' => 'Header info must include company name, address, phone, and valid email'
            ]
        ],
        'compliance' => [
            'required_licenses' => [
                'type' => 'json_array',
                'message' => 'Required licenses must be a valid JSON array'
            ],
            'document_retention' => [
                'type' => 'json_object',
                'validator' => function($value) {
                    $data = json_decode($value, true);
                    foreach ($data as $period) {
                        if (!preg_match('/^\d+[ymd]$/', $period)) return false;
                    }
                    return true;
                },
                'message' => 'Document retention periods must be specified in years (y), months (m), or days (d)'
            ],
            'audit_frequency' => [
                'pattern' => '/^[1-9][0-9]{0,2}$/',
                'message' => 'Audit frequency must be between 1 and 999 days'
            ]
        ]
    ];

    public static function validateSetting($type, $key, $value) {
        // Handle JSON values
        if (is_string($value) && self::isJson($value)) {
            $value = json_decode($value, true);
        }

        // If value is an array, validate each field according to rules
        if (is_array($value)) {
            if (!isset(self::$settingRules[$type])) {
                throw new Exception("No validation rules defined for setting type: {$type}");
            }

            $errors = [];
            foreach ($value as $field => $fieldValue) {
                if (isset(self::$settingRules[$type][$field])) {
                    $rule = self::$settingRules[$type][$field];
                    
                    if (isset($rule['pattern'])) {
                        if (!preg_match($rule['pattern'], (string)$fieldValue)) {
                            $errors[] = $rule['message'];
                        }
                    } elseif (isset($rule['type'])) {
                        if (!self::validateType($fieldValue, $rule['type'])) {
                            $errors[] = $rule['message'];
                        }
                    } elseif (isset($rule['validator'])) {
                        if (!$rule['validator']($fieldValue)) {
                            $errors[] = $rule['message'];
                        }
                    }
                }
            }

            if (!empty($errors)) {
                throw new Exception("Validation failed: " . implode(", ", $errors));
            }

            return true;
        }

        // Handle single value validation
        if (isset(self::$settingRules[$type][$key])) {
            $rule = self::$settingRules[$type][$key];
            
            if (isset($rule['pattern'])) {
                if (!preg_match($rule['pattern'], (string)$value)) {
                    throw new Exception($rule['message']);
                }
            } elseif (isset($rule['type'])) {
                if (!self::validateType($value, $rule['type'])) {
                    throw new Exception($rule['message']);
                }
            } elseif (isset($rule['validator'])) {
                if (!$rule['validator']($value)) {
                    throw new Exception($rule['message']);
                }
            }
        }

        return true;
    }

    public static function validateType($value, $type) {
        switch ($type) {
            case 'int':
            case 'integer':
                return filter_var($value, FILTER_VALIDATE_INT) !== false;
            
            case 'float':
            case 'double':
                return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
            
            case 'bool':
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null;
            
            case 'email':
                return preg_match(self::$patterns['email'], $value);
            
            case 'url':
                return preg_match(self::$patterns['url'], $value);
            
            case 'date':
                return preg_match(self::$patterns['date'], $value);
            
            case 'time':
                return preg_match(self::$patterns['time'], $value);
            
            case 'json':
                return self::isJson($value);
            
            case 'json_array':
                if (!self::isJson($value)) return false;
                $data = json_decode($value);
                return is_array($data);
            
            case 'json_object':
                if (!self::isJson($value)) return false;
                $data = json_decode($value);
                return is_object($data);
            
            case 'color':
                return preg_match(self::$patterns['color'], $value);
            
            case 'ipv4':
                return preg_match(self::$patterns['ipv4'], $value);
            
            case 'timezone':
                return preg_match(self::$patterns['timezone'], $value);
            
            case 'bpom_number':
                return preg_match(self::$patterns['bpom_number'], $value);
            
            case 'sku':
                return preg_match(self::$patterns['sku'], $value);
            
            case 'percentage':
                return preg_match(self::$patterns['percentage'], $value);
            
            default:
                return true;
        }
    }

    private static function isJson($string) {
        if (!is_string($string)) {
            return false;
        }
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public static function sanitize($value, $type) {
        switch ($type) {
            case 'int':
            case 'integer':
                return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
            
            case 'float':
            case 'double':
                return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
            case 'email':
                return filter_var($value, FILTER_SANITIZE_EMAIL);
            
            case 'url':
                return filter_var($value, FILTER_SANITIZE_URL);
            
            case 'string':
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            
            default:
                return $value;
        }
    }
}
