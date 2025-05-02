-- Permissions
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Role Permissions
CREATE TABLE role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- Insert default permissions
INSERT INTO permissions (name, description) VALUES
-- User Management
('manage_users', 'Create, edit, and delete users'),
('view_users', 'View user list and details'),

-- Product Management
('manage_products', 'Create, edit, and delete products'),
('view_products', 'View product list and details'),
('manage_stock', 'Update product stock levels'),
('import_products', 'Import products from external sources'),

-- Order Management
('manage_orders', 'Create, edit, and delete orders'),
('view_orders', 'View order list and details'),
('process_orders', 'Update order status and process payments'),

-- Commission Management
('manage_commissions', 'Configure commission rules and rates'),
('view_commissions', 'View commission reports and details'),
('approve_commissions', 'Approve commission payments'),

-- Profit Management
('manage_profit_sharing', 'Configure profit sharing rules'),
('view_profit_sharing', 'View profit sharing reports'),
('approve_profit_distribution', 'Approve profit distributions'),

-- Report Access
('view_sales_reports', 'Access sales reports'),
('view_inventory_reports', 'Access inventory reports'),
('view_financial_reports', 'Access financial reports'),
('export_reports', 'Export reports to different formats'),

-- Settings
('manage_settings', 'Configure system settings'),
('manage_roles', 'Manage user roles and permissions'),
('manage_backup', 'Perform system backup and restore');

-- Insert default roles
INSERT INTO roles (name) VALUES
('Admin'),
('Cashier'),
('Sales');

-- Assign all permissions to Admin role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

-- Assign limited permissions to Cashier role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions 
WHERE name IN (
    'view_products',
    'view_orders',
    'manage_orders',
    'process_orders',
    'view_sales_reports'
);

-- Assign limited permissions to Sales role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions 
WHERE name IN (
    'view_products',
    'view_orders',
    'manage_orders',
    'view_commissions',
    'view_sales_reports'
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, role_id) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);
