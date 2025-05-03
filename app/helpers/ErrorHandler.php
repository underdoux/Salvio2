<?php

require_once __DIR__ . '/Logger.php';

class ErrorHandler {
    public static function handleException($exception) {
        $message = "Exception: " . $exception->getMessage() . "\n";
        $message .= "File: " . $exception->getFile() . " on line " . $exception->getLine() . "\n";
        $message .= "Stack trace:\n" . $exception->getTraceAsString();

        // Log the error
        Logger::log($message);

        // Optionally, send an email to admin or notify via other channels
        // For now, just output a generic error message
        if (php_sapi_name() !== 'cli') {
            http_response_code(500);
            echo "An unexpected error occurred. Please try again later.";
        }

        // Stop script execution
        exit(1);
    }

    public static function register() {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
    }

    public static function handleError($errno, $errstr, $errfile, $errline) {
        $message = "Error [$errno]: $errstr in $errfile on line $errline";
        Logger::log($message);

        // Convert error to exception
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
}
