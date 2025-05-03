-- Create settings validation logs table
CREATE TABLE setting_validations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_id INT NOT NULL,
    passed BOOLEAN NOT NULL DEFAULT 0,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (setting_id) REFERENCES settings(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create settings access logs table
CREATE TABLE setting_access_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_id INT NOT NULL,
    user_id INT NOT NULL,
    access_type ENUM('read', 'write') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (setting_id) REFERENCES settings(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create settings change logs table
CREATE TABLE setting_change_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_id INT NOT NULL,
    user_id INT NOT NULL,
    old_value TEXT,
    new_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (setting_id) REFERENCES settings(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create automation tasks table
CREATE TABLE automation_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('validation', 'cleanup', 'backup', 'report') NOT NULL,
    description TEXT,
    params JSON,
    interval INT NOT NULL COMMENT 'Interval in seconds',
    last_run TIMESTAMP NULL,
    active BOOLEAN NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create automation rules table
CREATE TABLE automation_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    condition_type ENUM('value_change', 'validation_fail', 'access_threshold') NOT NULL,
    condition_params JSON,
    action_type ENUM('notify', 'revert', 'backup', 'validate') NOT NULL,
    action_params JSON,
    active BOOLEAN NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create automation execution logs table
CREATE TABLE automation_execution_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    success BOOLEAN NOT NULL DEFAULT 0,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES automation_tasks(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default automation tasks
INSERT INTO automation_tasks (name, type, description, params, interval) VALUES
('Daily Settings Validation', 'validation', 'Validates all settings daily', '{}', 86400),
('Weekly Data Cleanup', 'cleanup', 'Cleans up old logs weekly', '{}', 604800),
('Daily Settings Backup', 'backup', 'Creates daily backup of all settings', '{}', 86400),
('Monthly Settings Report', 'report', 'Generates monthly settings report', '{"type": "monthly"}', 2592000);

-- Insert default automation rules
INSERT INTO automation_rules (name, description, condition_type, condition_params, action_type, action_params) VALUES
('Critical Setting Change', 'Notify on critical setting changes', 'value_change', 
 '{"settings": ["smtp.host", "security.key", "payment.gateway"]}', 'notify', 
 '{"notify_type": "email", "recipients": ["admin@example.com"]}'),
('Validation Failure Alert', 'Alert on validation failures', 'validation_fail', 
 '{"consecutive_failures": 3}', 'notify', 
 '{"notify_type": "email", "recipients": ["admin@example.com"]}'),
('High Access Alert', 'Alert on high access frequency', 'access_threshold', 
 '{"threshold": 1000, "period": 3600}', 'notify', 
 '{"notify_type": "email", "recipients": ["admin@example.com"]}');

-- Create indexes for better performance
CREATE INDEX idx_setting_validations_date ON setting_validations(created_at);
CREATE INDEX idx_setting_access_logs_date ON setting_access_logs(created_at);
CREATE INDEX idx_setting_change_logs_date ON setting_change_logs(created_at);
CREATE INDEX idx_automation_execution_logs_date ON automation_execution_logs(created_at);
