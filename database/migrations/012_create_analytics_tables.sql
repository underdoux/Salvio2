-- Create sales_trends table
CREATE TABLE IF NOT EXISTS sales_trends (
    id INT PRIMARY KEY AUTO_INCREMENT,
    year INT NOT NULL,
    month INT NOT NULL,
    product_id INT NOT NULL,
    category_id INT NOT NULL,
    total_quantity INT NOT NULL DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    average_price DECIMAL(15,2) NOT NULL DEFAULT 0,
    growth_rate DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    UNIQUE KEY unique_product_month (year, month, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create market_response table
CREATE TABLE IF NOT EXISTS market_response (
    id INT PRIMARY KEY AUTO_INCREMENT,
    year INT NOT NULL,
    month INT NOT NULL,
    product_id INT NOT NULL,
    customer_type VARCHAR(50) NOT NULL,
    response_score DECIMAL(5,2) NOT NULL,
    total_orders INT NOT NULL DEFAULT 0,
    total_quantity INT NOT NULL DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    UNIQUE KEY unique_product_customer (year, month, product_id, customer_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create performance_metrics table
CREATE TABLE IF NOT EXISTS performance_metrics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    year INT NOT NULL,
    month INT NOT NULL,
    metric_type ENUM('sales', 'profit', 'commission', 'expense') NOT NULL,
    metric_value DECIMAL(15,2) NOT NULL,
    comparison_value DECIMAL(15,2),
    growth_rate DECIMAL(5,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_metric_month (year, month, metric_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create predictive_analytics table
CREATE TABLE IF NOT EXISTS predictive_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    prediction_date DATE NOT NULL,
    predicted_sales DECIMAL(15,2) NOT NULL,
    confidence_score DECIMAL(5,2) NOT NULL,
    factors_considered TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    UNIQUE KEY unique_product_date (product_id, prediction_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create analytics_logs table
CREATE TABLE IF NOT EXISTS analytics_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    log_type VARCHAR(50) NOT NULL,
    log_message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for performance
CREATE INDEX idx_sales_trends_date ON sales_trends(year, month);
CREATE INDEX idx_market_response_date ON market_response(year, month);
CREATE INDEX idx_performance_metrics_date ON performance_metrics(year, month);
CREATE INDEX idx_predictive_analytics_date ON predictive_analytics(prediction_date);
