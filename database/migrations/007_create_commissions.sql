-- Create commission_rates table
CREATE TABLE IF NOT EXISTS commission_rates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('global', 'category', 'product') NOT NULL,
    reference_id INT NULL, -- product_id or category_id, NULL for global
    rate_percent DECIMAL(5,2) NOT NULL,
    min_amount DECIMAL(15,2) DEFAULT 0,
    max_amount DECIMAL(15,2) DEFAULT NULL,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create sales_commissions table
CREATE TABLE IF NOT EXISTS sales_commissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    order_item_id INT NOT NULL,
    user_id INT NOT NULL,
    commission_rate_id INT NOT NULL,
    original_price DECIMAL(15,2) NOT NULL,
    commission_amount DECIMAL(15,2) NOT NULL,
    status ENUM('pending', 'approved', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (order_item_id) REFERENCES order_items(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (commission_rate_id) REFERENCES commission_rates(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create commission_payments table
CREATE TABLE IF NOT EXISTS commission_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'check') NOT NULL,
    reference_number VARCHAR(100),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create commission_payment_items table
CREATE TABLE IF NOT EXISTS commission_payment_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_id INT NOT NULL,
    commission_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES commission_payments(id),
    FOREIGN KEY (commission_id) REFERENCES sales_commissions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create commission_adjustments table
CREATE TABLE IF NOT EXISTS commission_adjustments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commission_id INT NOT NULL,
    adjustment_type ENUM('increase', 'decrease') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    reason TEXT NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (commission_id) REFERENCES sales_commissions(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better performance
CREATE INDEX idx_commission_rates_type ON commission_rates(type, reference_id);
CREATE INDEX idx_commission_rates_dates ON commission_rates(effective_from, effective_to);
CREATE INDEX idx_sales_commissions_user ON sales_commissions(user_id, status);
CREATE INDEX idx_sales_commissions_order ON sales_commissions(order_id);
CREATE INDEX idx_commission_payments_user ON commission_payments(user_id, payment_date);
CREATE INDEX idx_commission_payment_items_commission ON commission_payment_items(commission_id);
CREATE INDEX idx_commission_adjustments_commission ON commission_adjustments(commission_id);
