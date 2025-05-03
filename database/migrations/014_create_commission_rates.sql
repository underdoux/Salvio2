-- Create commission_rates table
CREATE TABLE commission_rates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rate DECIMAL(5,2) NOT NULL,
    product_id INT NULL,
    category_id INT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_rate_product FOREIGN KEY (product_id) 
        REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_rate_category FOREIGN KEY (category_id) 
        REFERENCES categories(id) ON DELETE CASCADE,
    -- Ensure only one rate type (global, category, or product) is set
    CONSTRAINT chk_rate_type CHECK (
        (product_id IS NULL AND category_id IS NULL) OR -- Global rate
        (product_id IS NULL AND category_id IS NOT NULL) OR -- Category rate
        (product_id IS NOT NULL AND category_id IS NULL) -- Product rate
    )
);

-- Insert default global commission rate
INSERT INTO commission_rates (rate) VALUES (5.00);

-- Create index for faster lookups
CREATE INDEX idx_commission_rates_status ON commission_rates(status);
CREATE INDEX idx_commission_rates_product ON commission_rates(product_id);
CREATE INDEX idx_commission_rates_category ON commission_rates(category_id);
