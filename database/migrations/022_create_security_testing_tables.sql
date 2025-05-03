-- Create security_scans table
CREATE TABLE security_scans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('penetration', 'vulnerability', 'stress', 'full_audit') NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'failed') NOT NULL,
    score INT,
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NULL,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create penetration_test_results table
CREATE TABLE penetration_test_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    scan_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    status ENUM('passed', 'failed') NOT NULL,
    severity ENUM('low', 'medium', 'high') NOT NULL,
    description TEXT,
    impact TEXT,
    recommendation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scan_id) REFERENCES security_scans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create vulnerability_results table
CREATE TABLE vulnerability_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    scan_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    risk_level ENUM('low', 'medium', 'high') NOT NULL,
    status ENUM('open', 'fixed', 'in_progress') NOT NULL,
    description TEXT,
    impact TEXT,
    fix_steps TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fixed_at TIMESTAMP NULL,
    FOREIGN KEY (scan_id) REFERENCES security_scans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create stress_test_results table
CREATE TABLE stress_test_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    scan_id INT NOT NULL,
    timestamp TIMESTAMP NOT NULL,
    concurrent_users INT NOT NULL,
    response_time FLOAT NOT NULL,
    error_rate FLOAT NOT NULL,
    cpu_usage FLOAT NOT NULL,
    memory_usage FLOAT NOT NULL,
    FOREIGN KEY (scan_id) REFERENCES security_scans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create security_metrics table
CREATE TABLE security_metrics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    scan_id INT NOT NULL,
    metric_name VARCHAR(255) NOT NULL,
    metric_value FLOAT NOT NULL,
    status ENUM('good', 'warning', 'critical') NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scan_id) REFERENCES security_scans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create active_threats table
CREATE TABLE active_threats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('intrusion', 'vulnerability', 'malware', 'anomaly') NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    description TEXT NOT NULL,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    resolution_notes TEXT,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create security_configurations table
CREATE TABLE security_configurations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    value TEXT NOT NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    description TEXT,
    last_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    modified_by INT,
    FOREIGN KEY (modified_by) REFERENCES users(id),
    UNIQUE KEY unique_config (category, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create security_test_schedules table
CREATE TABLE security_test_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    test_type ENUM('penetration', 'vulnerability', 'stress', 'full_audit') NOT NULL,
    frequency ENUM('daily', 'weekly', 'monthly', 'quarterly') NOT NULL,
    last_run TIMESTAMP NULL,
    next_run TIMESTAMP NOT NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add indexes for better performance
CREATE INDEX idx_scan_type ON security_scans(type);
CREATE INDEX idx_scan_status ON security_scans(status);
CREATE INDEX idx_vuln_risk ON vulnerability_results(risk_level);
CREATE INDEX idx_vuln_status ON vulnerability_results(status);
CREATE INDEX idx_threat_type ON active_threats(type);
CREATE INDEX idx_threat_severity ON active_threats(severity);
CREATE INDEX idx_security_config ON security_configurations(category, name);
CREATE INDEX idx_test_schedule ON security_test_schedules(test_type, next_run);
