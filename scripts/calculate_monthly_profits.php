<?php

require_once __DIR__ . '/../app/helpers/Logger.php';
require_once __DIR__ . '/../app/models/ProfitSharing.php';

// Lock file to prevent concurrent execution
$lockFile = __DIR__ . '/../storage/locks/profit_calculation.lock';
$lockFp = fopen($lockFile, 'c');

// Try to get an exclusive lock
if (!flock($lockFp, LOCK_EX | LOCK_NB)) {
    Logger::log("Monthly profit calculation is already running");
    exit(1);
}

try {
    Logger::log("Starting monthly profit calculation");
    
    // Create lock directory if it doesn't exist
    if (!file_exists(dirname($lockFile))) {
        mkdir(dirname($lockFile), 0755, true);
    }

    // Record start time
    $startTime = microtime(true);
    
    // Initialize ProfitSharing model
    $profitSharing = new ProfitSharing();
    
    // Check if calculation for current month already exists
    if ($profitSharing->isProfitCalculated()) {
        Logger::log("Profit for current month has already been calculated");
        exit(0);
    }
    
    // Calculate profits
    Logger::log("Calculating profits for previous month");
    $result = $profitSharing->calculateMonthlyProfit();
    
    if (!$result) {
        throw new Exception("Failed to calculate monthly profits");
    }
    
    // Save calculation results
    Logger::log("Saving profit calculation results");
    $profitSharing->saveProfitCalculation($result);
    
    // Calculate and save distributions
    Logger::log("Calculating profit distributions");
    $distributions = $profitSharing->calculateDistributions($result['net_profit']);
    $profitSharing->saveDistributions($distributions);
    
    // Record execution time
    $executionTime = microtime(true) - $startTime;
    Logger::log("Monthly profit calculation completed in " . number_format($executionTime, 2) . " seconds");
    
    // Send success notification
    Logger::log("Sending success notification");
    $profitSharing->sendCalculationNotification([
        'status' => 'success',
        'execution_time' => $executionTime,
        'total_profit' => $result['net_profit'],
        'distribution_count' => count($distributions)
    ]);
    
} catch (Exception $e) {
    Logger::log("Error in monthly profit calculation: " . $e->getMessage());
    
    // Send failure notification
    if (isset($profitSharing)) {
        $profitSharing->sendCalculationNotification([
            'status' => 'error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    exit(1);
} finally {
    // Release the lock
    flock($lockFp, LOCK_UN);
    fclose($lockFp);
    
    // Clean up old lock file
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
}

exit(0);
