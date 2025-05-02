# Salvio2 POS Windows Installation Guide

This guide will help you install Salvio2 POS on Windows using XAMPP.

## Prerequisites

1. Download and install XAMPP:

   - Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
   - Choose version with PHP 7.4 or higher
   - Run the installer and follow the installation wizard
   - Recommended installation path: `C:\xampp`

2. Required XAMPP components:
   - Apache
   - MySQL
   - PHP
   - phpMyAdmin

## Installation Steps

1. Start XAMPP Control Panel:

   - Run XAMPP Control Panel as Administrator
   - Start Apache and MySQL services
   - Verify both services are running (green status)

2. Prepare the installation:

   - Open Command Prompt as Administrator
   - Navigate to XAMPP's htdocs directory:

   ```batch
   cd C:\xampp\htdocs
   ```

3. Clone or copy Salvio2 files:

   - If using Git:

   ```batch
   git clone https://github.com/yourusername/salvio2.git Salvio2
   cd Salvio2
   ```

   - Or extract downloaded files to `C:\xampp\htdocs\Salvio2`

4. Run the installation script:

   ```batch
   cd scripts
   install_xampp.bat
   ```

   The script will:

   - Create the database and user
   - Set up application directories
   - Install database schema
   - Create admin user
   - Configure scheduled tasks

5. Save the credentials displayed at the end of installation:
   - Admin username and password
   - Database credentials
   - Keep these in a secure location!

## Post-Installation Configuration

1. Access the application:

   - Open your browser
   - Navigate to: `http://localhost/Salvio2`
   - Log in with the admin credentials

2. Configure system settings:

   - Go to Settings > System
   - Update company information
   - Configure email settings
   - Set up WhatsApp API credentials
   - Configure backup settings

3. Set up Windows Task Scheduler tasks:

   - Open Task Scheduler
   - Verify Salvio2 tasks are created:
     - Salvio2_Backup
     - Salvio2_BPOM_Scraper
     - Salvio2_Notifications
   - Adjust task schedules if needed

4. Configure PHP settings:

   - Open `C:\xampp\php\php.ini`
   - Update these settings:

   ```ini
   max_execution_time = 300
   max_input_time = 300
   memory_limit = 256M
   post_max_size = 64M
   upload_max_filesize = 64M
   ```

   - Save and restart Apache

5. Configure MySQL settings:
   - Open `C:\xampp\mysql\bin\my.ini`
   - Update these settings:
   ```ini
   max_allowed_packet = 64M
   innodb_buffer_pool_size = 256M
   ```
   - Save and restart MySQL

## Directory Structure

```
C:\xampp\htdocs\Salvio2\
├── config\           # Configuration files
├── controllers\      # Application controllers
├── models\          # Data models
├── services\        # Business logic services
├── views\           # View templates
├── public\          # Publicly accessible files
├── scripts\         # System scripts
├── storage\         # Application storage
│   ├── backups\     # Backup files
│   ├── cache\       # Cache files
│   ├── logs\        # Application logs
│   └── uploads\     # User uploads
└── vendor\          # Dependencies
```

## Troubleshooting

1. If Apache won't start:

   - Check if ports 80/443 are in use
   - Try different ports in httpd.conf
   - Check Windows Defender firewall

2. If MySQL won't start:

   - Check if port 3306 is in use
   - Verify data directory permissions
   - Check error logs in `C:\xampp\mysql\data\`

3. If file uploads fail:

   - Check PHP upload settings in php.ini
   - Verify storage directory permissions
   - Check Apache error logs

4. If scheduled tasks fail:
   - Verify PHP path in task configuration
   - Check task scheduler logs
   - Run tasks manually to test

## Maintenance

1. Database backup:

   - Use phpMyAdmin for manual backups
   - Verify automated backups in storage/backups
   - Test backup restoration

2. Log files:

   - Check `storage/logs` regularly
   - Set up log rotation
   - Monitor disk space

3. Updates:

   - Keep XAMPP components updated
   - Check for application updates
   - Test after updates

4. Security:
   - Change default passwords
   - Keep Windows updated
   - Configure Windows Defender
   - Use strong passwords

## Support

For support and bug reports:

- Email: support@salvio2.com
- GitHub Issues: https://github.com/yourusername/salvio2/issues
- Documentation: https://docs.salvio2.com

## Uninstallation

To remove Salvio2 POS:

1. Stop services:

   - Stop Apache and MySQL in XAMPP Control Panel
   - Stop scheduled tasks in Task Scheduler

2. Remove database:

   ```sql
   DROP DATABASE pos_pharma;
   DROP USER 'salvio2_user'@'localhost';
   ```

3. Delete files:
   - Remove `C:\xampp\htdocs\Salvio2` directory
   - Remove scheduled tasks
   - Clean up logs in `C:\xampp\logs`

## License

This software is licensed under the MIT License. See LICENSE file for details.
