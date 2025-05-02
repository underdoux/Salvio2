-- Operational expenses categories
CREATE TABLE expense_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Operational expenses
CREATE TABLE operational_expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    expense_date DATE NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Monthly profit calculations
CREATE TABLE monthly_profits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    period DATE NOT NULL,  -- Store first day of month
    total_sales DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_product_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_expenses DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_commissions DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    net_profit DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status ENUM('draft', 'final') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (period)
);

-- Profit distribution
CREATE TABLE profit_distribution (
    id INT PRIMARY KEY AUTO_INCREMENT,
    monthly_profit_id INT NOT NULL,
    investor_id INT NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    status ENUM('pending', 'distributed') NOT NULL DEFAULT 'pending',
    distributed_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (monthly_profit_id) REFERENCES monthly_profits(id),
    FOREIGN KEY (investor_id) REFERENCES investors(id)
);

-- Activity logs for profit calculations
CREATE TABLE profit_calculation_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    monthly_profit_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (monthly_profit_id) REFERENCES monthly_profits(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
