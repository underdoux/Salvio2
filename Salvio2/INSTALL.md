# Salvio2 POS Installation Guide

This guide will help you install and configure Salvio2 POS on your server.

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.3 or higher
- Apache 2.4 or higher
- Required PHP extensions:
  - PDO PHP Extension
  - MySQL PDO Driver
  - mbstring PHP Extension
  - xml PHP Extension
  - curl PHP Extension
  - zip PHP Extension
  - gd PHP Extension

## Installation Steps

1. First, ensure your system is up to date:

   ```bash
   sudo apt update
   sudo apt upgrade
   ```

2. Clone the repository to your local machine:

   ```bash
   git clone https://github.com/yourusername/salvio2.git
   cd salvio2
   ```

3. Make the installation scripts executable:

   ```bash
   chmod +x scripts/configure.sh
   chmod +x scripts/install.sh
   ```

4. Run the configuration wizard:

   ```bash
   sudo bash scripts/configure.sh
   ```

   The wizard will guide you through configuring:

   - Application path and domain
   - Database credentials
   - Admin user details
   - Email (SMTP) settings
   - WhatsApp API credentials
   - Backup settings
   - Remote storage (optional)

5. Run the installation script:

   ```bash
   sudo bash scripts/install.sh
   ```

   This will:

   - Install required packages
   - Configure Apache
   - Set up the database
   - Install application files
   - Configure system services
   - Set up log rotation

6. Configure your DNS or local hosts file:
   ```bash
   sudo echo "127.0.0.1 salvio2.local" >> /etc/hosts
   ```

## Post-Installation Steps

1. Access the application:

   - Open your browser and navigate to `http://salvio2.local`
   - Log in with the admin credentials you configured

2. Complete initial setup:

   - Change the admin password
   - Configure company details
   - Set up user roles and permissions
   - Configure product categories
   - Set up commission rules
   - Configure profit sharing settings

3. Configure SSL/TLS:

   ```bash
   sudo certbot --apache -d yourdomain.com
   ```

4. Test the system:
   - Create a test product
   - Process a test order
   - Verify email notifications
   - Check backup functionality
   - Test API endpoints

## Directory Structure

```
/var/www/salvio2/
├── config/           # Configuration files
├── controllers/      # Application controllers
├── models/          # Data models
├── services/        # Business logic services
├── views/           # View templates
├── public/          # Publicly accessible files
├── scripts/         # System scripts
├── storage/         # Application storage
│   ├── backups/     # Backup files
│   ├── cache/       # Cache files
│   ├── logs/        # Application logs
│   └── uploads/     # User uploads
└── vendor/          # Dependencies
```

## Service Management

Start/stop system services:

```bash
# Backup service
sudo systemctl start backup.timer
sudo systemctl stop backup.timer
sudo systemctl status backup.timer

# BPOM scraper
sudo systemctl start bpom-scraper.timer
sudo systemctl stop bpom-scraper.timer
sudo systemctl status bpom-scraper.timer

# Notification processor
sudo systemctl start notification-processor
sudo systemctl stop notification-processor
sudo systemctl status notification-processor
```

View logs:

```bash
# Application logs
tail -f /var/log/salvio2/app.log

# Backup logs
tail -f /var/log/salvio2/backup.log

# BPOM scraper logs
tail -f /var/log/salvio2/bpom-scraper.log

# Notification logs
tail -f /var/log/salvio2/notifications.log
```

## Troubleshooting

1. If Apache shows 403 Forbidden:

   ```bash
   sudo chown -R www-data:www-data /var/www/salvio2
   sudo chmod -R 755 /var/www/salvio2
   ```

2. If services fail to start:

   ```bash
   sudo journalctl -u backup.service
   sudo journalctl -u bpom-scraper.service
   sudo journalctl -u notification-processor.service
   ```

3. If database connection fails:

   ```bash
   sudo mysql -u root
   GRANT ALL PRIVILEGES ON salvio2.* TO 'salvio2_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

4. Clear application cache:
   ```bash
   sudo rm -rf /var/www/salvio2/storage/cache/*
   sudo chown -R www-data:www-data /var/www/salvio2/storage
   ```

## Security Recommendations

1. Enable firewall:

   ```bash
   sudo ufw enable
   sudo ufw allow 80/tcp
   sudo ufw allow 443/tcp
   ```

2. Set up fail2ban:

   ```bash
   sudo apt install fail2ban
   sudo systemctl enable fail2ban
   sudo systemctl start fail2ban
   ```

3. Regular updates:

   ```bash
   sudo apt update
   sudo apt upgrade
   ```

4. Backup configuration:
   - Set up remote backup storage
   - Test backup restoration
   - Monitor backup logs

## Support

For support and bug reports:

- Email: support@salvio2.com
- GitHub Issues: https://github.com/yourusername/salvio2/issues
- Documentation: https://docs.salvio2.com

## License

This software is licensed under the MIT License. See LICENSE file for details.
