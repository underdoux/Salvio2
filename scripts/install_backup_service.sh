#!/bin/bash

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "Please run as root"
    exit 1
fi

# Set variables
APP_PATH="/path/to/Salvio2/Salvio2"
SERVICE_NAME="backup"
LOG_DIR="/var/log/salvio2"
USER="www-data"
GROUP="www-data"

# Create log directory
echo "Creating log directory..."
mkdir -p $LOG_DIR
chown $USER:$GROUP $LOG_DIR
chmod 755 $LOG_DIR

# Create log files
touch $LOG_DIR/$SERVICE_NAME.log
touch $LOG_DIR/$SERVICE_NAME.error.log
chown $USER:$GROUP $LOG_DIR/$SERVICE_NAME.*
chmod 644 $LOG_DIR/$SERVICE_NAME.*

# Create backup storage directory
echo "Creating backup storage directory..."
mkdir -p $APP_PATH/storage/backups
chown $USER:$GROUP $APP_PATH/storage/backups
chmod 755 $APP_PATH/storage/backups

# Copy service files
echo "Installing systemd service files..."
cp $APP_PATH/scripts/$SERVICE_NAME.service /etc/systemd/system/
cp $APP_PATH/scripts/$SERVICE_NAME.timer /etc/systemd/system/

# Update service file with correct paths
sed -i "s|/path/to/Salvio2/Salvio2|$APP_PATH|g" /etc/systemd/system/$SERVICE_NAME.service

# Reload systemd
echo "Reloading systemd..."
systemctl daemon-reload

# Enable and start timer
echo "Enabling and starting backup timer..."
systemctl enable $SERVICE_NAME.timer
systemctl start $SERVICE_NAME.timer

# Create logrotate config
echo "Setting up log rotation..."
cat > /etc/logrotate.d/salvio2-backup << EOF
$LOG_DIR/$SERVICE_NAME.log $LOG_DIR/$SERVICE_NAME.error.log {
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
    create 644 $USER $GROUP
    sharedscripts
    postrotate
        systemctl kill -s HUP $SERVICE_NAME.service
    endscript
}
EOF

# Create backup cleanup cron job
echo "Setting up backup cleanup cron job..."
CRON_FILE="/etc/cron.daily/salvio2-backup-cleanup"
cat > $CRON_FILE << EOF
#!/bin/bash
# Clean up old backups based on retention policy
php $APP_PATH/scripts/run_backup.php --cleanup-only
EOF
chmod +x $CRON_FILE

echo "Installation complete!"
echo "The backup service will check for scheduled backups every 15 minutes"
echo "You can check the status with: systemctl status $SERVICE_NAME.timer"
echo "You can view logs with: journalctl -u $SERVICE_NAME"
echo "Log files are located in: $LOG_DIR"
echo "Backups will be stored in: $APP_PATH/storage/backups"

# Run initial backup
read -p "Would you like to run an initial backup now? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Running initial backup..."
    systemctl start $SERVICE_NAME.service
    echo "You can monitor the progress with: journalctl -u $SERVICE_NAME -f"
fi
