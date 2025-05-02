<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/SettingsService.php';

class BackupService {
    private $conn;
    private $settings;
    private $backupDir;
    private $currentUser;

    public function __construct($currentUser = null) {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->settings = new SettingsService();
        $this->currentUser = $currentUser;
        $this->backupDir = __DIR__ . '/../storage/backups';

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }
    }

    public function createBackup($type = 'full') {
        $timestamp = date('Y-m-d_His');
        $filename = "backup_{$type}_{$timestamp}";

        try {
            switch ($type) {
                case 'full':
                    return $this->createFullBackup($filename);
                case 'database':
                    return $this->createDatabaseBackup($filename);
                case 'files':
                    return $this->createFilesBackup($filename);
                default:
                    throw new Exception('Invalid backup type');
            }
        } catch (Exception $e) {
            $this->logError('backup_failed', [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function createFullBackup($filename) {
        // Create temporary directory
        $tempDir = $this->backupDir . '/temp_' . uniqid();
        mkdir($tempDir);

        try {
            // Backup database
            $dbFile = $this->createDatabaseBackup($filename, $tempDir);
            
            // Backup files
            $filesArchive = $this->createFilesBackup($filename, $tempDir);

            // Create manifest
            $manifest = $this->createBackupManifest($filename, 'full');
            file_put_contents($tempDir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

            // Create final archive
            $finalArchive = $this->backupDir . "/{$filename}.zip";
            $this->createZipArchive($tempDir, $finalArchive);

            // Cleanup
            $this->cleanupTempFiles($tempDir);

            // Log backup
            $this->logBackup($manifest);

            return [
                'filename' => basename($finalArchive),
                'path' => $finalArchive,
                'size' => filesize($finalArchive),
                'manifest' => $manifest
            ];

        } catch (Exception $e) {
            $this->cleanupTempFiles($tempDir);
            throw $e;
        }
    }

    private function createDatabaseBackup($filename, $dir = null) {
        $outputDir = $dir ?? $this->backupDir;
        $sqlFile = "{$outputDir}/{$filename}.sql";

        // Get all tables
        $tables = [];
        $result = $this->conn->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $output = "-- Database backup created on " . date('Y-m-d H:i:s') . "\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // Get create table syntax
            $result = $this->conn->query("SHOW CREATE TABLE `{$table}`");
            $row = $result->fetch(PDO::FETCH_NUM);
            $output .= "\n\n" . $row[1] . ";\n\n";

            // Get table data
            $result = $this->conn->query("SELECT * FROM `{$table}`");
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $fields = implode('`, `', array_keys($row));
                $values = array_map(function($value) {
                    return $this->conn->quote($value);
                }, $row);
                $values = implode(', ', $values);
                $output .= "INSERT INTO `{$table}` (`{$fields}`) VALUES ({$values});\n";
            }
        }

        $output .= "\nSET FOREIGN_KEY_CHECKS=1;";

        file_put_contents($sqlFile, $output);

        if (!$dir) {
            $zipFile = "{$outputDir}/{$filename}.zip";
            $zip = new ZipArchive();
            $zip->open($zipFile, ZipArchive::CREATE);
            $zip->addFile($sqlFile, basename($sqlFile));
            $zip->close();

            unlink($sqlFile);
            return $zipFile;
        }

        return $sqlFile;
    }

    private function createFilesBackup($filename, $dir = null) {
        $outputDir = $dir ?? $this->backupDir;
        $zipFile = "{$outputDir}/{$filename}_files.zip";

        $zip = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE);

        $excludeDirs = ['storage/backups', 'vendor', 'node_modules'];
        $this->addDirectoryToZip($zip, __DIR__ . '/..', '', $excludeDirs);

        $zip->close();

        return $zipFile;
    }

    private function createBackupManifest($filename, $type) {
        return [
            'id' => uniqid('backup_'),
            'filename' => $filename,
            'type' => $type,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $this->currentUser ? $this->currentUser->username : 'system',
            'version' => $this->settings->get('general', 'app_version'),
            'checksum' => null, // Will be added after file creation
            'size' => null, // Will be added after file creation
            'contents' => [
                'database' => true,
                'files' => true,
                'settings' => true
            ]
        ];
    }

    public function restore($backupFile, $options = []) {
        if (!file_exists($backupFile)) {
            throw new Exception('Backup file not found');
        }

        // Create temporary directory
        $tempDir = $this->backupDir . '/restore_' . uniqid();
        mkdir($tempDir);

        try {
            // Extract backup
            $zip = new ZipArchive();
            if ($zip->open($backupFile) !== true) {
                throw new Exception('Failed to open backup file');
            }
            $zip->extractTo($tempDir);
            $zip->close();

            // Verify manifest
            $manifest = json_decode(file_get_contents($tempDir . '/manifest.json'), true);
            if (!$manifest) {
                throw new Exception('Invalid backup manifest');
            }

            // Start transaction
            $this->conn->beginTransaction();

            try {
                // Restore database if included
                if ($manifest['contents']['database'] && (!$options['skip_database'] ?? false)) {
                    $this->restoreDatabase($tempDir . '/' . $manifest['filename'] . '.sql');
                }

                // Restore files if included
                if ($manifest['contents']['files'] && (!$options['skip_files'] ?? false)) {
                    $this->restoreFiles($tempDir . '/' . $manifest['filename'] . '_files.zip');
                }

                $this->conn->commit();

                // Log restoration
                $this->logRestore($manifest);

                return [
                    'success' => true,
                    'manifest' => $manifest
                ];

            } catch (Exception $e) {
                $this->conn->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            $this->logError('restore_failed', [
                'backup' => basename($backupFile),
                'error' => $e->getMessage()
            ]);
            throw $e;
        } finally {
            $this->cleanupTempFiles($tempDir);
        }
    }

    private function restoreDatabase($sqlFile) {
        $sql = file_get_contents($sqlFile);
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($sql) { return !empty($sql); }
        );

        foreach ($statements as $statement) {
            $this->conn->exec($statement);
        }
    }

    private function restoreFiles($zipFile) {
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new Exception('Failed to open files archive');
        }

        $baseDir = __DIR__ . '/..';
        $zip->extractTo($baseDir);
        $zip->close();
    }

    private function addDirectoryToZip($zip, $basePath, $relativePath, $excludeDirs) {
        $dir = $basePath . ($relativePath ? '/' . $relativePath : '');
        $files = scandir($dir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $filePath = $dir . '/' . $file;
            $zipPath = $relativePath ? $relativePath . '/' . $file : $file;

            if (is_dir($filePath)) {
                if (in_array($zipPath, $excludeDirs)) continue;
                $this->addDirectoryToZip($zip, $basePath, $zipPath, $excludeDirs);
            } else {
                $zip->addFile($filePath, $zipPath);
            }
        }
    }

    private function cleanupTempFiles($dir) {
        if (!is_dir($dir)) return;

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->cleanupTempFiles($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function createZipArchive($sourceDir, $outputFile) {
        $zip = new ZipArchive();
        if ($zip->open($outputFile, ZipArchive::CREATE) !== true) {
            throw new Exception('Failed to create zip archive');
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
    }

    private function logBackup($manifest) {
        $query = "INSERT INTO backup_log 
                (backup_id, type, filename, created_by, size, checksum)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $manifest['id'],
            $manifest['type'],
            $manifest['filename'],
            $manifest['created_by'],
            $manifest['size'],
            $manifest['checksum']
        ]);
    }

    private function logRestore($manifest) {
        $query = "INSERT INTO restore_log 
                (backup_id, restored_by, status, details)
                VALUES (?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $manifest['id'],
            $this->currentUser ? $this->currentUser->username : 'system',
            'success',
            json_encode([
                'version' => $manifest['version'],
                'timestamp' => date('Y-m-d H:i:s')
            ])
        ]);
    }

    private function logError($type, $details) {
        $query = "INSERT INTO error_log 
                (type, details, created_by)
                VALUES (?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $type,
            json_encode($details),
            $this->currentUser ? $this->currentUser->username : 'system'
        ]);
    }
}
?>
