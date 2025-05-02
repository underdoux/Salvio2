<?php

// Load database configuration
$config = require_once __DIR__ . '/../config/database.php';

try {
    // Create database connection
    $dsn = "mysql:host={$config['host']};charset={$config['charset']}";
    $db = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    // Create database if not exists
    $sql = "CREATE DATABASE IF NOT EXISTS {$config['dbname']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $db->exec($sql);
    echo "Database '{$config['dbname']}' created or already exists.\n";
    
    // Select the database
    $db->exec("USE {$config['dbname']}");
    
    // Get all migration files
    $migrations = glob(__DIR__ . '/migrations/*.sql');
    sort($migrations); // Sort to ensure correct order
    
    // Execute each migration file
    foreach ($migrations as $migration) {
        $sql = file_get_contents($migration);
        
        try {
            $db->exec($sql);
            echo "Executed migration: " . basename($migration) . "\n";
        } catch (PDOException $e) {
            echo "Error executing " . basename($migration) . ": " . $e->getMessage() . "\n";
        }
    }
    
    // Execute seeders if they exist
    $seeders = glob(__DIR__ . '/seeders/*.sql');
    foreach ($seeders as $seeder) {
        $sql = file_get_contents($seeder);
        
        try {
            $db->exec($sql);
            echo "Executed seeder: " . basename($seeder) . "\n";
        } catch (PDOException $e) {
            echo "Error executing " . basename($seeder) . ": " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nDatabase setup completed successfully!\n";
    
} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage() . "\n");
}
