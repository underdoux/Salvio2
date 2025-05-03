-- Create monthly_profits table
CREATE TABLE IF NOT EXISTS monthly_profits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    year INT NOT NULL,
    month INT NOT NULL,
    total_sales DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_costs DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_expenses DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_commissions DECIMAL(15,2) NOT NULL DEFAULT 0,
    net_profit DECIMAL(15,2) NOT NULL DEFAULT 0,
    status ENUM('draft', 'finalized') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_year_month (year, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create profit_distribution table
CREATE TABLE IF NOT EXISTS profit_distribution (
    id INT PRIMARY KEY AUTO_INCREMENT,
    monthly_profit_id INT NOT NULL,
    investor_id INT NOT NULL,
    distribution_amount DECIMAL(15,2) NOT NULL,
    status ENUM('pending', 'paid') NOT NULL DEFAULT 'pending',
    payment_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (monthly_profit_id) REFERENCES monthly_profits(id),
    FOREIGN KEY (investor_id) REFERENCES investors(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create profit_calculation_logs table
CREATE TABLE IF NOT EXISTS profit_calculation_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    monthly_profit_id INT NOT NULL,
    log_message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (monthly_profit_id) REFERENCES monthly_profits(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for performance
CREATE INDEX idx_monthly_profits_year_month ON monthly_profits(year, month);
CREATE INDEX idx_profit_distribution_status ON profit_distribution(status);
CREATE INDEX idx_profit_calculation_logs_monthly_profit ON profit_calculation_logs(monthly_profit_id);
