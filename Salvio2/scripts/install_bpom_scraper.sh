#!/bin/bash

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "Please run as root"
    exit 1
fi

# Set variables
APP_PATH="/path/to/Salvio2/Salvio2"
SERVICE_NAME="bpom-scraper"
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
echo "Enabling and starting BPOM scraper timer..."
systemctl enable $SERVICE_NAME.timer
systemctl start $SERVICE_NAME.timer

# Create logrotate config
echo "Setting up log rotation..."
cat > /etc/logrotate.d/salvio2-bpom << EOF
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

echo "Installation complete!"
echo "The BPOM scraper will run daily at 2 AM"
echo "You can check the status with: systemctl status $SERVICE_NAME.timer"
echo "You can view logs with: journalctl -u $SERVICE_NAME"
echo "Log files are located in: $LOG_DIR"

# Run initial scrape
read -p "Would you like to run an initial BPOM scrape now? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Running initial BPOM scrape..."
    systemctl start $SERVICE_NAME.service
    echo "You can monitor the progress with: journalctl -u $SERVICE_NAME -f"
fi
