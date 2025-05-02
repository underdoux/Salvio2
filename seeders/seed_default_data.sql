-- Insert default roles
INSERT INTO roles (name) VALUES 
('admin'),
('cashier'),
('sales');

-- Insert default permissions
INSERT INTO permissions (name) VALUES 
('manage_users'),
('manage_roles'),
('manage_settings'),
('view_products'),
('add_products'),
('edit_products'),
('delete_products'),
('view_orders'),
('create_orders'),
('edit_orders'),
('delete_orders'),
('process_payments'),
('view_commissions'),
('manage_commissions'),
('view_reports'),
('generate_reports'),
('view_insights'),
('manage_backup'),
('manage_api'),
('view_audit_logs');

-- Assign permissions to admin role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'admin'),
    id
FROM permissions;

-- Assign permissions to cashier role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'cashier'),
    id
FROM permissions 
WHERE name IN (
    'view_products',
    'view_orders',
    'create_orders',
    'edit_orders',
    'process_payments'
);

-- Assign permissions to sales role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'sales'),
    id
FROM permissions 
WHERE name IN (
    'view_products',
    'view_orders',
    'create_orders',
    'view_commissions'
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, role_id) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 (SELECT id FROM roles WHERE name = 'admin'));

-- Insert default categories
INSERT INTO categories (name) VALUES 
('Analgesics'),
('Antibiotics'),
('Antidiabetics'),
('Antihypertensives'),
('Antihistamines'),
('Vitamins & Supplements'),
('Medical Supplies'),
('Personal Care');

-- Insert audit log types
INSERT INTO audit_log (user_id, action, details) VALUES 
((SELECT id FROM users WHERE username = 'admin'),
 'system_initialized',
 '{"message": "System initialized with default data"}');
