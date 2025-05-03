<?php

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            // Load configuration with defaults
            $config = $this->loadConfig();
            
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            $this->connection = new PDO(
                $dsn, 
                $config['username'], 
                $config['password'], 
                $config['options']
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            Logger::log("Database connection failed: " . $e->getMessage(), 'ERROR');
            throw new Exception("Database connection failed");
        }
    }

    private function loadConfig() {
        $defaultConfig = [
            'host' => 'localhost',
            'dbname' => 'salvio_pos',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ];

        $configFile = __DIR__ . '/../../config/database.php';
        if (file_exists($configFile)) {
            $fileConfig = require $configFile;
            if (is_array($fileConfig)) {
                return array_merge($defaultConfig, $fileConfig);
            }
        }

        return $defaultConfig;
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}
