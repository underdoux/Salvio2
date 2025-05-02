-- Notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    recipient_type ENUM('user', 'customer') NOT NULL,
    recipient_id INT NOT NULL,
    status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    send_via ENUM('email', 'whatsapp', 'both') NOT NULL,
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Notification templates
CREATE TABLE notification_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(255),
    content TEXT NOT NULL,
    variables TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Analytics - Product Performance
CREATE TABLE product_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    period DATE NOT NULL,  -- First day of month
    total_sales DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    units_sold INT NOT NULL DEFAULT 0,
    profit_margin DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    UNIQUE KEY (product_id, period)
);

-- Analytics - Customer Insights
CREATE TABLE customer_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    period DATE NOT NULL,  -- First day of month
    total_orders INT NOT NULL DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    average_order_value DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    UNIQUE KEY (customer_id, period)
);

-- Analytics - Sales Trends
CREATE TABLE sales_trends (
    id INT PRIMARY KEY AUTO_INCREMENT,
    period DATE NOT NULL,  -- First day of month
    customer_type ENUM('pharmacy', 'clinic', 'hospital', 'other') NOT NULL,
    total_sales DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    order_count INT NOT NULL DEFAULT 0,
    average_order_value DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (period, customer_type)
);
