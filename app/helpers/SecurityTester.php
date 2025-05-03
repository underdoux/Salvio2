<?php

class SecurityTester {
    private $settings;
    private $auditLogger;
    private static $instance = null;

    private function __construct() {
        $this->settings = new Settings();
        $this->auditLogger = AuditLogger::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Run comprehensive security tests
     */
    public function runTests($options = []) {
        $results = [
            'csrf' => $this->testCSRFProtection(),
            'rate_limiting' => $this->testRateLimiting(),
            'input_validation' => $this->testInputValidation(),
            'authentication' => $this->test2FAEnforcement(),
            'permissions' => $this->testPermissions(),
            'encryption' => $this->testEncryption(),
            'audit_logging' => $this->testAuditLogging(),
            'session_security' => $this->testSessionSecurity(),
            'xss' => $this->testXSSPrevention(),
            'sql_injection' => $this->testSQLInjection()
        ];

        $this->logTestResults($results);
        return $results;
    }

    /**
     * Test CSRF Protection
     */
    private function testCSRFProtection() {
        $tests = [];
        
        // Test missing token
        $tests['missing_token'] = $this->simulateRequest('/settings/update', 'POST', [
            'key' => 'test.setting',
            'value' => 'test'
        ]);

        // Test invalid token
        $tests['invalid_token'] = $this->simulateRequest('/settings/update', 'POST', [
            'key' => 'test.setting',
            'value' => 'test',
            'csrf_token' => 'invalid_token'
        ]);

        // Test token replay
        $validToken = $_SESSION['csrf_token'];
        $tests['token_replay'] = $this->simulateRequest('/settings/update', 'POST', [
            'key' => 'test.setting',
            'value' => 'test',
            'csrf_token' => $validToken
        ], true);

        return $this->analyzeResults($tests, ['missing_token' => 403, 'invalid_token' => 403, 'token_replay' => 403]);
    }

    /**
     * Test Rate Limiting
     */
    private function testRateLimiting() {
        $tests = [];
        $endpoint = '/settings/update';
        $data = ['key' => 'test.setting', 'value' => 'test'];

        // Test rapid requests
        for ($i = 0; $i < 25; $i++) {
            $tests["request_{$i}"] = $this->simulateRequest($endpoint, 'POST', $data);
        }

        // Test type-specific limits
        $sensitiveData = ['key' => 'security.key', 'value' => 'test'];
        for ($i = 0; $i < 5; $i++) {
            $tests["sensitive_request_{$i}"] = $this->simulateRequest($endpoint, 'POST', $sensitiveData);
        }

        return $this->analyzeResults($tests, [
            'request_21' => 429,
            'sensitive_request_4' => 429
        ]);
    }

    /**
     * Test Input Validation
     */
    private function testInputValidation() {
        $tests = [];
        $endpoint = '/settings/update';

        // Test various invalid inputs
        $invalidInputs = [
            'empty' => ['key' => '', 'value' => ''],
            'sql_injection' => ['key' => "test'; DROP TABLE settings; --", 'value' => 'test'],
            'xss' => ['key' => 'test', 'value' => '<script>alert("xss")</script>'],
            'invalid_json' => ['key' => 'test.json', 'value' => '{invalid:json}'],
            'invalid_email' => ['key' => 'smtp.email', 'value' => 'invalid-email'],
            'invalid_number' => ['key' => 'limit.value', 'value' => 'not-a-number']
        ];

        foreach ($invalidInputs as $type => $data) {
            $tests[$type] = $this->simulateRequest($endpoint, 'POST', $data);
        }

        return $this->analyzeResults($tests, array_fill_keys(array_keys($invalidInputs), 400));
    }

    /**
     * Test 2FA Enforcement
     */
    private function test2FAEnforcement() {
        $tests = [];
        $endpoint = '/settings/update';
        $sensitiveSettings = [
            'smtp.password',
            'security.key',
            'payment.gateway_key'
        ];

        foreach ($sensitiveSettings as $key) {
            // Test without 2FA
            $tests["{$key}_no_2fa"] = $this->simulateRequest($endpoint, 'POST', [
                'key' => $key,
                'value' => 'test'
            ]);

            // Test with invalid 2FA code
            $tests["{$key}_invalid_2fa"] = $this->simulateRequest($endpoint, 'POST', [
                'key' => $key,
                'value' => 'test',
                'verification_code' => '000000'
            ]);
        }

        return $this->analyzeResults($tests, array_fill_keys(array_keys($tests), 401));
    }

    /**
     * Test Permissions
     */
    private function testPermissions() {
        $tests = [];
        $endpoint = '/settings/update';
        $roles = ['anonymous', 'user', 'admin'];
        $settings = [
            'basic.setting' => 'test',
            'security.key' => 'sensitive',
            'system.config' => 'admin_only'
        ];

        foreach ($roles as $role) {
            foreach ($settings as $key => $value) {
                $tests["{$role}_{$key}"] = $this->simulateRequest($endpoint, 'POST', [
                    'key' => $key,
                    'value' => $value
                ], false, $role);
            }
        }

        return $this->analyzeResults($tests, [
            'anonymous_basic.setting' => 401,
            'anonymous_security.key' => 401,
            'anonymous_system.config' => 401,
            'user_security.key' => 403,
            'user_system.config' => 403
        ]);
    }

    /**
     * Test Encryption
     */
    private function testEncryption() {
        $tests = [];
        $sensitiveValue = 'sensitive_data_' . time();
        
        // Test encryption
        $encrypted = $this->settings->set('security.test', $sensitiveValue);
        $tests['encryption'] = $this->checkDatabaseValue('security.test', $sensitiveValue);
        
        // Test decryption
        $decrypted = $this->settings->get('security.test');
        $tests['decryption'] = ($decrypted === $sensitiveValue);

        return $this->analyzeResults($tests, ['encryption' => true, 'decryption' => true]);
    }

    /**
     * Test Audit Logging
     */
    private function testAuditLogging() {
        $tests = [];
        $testKey = 'test.audit';
        $testValue = 'audit_test_' . time();

        // Make a change
        $this->settings->set($testKey, $testValue);

        // Check audit log
        $log = $this->auditLogger->getAuditTrail($testKey, [
            'limit' => 1,
            'order' => 'DESC'
        ]);

        $tests['log_exists'] = !empty($log);
        $tests['log_complete'] = $this->validateAuditLog($log[0] ?? null, $testKey, $testValue);

        return $this->analyzeResults($tests, ['log_exists' => true, 'log_complete' => true]);
    }

    /**
     * Test Session Security
     */
    private function testSessionSecurity() {
        $tests = [];
        
        // Test session fixation
        $tests['session_fixation'] = $this->testSessionFixation();
        
        // Test session timeout
        $tests['session_timeout'] = $this->testSessionTimeout();
        
        // Test concurrent sessions
        $tests['concurrent_sessions'] = $this->testConcurrentSessions();

        return $this->analyzeResults($tests, [
            'session_fixation' => true,
            'session_timeout' => true,
            'concurrent_sessions' => true
        ]);
    }

    /**
     * Test XSS Prevention
     */
    private function testXSSPrevention() {
        $tests = [];
        $xssPayloads = [
            '<script>alert("xss")</script>',
            'javascript:alert("xss")',
            '<img src="x" onerror="alert(\'xss\')">',
            '<svg/onload=alert("xss")>',
            '"><script>alert("xss")</script>'
        ];

        foreach ($xssPayloads as $i => $payload) {
            $tests["xss_{$i}"] = $this->simulateRequest('/settings/update', 'POST', [
                'key' => 'test.xss',
                'value' => $payload
            ]);
        }

        return $this->analyzeResults($tests, array_fill_keys(array_keys($tests), 400));
    }

    /**
     * Test SQL Injection Prevention
     */
    private function testSQLInjection() {
        $tests = [];
        $sqlInjectionPayloads = [
            "'; DROP TABLE settings; --",
            "' OR '1'='1",
            "' UNION SELECT * FROM users; --",
            "'; INSERT INTO settings VALUES ('hack','hack'); --",
            "' OR 'x'='x"
        ];

        foreach ($sqlInjectionPayloads as $i => $payload) {
            $tests["sql_{$i}"] = $this->simulateRequest('/settings/update', 'POST', [
                'key' => $payload,
                'value' => 'test'
            ]);
        }

        return $this->analyzeResults($tests, array_fill_keys(array_keys($tests), 400));
    }

    /**
     * Simulate HTTP request
     */
    private function simulateRequest($endpoint, $method, $data, $newSession = false, $role = null) {
        if ($newSession) {
            session_regenerate_id(true);
        }

        if ($role !== null) {
            $_SESSION['user_role'] = $role;
        }

        // Simulate request and return response
        try {
            $response = file_get_contents("http://localhost{$endpoint}", false, stream_context_create([
                'http' => [
                    'method' => $method,
                    'header' => 'Content-Type: application/json\r\n',
                    'content' => json_encode($data)
                ]
            ]));
            return json_decode($response, true);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Analyze test results
     */
    private function analyzeResults($tests, $expectedResults) {
        $results = [
            'passed' => 0,
            'failed' => 0,
            'details' => []
        ];

        foreach ($tests as $test => $response) {
            $expected = $expectedResults[$test] ?? null;
            $passed = $this->compareResult($response, $expected);
            
            $results['details'][$test] = [
                'passed' => $passed,
                'expected' => $expected,
                'received' => $response
            ];

            $passed ? $results['passed']++ : $results['failed']++;
        }

        return $results;
    }

    /**
     * Compare test result with expected outcome
     */
    private function compareResult($response, $expected) {
        if (is_array($response) && isset($response['status'])) {
            return $response['status'] === $expected;
        }
        return $response === $expected;
    }

    /**
     * Log test results
     */
    private function logTestResults($results) {
        $this->auditLogger->logSecurityEvent('security_test', [
            'total_tests' => array_sum(array_map(function($r) {
                return $r['passed'] + $r['failed'];
            }, $results)),
            'passed_tests' => array_sum(array_map(function($r) {
                return $r['passed'];
            }, $results)),
            'failed_tests' => array_sum(array_map(function($r) {
                return $r['failed'];
            }, $results)),
            'details' => $results
        ], ['severity' => 'info']);
    }

    /**
     * Check raw database value
     */
    private function checkDatabaseValue($key, $value) {
        $db = Database::getInstance();
        $result = $db->query("SELECT value FROM settings WHERE key = ?", [$key]);
        return $result[0]['value'] !== $value;
    }

    /**
     * Validate audit log entry
     */
    private function validateAuditLog($log, $key, $value) {
        if (!$log) return false;
        
        return isset($log['setting_key']) &&
               isset($log['new_value']) &&
               isset($log['timestamp']) &&
               isset($log['user_id']) &&
               $log['setting_key'] === $key;
    }

    /**
     * Test session fixation protection
     */
    private function testSessionFixation() {
        $oldSessionId = session_id();
        $this->simulateRequest('/auth/login', 'POST', [
            'username' => 'test',
            'password' => 'test'
        ]);
        return session_id() !== $oldSessionId;
    }

    /**
     * Test session timeout
     */
    private function testSessionTimeout() {
        $_SESSION['LAST_ACTIVITY'] = time() - 3600; // 1 hour ago
        $response = $this->simulateRequest('/settings/get', 'GET', []);
        return isset($response['status']) && $response['status'] === 401;
    }

    /**
     * Test concurrent session handling
     */
    private function testConcurrentSessions() {
        $sessionToken = md5(uniqid());
        $_SESSION['token'] = $sessionToken;
        
        // Simulate login from another device
        $this->simulateRequest('/auth/login', 'POST', [
            'username' => 'test',
            'password' => 'test'
        ], true);

        return $_SESSION['token'] !== $sessionToken;
    }
}
