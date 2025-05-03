<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/BaseModel.php';
require_once __DIR__ . '/../app/models/SecurityTest.php';
require_once __DIR__ . '/../app/helpers/AuditLogger.php';

/**
 * Security Test Runner Script
 * 
 * This script runs automated security tests and can be scheduled via cron
 * Usage: php run_security_tests.php [test_type]
 * test_type options: penetration, vulnerability, stress, all
 */

// Parse command line arguments
$testType = $argv[1] ?? 'all';
$validTypes = ['penetration', 'vulnerability', 'stress', 'all'];

if (!in_array($testType, $validTypes)) {
    die("Invalid test type. Valid options are: " . implode(', ', $validTypes) . "\n");
}

// Initialize security test model
$securityTest = new SecurityTest();
$auditLogger = AuditLogger::getInstance();

try {
    echo "Starting security tests...\n";
    $auditLogger->log('security', 'Starting automated security tests', ['type' => $testType]);

    // Run tests based on type
    if ($testType === 'all' || $testType === 'penetration') {
        echo "\nRunning penetration tests...\n";
        $results = $securityTest->runPenetrationTest();
        displayResults('Penetration Test Results', $results);
    }

    if ($testType === 'all' || $testType === 'vulnerability') {
        echo "\nRunning vulnerability scan...\n";
        $results = $securityTest->runVulnerabilityScan();
        displayResults('Vulnerability Scan Results', $results);
    }

    if ($testType === 'all' || $testType === 'stress') {
        echo "\nRunning stress tests...\n";
        $results = $securityTest->runStressTest();
        displayResults('Stress Test Results', $results);
    }

    echo "\nAll tests completed successfully.\n";
    $auditLogger->log('security', 'Automated security tests completed', ['type' => $testType]);

} catch (Exception $e) {
    $error = "Error running security tests: " . $e->getMessage() . "\n";
    echo $error;
    $auditLogger->log('security', 'Security test error', [
        'type' => $testType,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    exit(1);
}

/**
 * Display test results in a formatted way
 */
function displayResults($title, $results) {
    echo "\n=== $title ===\n";
    
    foreach ($results as $result) {
        // Handle different result types
        if (isset($result['name'])) {
            // Penetration/Vulnerability test results
            echo "\nTest: " . $result['name'];
            echo "\nStatus: " . $result['status'];
            echo "\nSeverity: " . ($result['severity'] ?? $result['risk_level'] ?? 'N/A');
            if (isset($result['description'])) {
                echo "\nDescription: " . $result['description'];
            }
            if (isset($result['impact'])) {
                echo "\nImpact: " . $result['impact'];
            }
            if (isset($result['recommendation'])) {
                echo "\nRecommendation: " . $result['recommendation'];
            }
            if (isset($result['fix_steps'])) {
                echo "\nFix Steps: " . $result['fix_steps'];
            }
        } else {
            // Stress test results
            echo "\nConcurrent Users: " . $result['concurrent_users'];
            echo "\nResponse Time: " . $result['response_time'] . "ms";
            echo "\nError Rate: " . $result['error_rate'] . "%";
            echo "\nCPU Usage: " . $result['cpu_usage'] . "%";
            echo "\nMemory Usage: " . $result['memory_usage'] . "MB";
        }
        echo "\n" . str_repeat('-', 50) . "\n";
    }
}

// Add to crontab for automated execution:
// 0 0 * * * /usr/bin/php /path/to/scripts/run_security_tests.php all >> /var/log/security_tests.log 2>&1
