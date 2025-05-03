-- Create monthly_profits table
CREATE TABLE monthly_profits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    month DATE NOT NULL,
    total_sales DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_costs DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_commissions DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_expenses DECIMAL(10,2) NOT NULL DEFAULT 0,
    net_profit DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('draft', 'final') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_month (month)
);

-- Create profit_distributions table
CREATE TABLE profit_distributions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    profit_id INT NOT NULL,
    investor_id INT NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'paid') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_distribution_profit FOREIGN KEY (profit_id) 
        REFERENCES monthly_profits(id) ON DELETE CASCADE,
    CONSTRAINT fk_distribution_investor FOREIGN KEY (investor_id) 
        REFERENCES investors(id) ON DELETE CASCADE,
    CONSTRAINT chk_percentage CHECK (percentage >= 0 AND percentage <= 100)
);

-- Insert sample data for testing
INSERT INTO monthly_profits (month, total_sales, total_costs, total_commissions, total_expenses, net_profit, status) VALUES
('2024-01-01', 100000.00, 60000.00, 5000.00, 10000.00, 25000.00, 'final'),
('2024-02-01', 120000.00, 70000.00, 6000.00, 12000.00, 32000.00, 'final'),
('2024-03-01', 110000.00, 65000.00, 5500.00, 11000.00, 28500.00, 'draft');

-- Insert sample distributions (assuming investor IDs 1 and 2 exist)
INSERT INTO profit_distributions (profit_id, investor_id, percentage, amount, status) VALUES
(1, 1, 60.00, 15000.00, 'paid'),
(1, 2, 40.00, 10000.00, 'paid'),
(2, 1, 60.00, 19200.00, 'approved'),
(2, 2, 40.00, 12800.00, 'approved'),
(3, 1, 60.00, 17100.00, 'pending'),
(3, 2, 40.00, 11400.00, 'pending');
