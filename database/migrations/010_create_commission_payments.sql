-- Create commission_payments table
CREATE TABLE commission_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commission_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    reference_number VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_commission FOREIGN KEY (commission_id) 
        REFERENCES sales_commissions(id) ON DELETE CASCADE
);
