-- Create backup codes table
CREATE TABLE two_factor_backup_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code VARCHAR(32) NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create trusted devices table
CREATE TABLE two_factor_trusted_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_identifier VARCHAR(255) NOT NULL,
    device_name VARCHAR(255),
    last_used_at TIMESTAMP NULL,
    trusted_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_device (user_id, device_identifier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create SMS verification table
CREATE TABLE two_factor_sms_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    verification_code VARCHAR(6) NOT NULL,
    attempts INT DEFAULT 0,
    expires_at TIMESTAMP NOT NULL,
    verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create recovery methods table
CREATE TABLE two_factor_recovery_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    method_type ENUM('email', 'sms', 'backup_codes', 'trusted_device') NOT NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_method (user_id, method_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create recovery logs table
CREATE TABLE two_factor_recovery_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    method_type ENUM('email', 'sms', 'backup_codes', 'trusted_device') NOT NULL,
    status ENUM('success', 'failed') NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
