<?php

require_once __DIR__ . '/../app/helpers/SecurityTester.php';

// Run security tests
$tester = SecurityTester::getInstance();
$results = $tester->runTests();

// Format results for display
$totalTests = 0;
$totalPassed = 0;

echo "\nSecurity Test Results\n";
echo "===================\n\n";

foreach ($results as $category => $result) {
    echo ucfirst(str_replace('_', ' ', $category)) . ":\n";
    echo str_repeat('-', strlen($category) + 1) . "\n";
    
    $totalTests += ($result['passed'] + $result['failed']);
    $totalPassed += $result['passed'];
    
    echo "Passed: {$result['passed']}\n";
    echo "Failed: {$result['failed']}\n";
    
    if ($result['failed'] > 0) {
        echo "\nFailed Tests:\n";
        foreach ($result['details'] as $test => $detail) {
            if (!$detail['passed']) {
                echo "- {$test}\n";
                echo "  Expected: " . json_encode($detail['expected']) . "\n";
                echo "  Received: " . json_encode($detail['received']) . "\n";
            }
        }
    }
    echo "\n";
}

$passRate = ($totalPassed / $totalTests) * 100;

echo "Overall Results:\n";
echo "===============\n";
echo "Total Tests: {$totalTests}\n";
echo "Passed: {$totalPassed}\n";
echo "Failed: " . ($totalTests - $totalPassed) . "\n";
echo "Pass Rate: " . number_format($passRate, 2) . "%\n";
