-- Create API keys table
CREATE TABLE api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL COMMENT 'Friendly name for the API key',
    key_hash VARCHAR(64) NOT NULL COMMENT 'SHA-256 hash of the API key',
    scopes JSON NOT NULL COMMENT 'Array of allowed API scopes',
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NULL,
    last_used_at TIMESTAMP NULL,
    total_requests BIGINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_key_hash (key_hash),
    INDEX idx_user_active (user_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create API requests table (for rate limiting)
CREATE TABLE api_requests (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    api_key_id INT NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id),
    INDEX idx_key_time (api_key_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create API access log
CREATE TABLE api_access_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    api_key_id INT NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    response_code INT NOT NULL,
    response_time FLOAT NOT NULL COMMENT 'Response time in seconds',
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id),
    INDEX idx_key_endpoint (api_key_id, endpoint),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create API error log
CREATE TABLE api_error_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    api_key_id INT,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    error_code INT NOT NULL,
    error_message TEXT NOT NULL,
    stack_trace TEXT,
    request_data TEXT,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id),
    INDEX idx_key_error (api_key_id, error_code),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create API webhooks table
CREATE TABLE api_webhooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    url VARCHAR(255) NOT NULL,
    events JSON NOT NULL COMMENT 'Array of events to trigger webhook',
    secret_hash VARCHAR(64) NOT NULL COMMENT 'Hash of webhook secret for signature verification',
    is_active BOOLEAN DEFAULT TRUE,
    last_triggered_at TIMESTAMP NULL,
    failure_count INT DEFAULT 0,
    last_failure_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_active (user_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create webhook delivery log
CREATE TABLE webhook_deliveries (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    webhook_id INT NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    payload TEXT NOT NULL,
    response_code INT,
    response_body TEXT,
    delivery_time FLOAT COMMENT 'Delivery time in seconds',
    status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
    attempt_count INT DEFAULT 0,
    next_retry_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (webhook_id) REFERENCES api_webhooks(id),
    INDEX idx_webhook_status (webhook_id, status),
    INDEX idx_next_retry (next_retry_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create API documentation table
CREATE TABLE api_documentation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    version VARCHAR(10) NOT NULL DEFAULT '1.0',
    summary VARCHAR(255) NOT NULL,
    description TEXT,
    parameters JSON COMMENT 'JSON schema of endpoint parameters',
    request_body JSON COMMENT 'JSON schema of request body',
    responses JSON COMMENT 'JSON schema of possible responses',
    scopes JSON COMMENT 'Required API scopes',
    deprecated BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_endpoint_method_version (endpoint, method, version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add API-related permissions
INSERT INTO permissions (name, description) VALUES
('manage_api_keys', 'Can create and manage API keys'),
('view_api_logs', 'Can view API access and error logs'),
('manage_webhooks', 'Can manage API webhooks'),
('edit_api_docs', 'Can edit API documentation');

-- Assign permissions to admin role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'admin'),
    id 
FROM permissions 
WHERE name IN (
    'manage_api_keys',
    'view_api_logs',
    'manage_webhooks',
    'edit_api_docs'
);

-- Create indexes for better performance
ALTER TABLE api_requests
ADD INDEX idx_created_ip (created_at, ip_address);

ALTER TABLE api_access_log
ADD INDEX idx_response_time (response_time),
ADD INDEX idx_response_code (response_code);

ALTER TABLE api_error_log
ADD INDEX idx_error_code_time (error_code, created_at);

ALTER TABLE webhook_deliveries
ADD INDEX idx_status_time (status, created_at);

-- Add fulltext search for API documentation
ALTER TABLE api_documentation
ADD FULLTEXT INDEX ft_docs (summary, description);
