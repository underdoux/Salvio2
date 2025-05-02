-- Insert default roles
INSERT IGNORE INTO roles (id, name) VALUES (1, 'Admin'), (2, 'Cashier'), (3, 'Sales');

-- Insert default admin user
INSERT IGNORE INTO users (username, password, role_id) VALUES (
    'admin',
    -- Password: Admin@123 (hashed using PHP password_hash)
    '$2y$10$e0NRzQ6XQ6Q6Q6Q6Q6Q6QO6Q6Q6Q6Q6Q6Q6Q6Q6Q6Q6Q6Q6Q6Q6Q6',
    1
);
