-- Create backup log table
CREATE TABLE backup_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_id VARCHAR(50) NOT NULL COMMENT 'Unique identifier for the backup',
    type ENUM('full', 'database', 'files') NOT NULL,
    filename VARCHAR(255) NOT NULL,
    created_by VARCHAR(100) NOT NULL,
    size BIGINT NOT NULL COMMENT 'Backup file size in bytes',
    checksum VARCHAR(64) NOT NULL COMMENT 'SHA-256 checksum of backup file',
    status ENUM('success', 'failed') DEFAULT 'success',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_backup_id (backup_id),
    INDEX idx_type_status (type, status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create restore log table
CREATE TABLE restore_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_id VARCHAR(50) NOT NULL,
    restored_by VARCHAR(100) NOT NULL,
    status ENUM('success', 'failed') NOT NULL,
    details JSON COMMENT 'Additional restore details',
    error_message TEXT,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (backup_id) REFERENCES backup_log(backup_id),
    INDEX idx_backup_status (backup_id, status),
    INDEX idx_started_at (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create backup schedule table
CREATE TABLE backup_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('full', 'database', 'files') NOT NULL,
    frequency ENUM('daily', 'weekly', 'monthly') NOT NULL,
    time_of_day TIME NOT NULL DEFAULT '00:00:00',
    day_of_week TINYINT NULL COMMENT 'For weekly backups (1-7, Monday-Sunday)',
    day_of_month TINYINT NULL COMMENT 'For monthly backups (1-31)',
    retention_days INT NOT NULL DEFAULT 30,
    is_active BOOLEAN DEFAULT TRUE,
    last_run TIMESTAMP NULL,
    next_run TIMESTAMP NULL,
    created_by VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_next_run (next_run, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create backup storage locations table
CREATE TABLE backup_storage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('local', 'ftp', 's3', 'google_drive') NOT NULL,
    config JSON NOT NULL COMMENT 'Storage-specific configuration',
    is_active BOOLEAN DEFAULT TRUE,
    last_sync TIMESTAMP NULL,
    created_by VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create backup file locations table
CREATE TABLE backup_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_id VARCHAR(50) NOT NULL,
    storage_id INT NOT NULL,
    path VARCHAR(255) NOT NULL,
    synced_at TIMESTAMP NULL,
    status ENUM('pending', 'synced', 'failed') DEFAULT 'pending',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (backup_id) REFERENCES backup_log(backup_id),
    FOREIGN KEY (storage_id) REFERENCES backup_storage(id),
    INDEX idx_backup_storage (backup_id, storage_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add backup-related permissions
INSERT INTO permissions (name, description) VALUES
('manage_backups', 'Can create and manage backups'),
('view_backups', 'Can view backup history'),
('restore_backups', 'Can restore from backups'),
('manage_backup_schedule', 'Can manage backup schedules');

-- Assign permissions to admin role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'admin'),
    id 
FROM permissions 
WHERE name IN (
    'manage_backups',
    'view_backups',
    'restore_backups',
    'manage_backup_schedule'
);

-- Insert default backup schedule
INSERT INTO backup_schedule 
(type, frequency, time_of_day, retention_days, created_by) VALUES
('full', 'weekly', '02:00:00', 30, 'system'),
('database', 'daily', '03:00:00', 7, 'system');

-- Insert default local storage
INSERT INTO backup_storage 
(name, type, config, created_by) VALUES
('Local Storage', 'local', '{"path": "storage/backups"}', 'system');

-- Create indexes for better performance
ALTER TABLE backup_log
ADD INDEX idx_created_by_date (created_by, created_at);

ALTER TABLE restore_log
ADD INDEX idx_restored_by_date (restored_by, started_at);

ALTER TABLE backup_schedule
ADD INDEX idx_type_frequency (type, frequency);

ALTER TABLE backup_storage
ADD INDEX idx_type_active (type, is_active);

-- Add trigger to update next_run in backup_schedule
DELIMITER //
CREATE TRIGGER update_next_run_after_insert
AFTER INSERT ON backup_schedule
FOR EACH ROW
BEGIN
    UPDATE backup_schedule 
    SET next_run = CASE
        WHEN frequency = 'daily' THEN
            TIMESTAMP(CURRENT_DATE() + INTERVAL 1 DAY, NEW.time_of_day)
        WHEN frequency = 'weekly' THEN
            TIMESTAMP(CURRENT_DATE() + INTERVAL (7 + NEW.day_of_week - DAYOFWEEK(CURRENT_DATE())) % 7 DAY, NEW.time_of_day)
        WHEN frequency = 'monthly' THEN
            TIMESTAMP(DATE_ADD(CURRENT_DATE(), INTERVAL 1 MONTH) - INTERVAL DAY(CURRENT_DATE()) DAY + INTERVAL NEW.day_of_month DAY, NEW.time_of_day)
        END
    WHERE id = NEW.id;
END //
DELIMITER ;

-- Add trigger to update next_run after backup completion
DELIMITER //
CREATE TRIGGER update_next_run_after_backup
AFTER INSERT ON backup_log
FOR EACH ROW
BEGIN
    UPDATE backup_schedule bs
    SET 
        last_run = NOW(),
        next_run = CASE
            WHEN bs.frequency = 'daily' THEN
                TIMESTAMP(CURRENT_DATE() + INTERVAL 1 DAY, bs.time_of_day)
            WHEN bs.frequency = 'weekly' THEN
                TIMESTAMP(CURRENT_DATE() + INTERVAL (7 + bs.day_of_week - DAYOFWEEK(CURRENT_DATE())) % 7 DAY, bs.time_of_day)
            WHEN bs.frequency = 'monthly' THEN
                TIMESTAMP(DATE_ADD(CURRENT_DATE(), INTERVAL 1 MONTH) - INTERVAL DAY(CURRENT_DATE()) DAY + INTERVAL bs.day_of_month DAY, bs.time_of_day)
            END
    WHERE bs.type = NEW.type;
END //
DELIMITER ;
