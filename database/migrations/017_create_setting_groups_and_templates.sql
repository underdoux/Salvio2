-- Create setting groups table
CREATE TABLE setting_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_group_name (name),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add group_id to settings table
ALTER TABLE settings 
ADD COLUMN group_id INT NULL,
ADD FOREIGN KEY (group_id) REFERENCES setting_groups(id);

-- Create setting templates table
CREATE TABLE setting_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_template_name (name),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create setting template values table
CREATE TABLE setting_template_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES setting_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (setting_key) REFERENCES settings(`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default setting groups
INSERT INTO setting_groups (name, description, created_by) VALUES
('System', 'Core system settings', 1),
('Email', 'Email and SMTP configuration', 1),
('Currency', 'Currency and payment settings', 1),
('Tax', 'Tax calculation settings', 1),
('Commission', 'Commission and payout settings', 1),
('Notification', 'Notification preferences', 1),
('Security', 'Security and access control', 1);

-- Update existing settings with group assignments
UPDATE settings s
JOIN setting_groups g ON 
    CASE 
        WHEN s.key LIKE 'smtp%' OR s.key LIKE 'email%' THEN g.name = 'Email'
        WHEN s.key LIKE 'currency%' THEN g.name = 'Currency'
        WHEN s.key LIKE 'tax%' THEN g.name = 'Tax'
        WHEN s.key LIKE 'commission%' THEN g.name = 'Commission'
        WHEN s.key LIKE 'notification%' THEN g.name = 'Notification'
        WHEN s.key LIKE 'security%' OR s.key LIKE 'password%' THEN g.name = 'Security'
        ELSE g.name = 'System'
    END
SET s.group_id = g.id;

-- Create default templates
INSERT INTO setting_templates (name, description, created_by) VALUES
('Default', 'Default system configuration', 1),
('Minimal', 'Minimal required settings', 1),
('Enterprise', 'Enterprise-level configuration', 1);

-- Insert template values (example for Default template)
INSERT INTO setting_template_values (template_id, setting_key, value)
SELECT 
    1, -- Default template ID
    s.key,
    s.value
FROM settings s
WHERE s.key IN (
    'currency.code',
    'currency.symbol',
    'tax.rate',
    'commission.global_rate',
    'smtp.host',
    'smtp.port'
);
