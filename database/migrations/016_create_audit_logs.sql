CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    setting_key VARCHAR(100) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    user_id INT UNSIGNED NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NULL,
    timestamp DATETIME NOT NULL,
    session_id VARCHAR(255) NULL,
    request_method VARCHAR(10) NULL,
    request_path VARCHAR(255) NULL,
    setting_type VARCHAR(50) NULL,
    requires_2fa BOOLEAN NULL DEFAULT FALSE,
    validation_rules TEXT NULL,
    rate_limit_remaining INT NULL,
    change_reason TEXT NULL,
    related_changes TEXT NULL,
    details TEXT NULL,
    severity VARCHAR(20) NULL DEFAULT 'info',
    result VARCHAR(20) NULL DEFAULT 'success',
    related_user INT UNSIGNED NULL,
    affected_resource VARCHAR(255) NULL,
    authentication_method VARCHAR(50) NULL,
    attempted_value TEXT NULL,
    validation_result TEXT NULL,
    error_details TEXT NULL,
    validator_version VARCHAR(20) NULL,
    attempts INT UNSIGNED NULL,
    window_start DATETIME NULL,
    window_size INT NULL,
    limit_type VARCHAR(50) NULL,
    remaining_attempts INT NULL,
    action VARCHAR(50) NULL,
    verification_method VARCHAR(50) NULL,
    attempt_number INT UNSIGNED NULL,
    success BOOLEAN NULL,
    error_type VARCHAR(100) NULL,
    expiry_time DATETIME NULL,
    
    INDEX idx_event_type (event_type),
    INDEX idx_setting_key (setting_key),
    INDEX idx_user_id (user_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_ip_address (ip_address),
    INDEX idx_setting_type (setting_type),
    INDEX idx_severity (severity),
    INDEX idx_result (result)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key if users table exists
ALTER TABLE audit_logs
ADD CONSTRAINT fk_audit_logs_user
FOREIGN KEY (user_id) REFERENCES users(id)
ON DELETE SET NULL;

-- Add foreign key for related user if needed
ALTER TABLE audit_logs
ADD CONSTRAINT fk_audit_logs_related_user
FOREIGN KEY (related_user) REFERENCES users(id)
ON DELETE SET NULL;
