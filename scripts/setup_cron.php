<?php

// Get the absolute path to the project root
$projectRoot = dirname(__DIR__);

// Get the path to the monthly profit calculation script
$scriptPath = $projectRoot . '/scripts/calculate_monthly_profits.php';

// Create the crontab entry
// Run at 00:01 on the first day of each month
$cronEntry = "1 0 1 * * php {$scriptPath} >> {$projectRoot}/storage/logs/cron.log 2>&1\n";

// Write the crontab entry to a temporary file
$tempFile = tempnam(sys_get_temp_dir(), 'cron');
file_put_contents($tempFile, $cronEntry);

// Add the cron job
exec("crontab -l > {$tempFile}_existing 2>/dev/null");
file_put_contents($tempFile, file_get_contents($tempFile . '_existing') . $cronEntry);
exec("crontab {$tempFile}");

// Clean up temporary files
unlink($tempFile);
unlink($tempFile . '_existing');

echo "Cron job has been set up successfully.\n";
echo "The monthly profit calculation will run at 00:01 on the first day of each month.\n";
echo "Logs will be written to: {$projectRoot}/storage/logs/cron.log\n";

// Create log directory if it doesn't exist
if (!file_exists($projectRoot . '/storage/logs')) {
    mkdir($projectRoot . '/storage/logs', 0755, true);
}

// Create empty log file if it doesn't exist
$logFile = $projectRoot . '/storage/logs/cron.log';
if (!file_exists($logFile)) {
    touch($logFile);
    chmod($logFile, 0644);
}

echo "Log file has been initialized.\n";
