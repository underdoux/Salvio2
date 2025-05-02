CREATE TABLE report_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL COMMENT 'Type of report (sales, inventory, etc.)',
    frequency ENUM('daily', 'weekly', 'monthly') NOT NULL,
    params JSON COMMENT 'Report parameters (filters, date ranges, etc.)',
    recipients JSON COMMENT 'Array of email addresses to receive the report',
    last_run TIMESTAMP NULL COMMENT 'When the report was last generated',
    next_run TIMESTAMP NULL COMMENT 'When the report should next be generated',
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_type_frequency (type, frequency),
    INDEX idx_next_run (next_run),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE report_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NULL COMMENT 'NULL for ad-hoc reports',
    type VARCHAR(50) NOT NULL,
    params JSON,
    file_path VARCHAR(255) NOT NULL,
    file_size INT NOT NULL COMMENT 'File size in bytes',
    generated_by INT NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('success', 'failed') NOT NULL DEFAULT 'success',
    error_message TEXT,
    FOREIGN KEY (schedule_id) REFERENCES report_schedules(id) ON DELETE SET NULL,
    FOREIGN KEY (generated_by) REFERENCES users(id),
    INDEX idx_type_status (type, status),
    INDEX idx_generated_at (generated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add report-related settings
INSERT INTO settings (category, name, value, type, description) VALUES
('reports', 'retention_period', '90', 'integer', 'Number of days to keep report files'),
('reports', 'max_file_size', '10485760', 'integer', 'Maximum report file size in bytes (10MB)'),
('reports', 'default_format', 'xlsx', 'string', 'Default report file format'),
('reports', 'logo_path', '/assets/images/logo.png', 'string', 'Path to logo for report headers'),
('reports', 'company_details', '{"name":"Company Name","address":"Company Address","phone":"Phone Number","email":"Email"}', 'json', 'Company details for report headers');

-- Add report permissions
INSERT INTO permissions (name, description) VALUES
('view_reports', 'Can view and download reports'),
('generate_reports', 'Can generate new reports'),
('schedule_reports', 'Can create and manage report schedules'),
('manage_report_settings', 'Can manage report settings');

-- Assign permissions to admin role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'admin'),
    id 
FROM permissions 
WHERE name IN ('view_reports', 'generate_reports', 'schedule_reports', 'manage_report_settings');

-- Create reports directory if it doesn't exist
CREATE TABLE IF NOT EXISTS system_init (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task VARCHAR(255) NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO system_init (task) VALUES 
('CREATE DIRECTORY storage/reports'),
('CREATE DIRECTORY storage/reports/archive');

-- Add report metrics table for analytics
CREATE TABLE report_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    type VARCHAR(50) NOT NULL,
    total_generated INT DEFAULT 0,
    total_scheduled INT DEFAULT 0,
    total_ad_hoc INT DEFAULT 0,
    total_size BIGINT DEFAULT 0 COMMENT 'Total size of generated files in bytes',
    avg_generation_time FLOAT DEFAULT 0 COMMENT 'Average report generation time in seconds',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date_type (date, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better performance
ALTER TABLE report_history 
ADD INDEX idx_schedule_generated (schedule_id, generated_at),
ADD INDEX idx_type_generated (type, generated_at);

ALTER TABLE report_metrics
ADD INDEX idx_date_metrics (date, total_generated, total_scheduled);
