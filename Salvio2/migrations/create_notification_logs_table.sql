CREATE TABLE notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL COMMENT 'Type of notification (order_status, commission_payment, etc.)',
    channel VARCHAR(20) NOT NULL COMMENT 'Notification channel (email, whatsapp)',
    recipient VARCHAR(255) NOT NULL COMMENT 'Email address or phone number',
    content TEXT NOT NULL COMMENT 'The actual message content sent',
    status ENUM('success', 'failed') NOT NULL DEFAULT 'success',
    error_message TEXT COMMENT 'Error message if notification failed',
    sent_at TIMESTAMP NOT NULL COMMENT 'When the notification was sent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_channel (channel),
    INDEX idx_recipient (recipient),
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create notification settings table
CREATE TABLE notification_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email_notifications BOOLEAN DEFAULT TRUE,
    whatsapp_notifications BOOLEAN DEFAULT TRUE,
    notification_types JSON COMMENT 'Array of notification types user wants to receive',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_user_settings (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add notification-related fields to users table
ALTER TABLE users
ADD COLUMN email_notifications BOOLEAN DEFAULT TRUE AFTER email,
ADD COLUMN whatsapp_notifications BOOLEAN DEFAULT TRUE AFTER phone;

-- Add notification-related fields to investors table
ALTER TABLE investors
ADD COLUMN email_notifications BOOLEAN DEFAULT TRUE AFTER email,
ADD COLUMN whatsapp_notifications BOOLEAN DEFAULT TRUE AFTER phone;

-- Create notification blacklist table
CREATE TABLE notification_blacklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient VARCHAR(255) NOT NULL COMMENT 'Email address or phone number',
    channel VARCHAR(20) NOT NULL COMMENT 'email or whatsapp',
    reason TEXT COMMENT 'Why the recipient was blacklisted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT COMMENT 'User who added the blacklist entry',
    UNIQUE KEY unique_recipient_channel (recipient, channel),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create notification templates table
CREATE TABLE notification_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL COMMENT 'Template type/identifier',
    channel VARCHAR(20) NOT NULL COMMENT 'email or whatsapp',
    subject VARCHAR(255) COMMENT 'Email subject (for email templates)',
    content TEXT NOT NULL COMMENT 'Template content with placeholders',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_type_channel (type, channel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default notification templates
INSERT INTO notification_templates (type, channel, subject, content) VALUES
('order_new', 'email', 'Order Confirmation - #{order_number}', 'Default email template content'),
('order_new', 'whatsapp', NULL, 'Default WhatsApp template content'),
('order_status', 'email', 'Order Status Update - #{order_number}', 'Default email template content'),
('order_status', 'whatsapp', NULL, 'Default WhatsApp template content'),
('commission_approved', 'email', 'Commission Approved - #{commission_id}', 'Default email template content'),
('commission_approved', 'whatsapp', NULL, 'Default WhatsApp template content'),
('profit_distribution', 'email', 'Profit Distribution - {period}', 'Default email template content'),
('profit_distribution', 'whatsapp', NULL, 'Default WhatsApp template content');

-- Create notification queue table for retries and scheduling
CREATE TABLE notification_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    channel VARCHAR(20) NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    scheduled_for TIMESTAMP NOT NULL COMMENT 'When to send the notification',
    attempts INT DEFAULT 0 COMMENT 'Number of send attempts',
    last_attempt TIMESTAMP NULL COMMENT 'Last attempt timestamp',
    error_message TEXT COMMENT 'Last error message',
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status_scheduled (status, scheduled_for)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create notification metrics table for analytics
CREATE TABLE notification_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    type VARCHAR(50) NOT NULL,
    channel VARCHAR(20) NOT NULL,
    total_sent INT DEFAULT 0,
    successful INT DEFAULT 0,
    failed INT DEFAULT 0,
    unique_recipients INT DEFAULT 0,
    avg_delivery_time FLOAT COMMENT 'Average delivery time in seconds',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date_type_channel (date, type, channel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
