#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

# Default values
DEFAULT_APP_PATH="/var/www/salvio2"
DEFAULT_DB_NAME="pos_pharma"
DEFAULT_DB_USER="salvio2_user"
DEFAULT_DOMAIN="salvio2.local"

echo -e "${BLUE}Salvio2 POS Configuration Wizard${NC}"
echo "============================="
echo

# Application Path
read -p "Enter application path [$DEFAULT_APP_PATH]: " APP_PATH
APP_PATH=${APP_PATH:-$DEFAULT_APP_PATH}

# Domain Name
read -p "Enter domain name [$DEFAULT_DOMAIN]: " DOMAIN
DOMAIN=${DOMAIN:-$DEFAULT_DOMAIN}

# Database Configuration
read -p "Enter database name [$DEFAULT_DB_NAME]: " DB_NAME
DB_NAME=${DB_NAME:-$DEFAULT_DB_NAME}

read -p "Enter database user [$DEFAULT_DB_USER]: " DB_USER
DB_USER=${DB_USER:-$DEFAULT_DB_USER}

read -s -p "Enter database password: " DB_PASS
echo
if [ -z "$DB_PASS" ]; then
    DB_PASS=$(openssl rand -base64 12)
    echo "Generated random database password: $DB_PASS"
fi

# Admin Configuration
read -p "Enter admin email: " ADMIN_EMAIL
while [[ ! "$ADMIN_EMAIL" =~ ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$ ]]; do
    echo -e "${RED}Invalid email format${NC}"
    read -p "Enter admin email: " ADMIN_EMAIL
done

read -s -p "Enter admin password: " ADMIN_PASS
echo
if [ -z "$ADMIN_PASS" ]; then
    ADMIN_PASS=$(openssl rand -base64 12)
    echo "Generated random admin password: $ADMIN_PASS"
fi

# Email Configuration
echo
echo "Email Configuration"
echo "-----------------"
read -p "SMTP Host [smtp.gmail.com]: " SMTP_HOST
SMTP_HOST=${SMTP_HOST:-"smtp.gmail.com"}

read -p "SMTP Port [587]: " SMTP_PORT
SMTP_PORT=${SMTP_PORT:-"587"}

read -p "SMTP Username: " SMTP_USER

read -s -p "SMTP Password: " SMTP_PASS
echo

# WhatsApp Configuration
echo
echo "WhatsApp Configuration"
echo "--------------------"
read -p "WhatsApp API Key: " WA_API_KEY

read -s -p "WhatsApp API Secret: " WA_API_SECRET
echo

# Backup Configuration
echo
echo "Backup Configuration"
echo "------------------"
read -p "Backup retention days [30]: " BACKUP_RETENTION
BACKUP_RETENTION=${BACKUP_RETENTION:-30}

read -p "Enable remote backup storage? (y/n) [n]: " ENABLE_REMOTE
ENABLE_REMOTE=${ENABLE_REMOTE:-n}

if [[ $ENABLE_REMOTE =~ ^[Yy]$ ]]; then
    echo "Remote Storage Configuration"
    echo "1) Amazon S3"
    echo "2) Google Drive"
    echo "3) FTP Server"
    read -p "Select storage type [1]: " STORAGE_TYPE
    STORAGE_TYPE=${STORAGE_TYPE:-1}

    case $STORAGE_TYPE in
        1)
            read -p "AWS Access Key ID: " AWS_ACCESS_KEY
            read -s -p "AWS Secret Access Key: " AWS_SECRET_KEY
            echo
            read -p "S3 Bucket Name: " S3_BUCKET
            read -p "S3 Region [us-east-1]: " S3_REGION
            S3_REGION=${S3_REGION:-"us-east-1"}
            ;;
        2)
            echo "Please place your Google Drive credentials file in config/google-credentials.json"
            read -p "Google Drive Folder ID: " GDRIVE_FOLDER
            ;;
        3)
            read -p "FTP Host: " FTP_HOST
            read -p "FTP Username: " FTP_USER
            read -s -p "FTP Password: " FTP_PASS
            echo
            read -p "FTP Path: " FTP_PATH
            ;;
    esac
fi

# Save configuration
echo
echo "Saving configuration..."

# Create config directory if it doesn't exist
mkdir -p config

# Create main config file
cat > config/app.php << EOF
<?php
return [
    'app_path' => '$APP_PATH',
    'domain' => '$DOMAIN',
    'db' => [
        'name' => '$DB_NAME',
        'user' => '$DB_USER',
        'pass' => '$DB_PASS'
    ],
    'admin_email' => '$ADMIN_EMAIL',
    'smtp' => [
        'host' => '$SMTP_HOST',
        'port' => $SMTP_PORT,
        'user' => '$SMTP_USER',
        'pass' => '$SMTP_PASS'
    ],
    'whatsapp' => [
        'api_key' => '$WA_API_KEY',
        'api_secret' => '$WA_API_SECRET'
    ],
    'backup' => [
        'retention_days' => $BACKUP_RETENTION
    ]
];
?>
EOF

# Create storage config if remote storage is enabled
if [[ $ENABLE_REMOTE =~ ^[Yy]$ ]]; then
    case $STORAGE_TYPE in
        1)
            cat > config/storage.php << EOF
<?php
return [
    'type' => 's3',
    'config' => [
        'key' => '$AWS_ACCESS_KEY',
        'secret' => '$AWS_SECRET_KEY',
        'bucket' => '$S3_BUCKET',
        'region' => '$S3_REGION'
    ]
];
?>
EOF
            ;;
        2)
            cat > config/storage.php << EOF
<?php
return [
    'type' => 'google_drive',
    'config' => [
        'folder_id' => '$GDRIVE_FOLDER'
    ]
];
?>
EOF
            ;;
        3)
            cat > config/storage.php << EOF
<?php
return [
    'type' => 'ftp',
    'config' => [
        'host' => '$FTP_HOST',
        'username' => '$FTP_USER',
        'password' => '$FTP_PASS',
        'path' => '$FTP_PATH'
    ]
];
?>
EOF
            ;;
    esac
fi

echo -e "${GREEN}Configuration saved successfully!${NC}"
echo
echo "You can now run the installation script:"
echo "sudo bash scripts/install.sh"
echo
echo "Please save these credentials in a secure location:"
echo "------------------------------------------------"
echo "Database Name: $DB_NAME"
echo "Database User: $DB_USER"
echo "Database Password: $DB_PASS"
echo "Admin Email: $ADMIN_EMAIL"
echo "Admin Password: $ADMIN_PASS"
echo
echo -e "${BLUE}Thank you for choosing Salvio2 POS!${NC}"
