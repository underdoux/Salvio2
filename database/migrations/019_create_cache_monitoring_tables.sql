-- Create cache access logs table
CREATE TABLE cache_access_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_hash VARCHAR(32) NOT NULL,
    hit BOOLEAN NOT NULL DEFAULT 0,
    response_time FLOAT NOT NULL,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_key_hash (key_hash),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create cache items table
CREATE TABLE cache_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_hash VARCHAR(32) NOT NULL,
    size INT NOT NULL,
    compressed BOOLEAN NOT NULL DEFAULT 0,
    compression_ratio FLOAT,
    expired BOOLEAN NOT NULL DEFAULT 0,
    needs_replication BOOLEAN NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_key_hash (key_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create cache statistics table
CREATE TABLE cache_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hit_ratio FLOAT NOT NULL,
    memory_used FLOAT NOT NULL,
    avg_compression FLOAT,
    total_items INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create cache nodes table
CREATE TABLE cache_nodes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hostname VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    port INT NOT NULL DEFAULT 11211,
    active BOOLEAN NOT NULL DEFAULT 1,
    last_sync TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_hostname (hostname),
    UNIQUE KEY unique_ip_port (ip_address, port)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create cache replication logs table
CREATE TABLE cache_replication_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    node_id INT NOT NULL,
    success BOOLEAN NOT NULL DEFAULT 0,
    items_count INT NOT NULL DEFAULT 0,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (node_id) REFERENCES cache_nodes(id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create cache health logs table
CREATE TABLE cache_health_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    memory_status ENUM('healthy', 'warning', 'critical') NOT NULL,
    performance_status ENUM('healthy', 'warning', 'critical') NOT NULL,
    replication_status ENUM('healthy', 'warning', 'critical') NOT NULL,
    error_status ENUM('healthy', 'warning', 'critical') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default cache node (localhost)
INSERT INTO cache_nodes (hostname, ip_address, port) 
VALUES ('localhost', '127.0.0.1', 11211);

-- Create cleanup procedure for old logs
DELIMITER //

CREATE PROCEDURE cleanup_cache_logs()
BEGIN
    -- Delete access logs older than 7 days
    DELETE FROM cache_access_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
    
    -- Delete statistics older than 30 days
    DELETE FROM cache_statistics 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Delete replication logs older than 7 days
    DELETE FROM cache_replication_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
    
    -- Delete health logs older than 30 days
    DELETE FROM cache_health_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
END //

DELIMITER ;

-- Create event to run cleanup procedure daily
CREATE EVENT IF NOT EXISTS cache_logs_cleanup
ON SCHEDULE EVERY 1 DAY
DO CALL cleanup_cache_logs();
