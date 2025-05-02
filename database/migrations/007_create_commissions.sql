-- Create commission_rates table
CREATE TABLE commission_rates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('global', 'category', 'product') NOT NULL,
    reference_id INT NULL,
    rate DECIMAL(5,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_commission_rate CHECK (rate >= 0 AND rate <= 100),
    CONSTRAINT fk_commission_rate_category FOREIGN KEY (reference_id) 
        REFERENCES categories(id) ON DELETE CASCADE,
    CONSTRAINT fk_commission_rate_product FOREIGN KEY (reference_id) 
        REFERENCES products(id) ON DELETE CASCADE
);

-- Create sales_commissions table
CREATE TABLE sales_commissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'paid') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_commission_user FOREIGN KEY (user_id) 
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_commission_order FOREIGN KEY (order_id) 
        REFERENCES orders(id) ON DELETE CASCADE
);

-- Insert default global commission rate
INSERT INTO commission_rates (type, rate) VALUES ('global', 5.00);
