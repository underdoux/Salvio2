#!/bin/bash

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "Please run as root"
    exit 1
fi

# Configuration
APP_PATH="/var/www/salvio2"
DB_NAME="pos_pharma"
DB_USER="salvio2_user"
DB_PASS=$(openssl rand -base64 12)
ADMIN_EMAIL="admin@example.com"
ADMIN_PASS=$(openssl rand -base64 12)

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${GREEN}Starting Salvio2 POS Installation...${NC}"

# Install required packages
echo "Installing required packages..."
apt-get update
apt-get install -y \
    apache2 \
    php \
    php-mysql \
    php-mbstring \
    php-xml \
    php-curl \
    php-zip \
    php-gd \
    mariadb-server \
    composer \
    git \
    unzip

# Configure Apache
echo "Configuring Apache..."
cat > /etc/apache2/sites-available/salvio2.conf << EOF
<VirtualHost *:80>
    ServerName salvio2.local
    DocumentRoot $APP_PATH/public
    
    <Directory $APP_PATH/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/salvio2-error.log
    CustomLog \${APACHE_LOG_DIR}/salvio2-access.log combined
</VirtualHost>
EOF

a2ensite salvio2.conf
a2enmod rewrite
systemctl restart apache2

# Configure MySQL
echo "Configuring MySQL..."
mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Create app directory
echo "Setting up application directory..."
mkdir -p $APP_PATH
chown -R www-data:www-data $APP_PATH
chmod -R 755 $APP_PATH

# Copy application files
echo "Copying application files..."
cp -r . $APP_PATH/
cd $APP_PATH

# Create storage directories
mkdir -p storage/logs
mkdir -p storage/backups
mkdir -p storage/cache
mkdir -p storage/uploads
chown -R www-data:www-data storage
chmod -R 775 storage

# Create configuration file
echo "Creating configuration file..."
cat > config/database.php << EOF
<?php
class Database {
    private \$host = "localhost";
    private \$db_name = "$DB_NAME";
    private \$username = "$DB_USER";
    private \$password = "$DB_PASS";
    public \$conn;

    public function getConnection() {
        \$this->conn = null;
        try {
            \$this->conn = new PDO(
                "mysql:host=" . \$this->host . ";dbname=" . \$this->db_name,
                \$this->username,
                \$this->password,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4")
            );
            \$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException \$e) {
            echo "Connection error: " . \$e->getMessage();
        }
        return \$this->conn;
    }
}
?>
EOF

# Install database schema
echo "Installing database schema..."
mysql -u $DB_USER -p$DB_PASS $DB_NAME < migrations/create_tables.sql
mysql -u $DB_USER -p$DB_PASS $DB_NAME < migrations/create_backup_tables.sql
mysql -u $DB_USER -p$DB_PASS $DB_NAME < migrations/create_api_tables.sql
mysql -u $DB_USER -p$DB_PASS $DB_NAME < migrations/create_settings_tables.sql
mysql -u $DB_USER -p$DB_PASS $DB_NAME < migrations/create_insight_tables.sql
mysql -u $DB_USER -p$DB_PASS $DB_NAME < migrations/create_notification_logs_table.sql

# Create admin user
ADMIN_PASS_HASH=$(php -r "echo password_hash('$ADMIN_PASS', PASSWORD_DEFAULT);")
mysql -u $DB_USER -p$DB_PASS $DB_NAME -e "
    INSERT INTO users (username, email, password, role_id) 
    VALUES ('admin', '$ADMIN_EMAIL', '$ADMIN_PASS_HASH', 
    (SELECT id FROM roles WHERE name = 'admin'));"

# Install systemd services
echo "Installing system services..."
cp scripts/backup.service /etc/systemd/system/
cp scripts/backup.timer /etc/systemd/system/
cp scripts/bpom-scraper.service /etc/systemd/system/
cp scripts/bpom-scraper.timer /etc/systemd/system/
cp scripts/notification-processor.service /etc/systemd/system/

# Update service files with correct paths
sed -i "s|/path/to/Salvio2/Salvio2|$APP_PATH|g" /etc/systemd/system/*.service

# Create log directory
mkdir -p /var/log/salvio2
chown www-data:www-data /var/log/salvio2
chmod 755 /var/log/salvio2

# Enable and start services
systemctl daemon-reload
systemctl enable backup.timer
systemctl start backup.timer
systemctl enable bpom-scraper.timer
systemctl start bpom-scraper.timer
systemctl enable notification-processor
systemctl start notification-processor

# Set up log rotation
cat > /etc/logrotate.d/salvio2 << EOF
/var/log/salvio2/*.log {
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
    create 0644 www-data www-data
}
EOF

# Installation complete
echo -e "${GREEN}Installation completed successfully!${NC}"
echo
echo "Installation Details:"
echo "--------------------"
echo "Application URL: http://salvio2.local"
echo "Admin Username: admin"
echo "Admin Password: $ADMIN_PASS"
echo "Database Name: $DB_NAME"
echo "Database User: $DB_USER"
echo "Database Password: $DB_PASS"
echo
echo "Please save these credentials in a secure location."
echo "You should change the admin password after first login."
echo
echo "Next steps:"
echo "1. Configure your DNS or hosts file to point salvio2.local to your server"
echo "2. Configure SSL/TLS for secure access"
echo "3. Review and update the application settings through the admin interface"
echo "4. Set up email and WhatsApp notification credentials"
echo "5. Configure backup storage locations"
echo
echo -e "${GREEN}Thank you for installing Salvio2 POS!${NC}"
