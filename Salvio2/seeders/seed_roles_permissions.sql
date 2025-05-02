-- Seed roles
INSERT INTO roles (name) VALUES ('Admin'), ('Cashier'), ('Sales');

-- Seed permissions
INSERT INTO permissions (name, description) VALUES
('manage_users', 'Manage users and roles'),
('manage_products', 'Add/edit products and categories'),
('process_transactions', 'Process transactions and orders'),
('view_reports', 'View sales and commission reports'),
('configure_commissions', 'Configure commission rates and caps'),
('distribute_profits', 'Distribute investor profit shares');

-- Assign permissions to roles
-- Admin gets all permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p WHERE r.name = 'Admin';

-- Cashier permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p WHERE r.name = 'Cashier' AND p.name IN ('process_transactions', 'view_reports');

-- Sales permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p WHERE r.name = 'Sales' AND p.name IN ('process_transactions', 'view_reports');
