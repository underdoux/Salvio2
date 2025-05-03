-- Create settings dependencies table
CREATE TABLE setting_dependencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_id INT NOT NULL,
    depends_on_setting_id INT NOT NULL,
    condition_type ENUM('required', 'conflicts', 'affects') NOT NULL,
    condition_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (setting_id) REFERENCES settings(id) ON DELETE CASCADE,
    FOREIGN KEY (depends_on_setting_id) REFERENCES settings(id) ON DELETE CASCADE,
    UNIQUE KEY unique_dependency (setting_id, depends_on_setting_id, condition_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create settings presets table
CREATE TABLE setting_presets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_preset_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create preset values table
CREATE TABLE setting_preset_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    preset_id INT NOT NULL,
    setting_id INT NOT NULL,
    value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (preset_id) REFERENCES setting_presets(id) ON DELETE CASCADE,
    FOREIGN KEY (setting_id) REFERENCES settings(id) ON DELETE CASCADE,
    UNIQUE KEY unique_preset_setting (preset_id, setting_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create settings import/export logs
CREATE TABLE setting_import_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    status ENUM('success', 'partial', 'failed') NOT NULL,
    settings_count INT NOT NULL DEFAULT 0,
    success_count INT NOT NULL DEFAULT 0,
    error_details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create bulk update logs
CREATE TABLE setting_bulk_update_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    settings_count INT NOT NULL DEFAULT 0,
    success_count INT NOT NULL DEFAULT 0,
    error_details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default presets
INSERT INTO setting_presets (name, description, is_default) VALUES
('Default', 'Default system settings', TRUE),
('Minimal', 'Minimal required settings', FALSE),
('Enterprise', 'Enterprise-level settings', FALSE);
