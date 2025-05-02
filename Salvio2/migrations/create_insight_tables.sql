-- Create insights cache table
CREATE TABLE insight_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cache_key VARCHAR(255) NOT NULL,
    data JSON NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cache_key (cache_key),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create insights metrics table
CREATE TABLE insight_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    metric_type VARCHAR(50) NOT NULL COMMENT 'sales, products, customers, etc.',
    metric_name VARCHAR(50) NOT NULL COMMENT 'revenue, orders, etc.',
    value DECIMAL(15,2) NOT NULL,
    comparison_value DECIMAL(15,2) COMMENT 'Value from previous period',
    growth_rate DECIMAL(5,2) COMMENT 'Percentage growth',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_metric_date (date, metric_type, metric_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create customer segments table
CREATE TABLE customer_segments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    criteria JSON NOT NULL COMMENT 'Segmentation rules',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create customer segment assignments table
CREATE TABLE customer_segment_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    segment_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (segment_id) REFERENCES customer_segments(id),
    UNIQUE KEY unique_customer_segment (customer_id, segment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create insight alerts table
CREATE TABLE insight_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL COMMENT 'low_stock, sales_drop, etc.',
    severity ENUM('info', 'warning', 'critical') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    metric_value DECIMAL(15,2),
    threshold_value DECIMAL(15,2),
    status ENUM('active', 'acknowledged', 'resolved') DEFAULT 'active',
    acknowledged_by INT,
    acknowledged_at TIMESTAMP NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (acknowledged_by) REFERENCES users(id),
    INDEX idx_type_status (type, status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create insight alert settings table
CREATE TABLE insight_alert_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    threshold_value DECIMAL(15,2) NOT NULL,
    comparison_operator ENUM('<', '<=', '>', '>=', '=') NOT NULL,
    severity ENUM('info', 'warning', 'critical') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    notification_channels JSON COMMENT 'Array of notification channels',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_alert_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create insight bookmarks table
CREATE TABLE insight_bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    filters JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_type (user_id, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add insight permissions
INSERT INTO permissions (name, description) VALUES
('view_insights', 'Can view business insights'),
('export_insights', 'Can export insight data'),
('manage_insight_settings', 'Can manage insight settings and alerts'),
('acknowledge_alerts', 'Can acknowledge insight alerts');

-- Assign permissions to admin role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'admin'),
    id 
FROM permissions 
WHERE name IN ('view_insights', 'export_insights', 'manage_insight_settings', 'acknowledge_alerts');

-- Add insight-related settings
INSERT INTO settings (category, name, value, type, description) VALUES
('insights', 'cache_duration', '3600', 'integer', 'Duration in seconds to cache insight data'),
('insights', 'default_date_range', 'this_month', 'string', 'Default date range for insights'),
('insights', 'refresh_interval', '300', 'integer', 'Auto-refresh interval in seconds'),
('insights', 'alert_check_interval', '900', 'integer', 'Interval to check for new alerts in seconds');

-- Create default customer segments
INSERT INTO customer_segments (name, description, criteria) VALUES
('VIP', 'Customers with high purchase value and frequency', 
 '{"min_orders": 10, "min_total_spent": 10000000, "last_order_within_days": 30}'),
('Regular', 'Customers who purchase regularly', 
 '{"min_orders": 5, "min_total_spent": 5000000, "last_order_within_days": 60}'),
('At Risk', 'Customers who haven''t purchased recently', 
 '{"min_orders": 2, "days_since_last_order": 90}'),
('New', 'First-time customers', 
 '{"max_orders": 1, "joined_within_days": 30}');

-- Create default alert settings
INSERT INTO insight_alert_settings 
(type, threshold_value, comparison_operator, severity, notification_channels) VALUES
('low_stock', 10, '<=', 'warning', '["email", "system"]'),
('sales_drop', 20, '>=', 'critical', '["email", "system", "whatsapp"]'),
('high_returns', 5, '>=', 'warning', '["email", "system"]'),
('payment_overdue', 7, '>=', 'critical', '["email", "system", "whatsapp"]');

-- Add indexes for better performance
ALTER TABLE insight_metrics
ADD INDEX idx_metric_date_type (metric_type, date),
ADD INDEX idx_metric_date_name (metric_name, date);

ALTER TABLE customer_segment_assignments
ADD INDEX idx_segment_assigned (segment_id, assigned_at);

ALTER TABLE insight_alerts
ADD INDEX idx_severity_created (severity, created_at);
