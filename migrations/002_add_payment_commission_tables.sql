-- Payment Management
ALTER TABLE orders ADD COLUMN payment_type ENUM('cash', 'installment') DEFAULT 'cash';
ALTER TABLE orders ADD COLUMN payment_status ENUM('pending', 'partial', 'completed') DEFAULT 'pending';

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- Commission Management
CREATE TABLE commission_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('global', 'category', 'product') NOT NULL,
    reference_id INT,  -- category_id or product_id for specific rules
    rate DECIMAL(5,2) NOT NULL,
    min_amount DECIMAL(10,2),
    max_amount DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE commissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    rule_id INT NOT NULL,
    status ENUM('pending', 'approved', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (rule_id) REFERENCES commission_rules(id)
);

-- Profit Sharing
CREATE TABLE investors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE profit_distributions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    total_revenue DECIMAL(12,2) NOT NULL,
    total_cost DECIMAL(12,2) NOT NULL,
    net_profit DECIMAL(12,2) NOT NULL,
    status ENUM('draft', 'approved', 'distributed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE profit_shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    distribution_id INT NOT NULL,
    investor_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    paid_date DATE,
    status ENUM('pending', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (distribution_id) REFERENCES profit_distributions(id),
    FOREIGN KEY (investor_id) REFERENCES investors(id)
);

-- Stock Management
CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    reference_type VARCHAR(50),  -- 'order', 'purchase', 'adjustment'
    reference_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Settings
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    key_name VARCHAR(50) NOT NULL,
    value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY category_key (category, key_name)
);

-- Audit Logs
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT,
    old_values TEXT,
    new_values TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Add indexes for better performance
CREATE INDEX idx_orders_payment ON orders(payment_type, payment_status);
CREATE INDEX idx_commission_rules_type ON commission_rules(type, reference_id);
CREATE INDEX idx_stock_movements_product ON stock_movements(product_id, type);
CREATE INDEX idx_audit_logs_action ON audit_logs(action, table_name);
