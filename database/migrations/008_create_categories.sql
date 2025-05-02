CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some default categories
INSERT INTO categories (name, description) VALUES 
('General', 'General category for products'),
('Electronics', 'Electronic devices and accessories'),
('Clothing', 'Clothing and apparel items'),
('Food & Beverage', 'Food and beverage products');
