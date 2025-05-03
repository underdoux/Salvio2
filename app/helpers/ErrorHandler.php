<?php

class ErrorHandler {
    private static $instance = null;
    private $baseUrl;

    private function __construct() {
        $this->baseUrl = '/Salvio2/public';
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Handle 404 Not Found errors
     */
    public function handle404($uri = null, $routes = []) {
        http_response_code(404);
        
        // Set debug info if in debug mode
        $debugInfo = defined('DEBUG_MODE') && DEBUG_MODE ? [
            'requested_uri' => $uri,
            'available_routes' => $routes
        ] : null;

        // Extract variables for the view
        $baseUrl = $this->baseUrl;
        
        // Include the 404 view
        require __DIR__ . '/../views/errors/404.php';
    }

    /**
     * Handle general errors
     */
    public function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            // This error code is not included in error_reporting
            return false;
        }

        $errorType = $this->getErrorType($errno);
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $debugInfo = [
                'type' => $errorType,
                'message' => $errstr,
                'file' => $errfile,
                'line' => $errline
            ];
        } else {
            $debugInfo = null;
        }

        // Log the error
        error_log("[$errorType] $errstr in $errfile on line $errline");

        // Extract variables for the view
        $baseUrl = $this->baseUrl;
        
        // Include the error view
        require __DIR__ . '/../views/errors/error.php';
        
        return true;
    }

    /**
     * Handle uncaught exceptions
     */
    public function handleException($exception) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $debugInfo = [
                'type' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
        } else {
            $debugInfo = null;
        }

        // Log the exception
        error_log($exception->getMessage() . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine());

        // Extract variables for the view
        $baseUrl = $this->baseUrl;
        
        // Include the error view
        require __DIR__ . '/../views/errors/error.php';
    }

    /**
     * Get human-readable error type
     */
    private function getErrorType($errno) {
        switch ($errno) {
            case E_ERROR:
                return 'Fatal Error';
            case E_WARNING:
                return 'Warning';
            case E_PARSE:
                return 'Parse Error';
            case E_NOTICE:
                return 'Notice';
            case E_CORE_ERROR:
                return 'Core Error';
            case E_CORE_WARNING:
                return 'Core Warning';
            case E_COMPILE_ERROR:
                return 'Compile Error';
            case E_COMPILE_WARNING:
                return 'Compile Warning';
            case E_USER_ERROR:
                return 'User Error';
            case E_USER_WARNING:
                return 'User Warning';
            case E_USER_NOTICE:
                return 'User Notice';
            case E_STRICT:
                return 'Strict Notice';
            case E_RECOVERABLE_ERROR:
                return 'Recoverable Error';
            case E_DEPRECATED:
                return 'Deprecated';
            case E_USER_DEPRECATED:
                return 'User Deprecated';
            default:
                return 'Unknown Error';
        }
    }
}
