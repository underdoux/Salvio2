CREATE TABLE IF NOT EXISTS monthly_profits (
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
