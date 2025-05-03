CREATE TABLE IF NOT EXISTS profit_distributions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    profit_id INT NOT NULL,
    investor_id INT NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'paid') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (profit_id) REFERENCES monthly_profits(id) ON DELETE CASCADE,
    FOREIGN KEY (investor_id) REFERENCES investors(id) ON DELETE CASCADE,
    CHECK (percentage >= 0 AND percentage <= 100)
);
