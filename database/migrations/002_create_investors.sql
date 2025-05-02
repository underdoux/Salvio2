-- Investors table
CREATE TABLE investors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    capital_amount DECIMAL(15,2) NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    status BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Capital history table
CREATE TABLE capital_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    investor_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    type ENUM('investment', 'withdrawal') NOT NULL,
    date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (investor_id) REFERENCES investors(id)
);
