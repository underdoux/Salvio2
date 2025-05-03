<?php

require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Mailer.php';

class ErrorReporter {
    private static $mailer;
    private static $errorLevels = [
        E_ERROR => 'Fatal Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Standards',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated'
    ];

    public static function init() {
        self::$mailer = new Mailer();
    }

    public static function reportError($error, $context = []) {
        $errorDetails = self::formatErrorDetails($error, $context);
        
        // Log the detailed error
        Logger::log($errorDetails);

        // Send email notification for severe errors
        if (self::isSevereError($error)) {
            self::notifyAdmin($errorDetails);
        }

        return $errorDetails;
    }

    private static function formatErrorDetails($error, $context) {
        $details = [];

        if ($error instanceof \Throwable) {
            $details = [
                'type' => get_class($error),
                'message' => $error->getMessage(),
                'code' => $error->getCode(),
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'trace' => $error->getTraceAsString()
            ];
        } else if (is_array($error)) {
            $details = $error;
        } else {
            $details = ['message' => (string)$error];
        }

        // Add error level description if available
        if (isset($details['code']) && isset(self::$errorLevels[$details['code']])) {
            $details['level'] = self::$errorLevels[$details['code']];
        }

        // Add context information
        if (!empty($context)) {
            $details['context'] = $context;
        }

        // Add request information if available
        if (!empty($_SERVER)) {
            $details['request'] = [
                'url' => $_SERVER['REQUEST_URI'] ?? 'N/A',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }

        // Add session information if available
        if (isset($_SESSION)) {
            $details['session'] = [
                'id' => session_id(),
                'user_id' => $_SESSION['user_id'] ?? 'N/A'
            ];
        }

        return $details;
    }

    private static function isSevereError($error) {
        if ($error instanceof \Throwable) {
            return in_array($error->getCode(), [
                E_ERROR,
                E_CORE_ERROR,
                E_COMPILE_ERROR,
                E_USER_ERROR,
                E_RECOVERABLE_ERROR
            ]);
        }
        return false;
    }

    private static function notifyAdmin($errorDetails) {
        $subject = "Critical Error Report - " . date('Y-m-d H:i:s');
        $message = self::formatEmailMessage($errorDetails);
        
        try {
            self::$mailer->sendProfitCalculationNotification([
                'status' => 'error',
                'subject' => $subject,
                'message' => $message,
                'details' => $errorDetails
            ]);
        } catch (\Exception $e) {
            Logger::log("Failed to send error notification email: " . $e->getMessage());
        }
    }

    private static function formatEmailMessage($errorDetails) {
        $message = "<h2>Critical Error Report</h2>\n\n";
        
        foreach ($errorDetails as $key => $value) {
            if (is_array($value)) {
                $message .= "<h3>" . ucfirst($key) . ":</h3>\n";
                $message .= "<pre>" . print_r($value, true) . "</pre>\n\n";
            } else {
                $message .= "<strong>" . ucfirst($key) . ":</strong> " . $value . "\n\n";
            }
        }

        return $message;
    }

    public static function getErrorSummary($period = 'day') {
        // Read from log file and generate summary
        $logFile = __DIR__ . '/../../storage/logs/app.log';
        $summary = [
            'total_errors' => 0,
            'error_types' => [],
            'most_frequent' => null,
            'recent_errors' => []
        ];

        if (file_exists($logFile)) {
            $logs = file($logFile);
            $startTime = self::getStartTime($period);

            foreach ($logs as $log) {
                if (strpos($log, '[ERROR]') !== false) {
                    $logTime = strtotime(substr($log, 0, 19));
                    if ($logTime >= $startTime) {
                        $summary['total_errors']++;
                        
                        // Extract error type
                        if (preg_match('/\[ERROR\]\s*\[(.*?)\]/', $log, $matches)) {
                            $errorType = $matches[1];
                            $summary['error_types'][$errorType] = 
                                ($summary['error_types'][$errorType] ?? 0) + 1;
                        }

                        // Keep track of recent errors
                        if (count($summary['recent_errors']) < 10) {
                            $summary['recent_errors'][] = $log;
                        }
                    }
                }
            }

            // Find most frequent error
            if (!empty($summary['error_types'])) {
                arsort($summary['error_types']);
                $summary['most_frequent'] = array_key_first($summary['error_types']);
            }
        }

        return $summary;
    }

    private static function getStartTime($period) {
        $now = time();
        switch ($period) {
            case 'hour':
                return $now - 3600;
            case 'day':
                return $now - 86400;
            case 'week':
                return $now - 604800;
            case 'month':
                return $now - 2592000;
            default:
                return $now - 86400; // Default to day
        }
    }
}

// Initialize the error reporter
ErrorReporter::init();
