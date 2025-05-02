-- Insert default roles
INSERT INTO roles (name) VALUES 
('admin'),
('cashier'),
('sales');

-- Insert admin user with password 'Admin@123'
INSERT INTO users (username, password, role_id) 
SELECT 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', id 
FROM roles WHERE name = 'admin';
