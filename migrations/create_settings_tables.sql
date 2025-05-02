-- Create settings table
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL COMMENT 'general, payment, notification, etc.',
    name VARCHAR(100) NOT NULL,
    value TEXT,
    type ENUM('string', 'integer', 'float', 'boolean', 'json', 'array') NOT NULL DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE COMMENT 'Whether setting is visible to non-admin users',
    requires_restart BOOLEAN DEFAULT FALSE COMMENT 'Whether changes require system restart',
    validation_rules TEXT COMMENT 'JSON rules for validation',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_setting (category, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create settings audit log
CREATE TABLE settings_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_id INT NOT NULL,
    user_id INT NOT NULL,
    old_value TEXT,
    new_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (setting_id) REFERENCES settings(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO settings (category, name, value, type, description, is_public, validation_rules) VALUES
-- General Settings
('general', 'site_name', 'POS Pharma', 'string', 'Name of the application', true, '{"min_length": 1, "max_length": 100}'),
('general', 'company_name', 'Your Company Name', 'string', 'Legal company name', true, '{"min_length": 1, "max_length": 200}'),
('general', 'company_address', 'Company Address', 'string', 'Company physical address', true, '{"min_length": 1}'),
('general', 'company_phone', '+62xxx', 'string', 'Company contact number', true, '{"pattern": "^\\+?[0-9]{10,15}$"}'),
('general', 'company_email', 'contact@example.com', 'string', 'Company email address', true, '{"type": "email"}'),
('general', 'timezone', 'Asia/Jakarta', 'string', 'Application timezone', true, '{"pattern": "^[A-Za-z]+/[A-Za-z_]+$"}'),
('general', 'date_format', 'Y-m-d', 'string', 'Default date format', true, null),
('general', 'time_format', 'H:i:s', 'string', 'Default time format', true, null),
('general', 'currency', 'IDR', 'string', 'Default currency', true, '{"pattern": "^[A-Z]{3}$"}'),
('general', 'decimal_places', '2', 'integer', 'Number of decimal places for amounts', true, '{"min": 0, "max": 4}'),
('general', 'thousand_separator', '.', 'string', 'Thousand separator for numbers', true, '{"length": 1}'),
('general', 'decimal_separator', ',', 'string', 'Decimal separator for numbers', true, '{"length": 1}'),

-- Payment Settings
('payment', 'payment_methods', '["cash","transfer","credit_card"]', 'json', 'Available payment methods', false, null),
('payment', 'min_installment_amount', '1000000', 'integer', 'Minimum amount for installment payments', false, '{"min": 0}'),
('payment', 'max_installment_periods', '12', 'integer', 'Maximum number of installment periods', false, '{"min": 1, "max": 24}'),
('payment', 'late_payment_fee', '2.5', 'float', 'Late payment fee percentage', false, '{"min": 0, "max": 100}'),
('payment', 'payment_due_reminder_days', '3', 'integer', 'Days before payment due to send reminder', false, '{"min": 1, "max": 30}'),

-- Commission Settings
('commission', 'default_commission_rate', '5', 'float', 'Default commission percentage', false, '{"min": 0, "max": 100}'),
('commission', 'min_commission_amount', '1000', 'integer', 'Minimum commission amount', false, '{"min": 0}'),
('commission', 'max_commission_amount', '10000000', 'integer', 'Maximum commission amount', false, '{"min": 0}'),
('commission', 'commission_calculation_basis', 'original_price', 'string', 'Basis for commission calculation', false, null),

-- Stock Settings
('stock', 'low_stock_threshold', '10', 'integer', 'Low stock warning threshold', false, '{"min": 0}'),
('stock', 'enable_negative_stock', 'false', 'boolean', 'Allow negative stock quantities', false, null),
('stock', 'stock_notification_emails', '[]', 'json', 'Email addresses for stock notifications', false, null),
('stock', 'auto_order_threshold', '5', 'integer', 'Auto-order when stock reaches this level', false, '{"min": 0}'),

-- Notification Settings
('notification', 'email_notifications', 'true', 'boolean', 'Enable email notifications', false, null),
('notification', 'whatsapp_notifications', 'true', 'boolean', 'Enable WhatsApp notifications', false, null),
('notification', 'notification_email', 'notifications@example.com', 'string', 'Email address for sending notifications', false, '{"type": "email"}'),
('notification', 'smtp_host', 'smtp.example.com', 'string', 'SMTP server hostname', false, null),
('notification', 'smtp_port', '587', 'integer', 'SMTP server port', false, '{"min": 1, "max": 65535}'),
('notification', 'smtp_encryption', 'tls', 'string', 'SMTP encryption type', false, null),
('notification', 'smtp_username', '', 'string', 'SMTP username', false, null),
('notification', 'smtp_password', '', 'string', 'SMTP password', false, null),
('notification', 'whatsapp_api_key', '', 'string', 'WhatsApp API key', false, null),
('notification', 'whatsapp_api_secret', '', 'string', 'WhatsApp API secret', false, null),

-- Security Settings
('security', 'password_min_length', '8', 'integer', 'Minimum password length', false, '{"min": 8, "max": 32}'),
('security', 'password_requires_special', 'true', 'boolean', 'Require special characters in passwords', false, null),
('security', 'password_requires_numbers', 'true', 'boolean', 'Require numbers in passwords', false, null),
('security', 'password_expires_days', '90', 'integer', 'Days until password expires', false, '{"min": 0}'),
('security', 'session_timeout', '3600', 'integer', 'Session timeout in seconds', false, '{"min": 300}'),
('security', 'max_login_attempts', '5', 'integer', 'Maximum failed login attempts', false, '{"min": 1}'),
('security', 'lockout_duration', '900', 'integer', 'Account lockout duration in seconds', false, '{"min": 60}'),

-- Report Settings
('report', 'report_logo_path', '/assets/images/logo.png', 'string', 'Path to logo for reports', false, null),
('report', 'report_footer_text', '', 'string', 'Custom footer text for reports', false, null),
('report', 'default_report_format', 'xlsx', 'string', 'Default format for exported reports', false, null),
('report', 'include_company_header', 'true', 'boolean', 'Include company details in report header', false, null),

-- API Settings
('api', 'enable_api', 'false', 'boolean', 'Enable API access', false, null),
('api', 'api_key_expiry_days', '365', 'integer', 'Days until API keys expire', false, '{"min": 1}'),
('api', 'rate_limit_per_minute', '60', 'integer', 'API rate limit per minute', false, '{"min": 1}'),
('api', 'allowed_origins', '[]', 'json', 'Allowed CORS origins', false, null);

-- Add settings permissions
INSERT INTO permissions (name, description) VALUES
('view_settings', 'Can view system settings'),
('edit_settings', 'Can edit system settings'),
('manage_api_keys', 'Can manage API keys and access');

-- Assign permissions to admin role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'admin'),
    id 
FROM permissions 
WHERE name IN ('view_settings', 'edit_settings', 'manage_api_keys');

-- Create indexes
ALTER TABLE settings
ADD INDEX idx_category (category),
ADD INDEX idx_public (is_public);

ALTER TABLE settings_audit_log
ADD INDEX idx_setting_date (setting_id, created_at);
