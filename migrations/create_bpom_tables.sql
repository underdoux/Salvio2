-- Create BPOM products table
CREATE TABLE bpom_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bpom_id VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    manufacturer VARCHAR(255),
    category_id INT,
    registration_status VARCHAR(50),
    registration_number VARCHAR(100),
    registration_date DATE,
    expiry_date DATE,
    composition TEXT,
    dosage_form VARCHAR(100),
    packaging TEXT,
    scraped_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    INDEX idx_bpom_id (bpom_id),
    INDEX idx_name (name),
    INDEX idx_registration (registration_status, registration_date, expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create BPOM scraping log table
CREATE TABLE bpom_scraping_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NULL,
    total_pages INT DEFAULT 0,
    total_products INT DEFAULT 0,
    new_products INT DEFAULT 0,
    updated_products INT DEFAULT 0,
    failed_products INT DEFAULT 0,
    status ENUM('running', 'completed', 'failed') DEFAULT 'running',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create BPOM product categories table
CREATE TABLE bpom_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50),
    description TEXT,
    parent_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES bpom_categories(id),
    UNIQUE KEY unique_name (name),
    UNIQUE KEY unique_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create BPOM category mapping table
CREATE TABLE bpom_category_mappings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bpom_category_id INT NOT NULL,
    local_category_id INT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bpom_category_id) REFERENCES bpom_categories(id),
    FOREIGN KEY (local_category_id) REFERENCES categories(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    UNIQUE KEY unique_mapping (bpom_category_id, local_category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create BPOM product matching table
CREATE TABLE bpom_product_matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    bpom_product_id INT NOT NULL,
    match_confidence DECIMAL(5,2) COMMENT 'Confidence score of the match (0-100)',
    matched_by ENUM('auto', 'manual') DEFAULT 'auto',
    verified_by INT,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (bpom_product_id) REFERENCES bpom_products(id),
    FOREIGN KEY (verified_by) REFERENCES users(id),
    UNIQUE KEY unique_product_match (product_id, bpom_product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create BPOM scraping settings table
CREATE TABLE bpom_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    value TEXT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_setting_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default BPOM categories
INSERT INTO bpom_categories (name, code, description) VALUES
('Obat Bebas', 'OB', 'Obat yang dapat dibeli tanpa resep dokter'),
('Obat Bebas Terbatas', 'OBT', 'Obat yang dapat dibeli tanpa resep dokter dalam jumlah terbatas'),
('Obat Keras', 'OK', 'Obat yang hanya dapat dibeli dengan resep dokter'),
('Narkotika', 'N', 'Obat yang termasuk dalam golongan narkotika'),
('Psikotropika', 'P', 'Obat yang termasuk dalam golongan psikotropika');

-- Insert default BPOM settings
INSERT INTO bpom_settings (name, value, description) VALUES
('scraping_interval', '86400', 'Interval between scraping runs in seconds (default: 24 hours)'),
('request_delay', '2', 'Delay between requests in seconds'),
('max_retries', '3', 'Maximum number of retries for failed requests'),
('auto_categorize', 'true', 'Automatically categorize products based on BPOM data'),
('match_threshold', '80', 'Minimum confidence score for automatic product matching'),
('max_pages', '100', 'Maximum number of pages to scrape per run');

-- Add BPOM-related permissions
INSERT INTO permissions (name, description) VALUES
('manage_bpom_settings', 'Can manage BPOM integration settings'),
('run_bpom_scraper', 'Can manually trigger BPOM scraping'),
('verify_bpom_matches', 'Can verify product matches with BPOM data'),
('manage_bpom_categories', 'Can manage BPOM category mappings');

-- Assign permissions to admin role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'admin'),
    id 
FROM permissions 
WHERE name IN (
    'manage_bpom_settings',
    'run_bpom_scraper',
    'verify_bpom_matches',
    'manage_bpom_categories'
);

-- Add indexes for better performance
ALTER TABLE bpom_products
ADD FULLTEXT INDEX ft_name_composition (name, composition);

ALTER TABLE bpom_scraping_logs
ADD INDEX idx_status_time (status, start_time);

ALTER TABLE bpom_product_matches
ADD INDEX idx_confidence_verified (match_confidence, verified_at);
