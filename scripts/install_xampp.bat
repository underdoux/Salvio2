@echo off
setlocal enabledelayedexpansion

echo Salvio2 POS Installation for XAMPP
echo =================================
echo.

:: Check if running as administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo Please run this script as Administrator
    pause
    exit /b 1
)

:: Set default paths
set "XAMPP_PATH=C:\xampp"
set "HTDOCS_PATH=%XAMPP_PATH%\htdocs"
set "MYSQL_PATH=%XAMPP_PATH%\mysql\bin"
set "PHP_PATH=%XAMPP_PATH%\php"

:: Verify XAMPP installation
if not exist "%XAMPP_PATH%" (
    echo XAMPP not found at %XAMPP_PATH%
    echo Please install XAMPP first
    pause
    exit /b 1
)

:: Create database
echo Creating database...
echo.
set "DB_NAME=pos_pharma"
set "DB_USER=salvio2_user"

:: Generate random password
set "CHARS=ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789"
set "DB_PASS="
for /L %%i in (1,1,12) do (
    set /a "rand=!random! %% 62"
    for %%j in (!rand!) do set "DB_PASS=!DB_PASS!!CHARS:~%%j,1!"
)

:: Create database and user
"%MYSQL_PATH%\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS %DB_NAME% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
"%MYSQL_PATH%\mysql.exe" -u root -e "CREATE USER IF NOT EXISTS '%DB_USER%'@'localhost' IDENTIFIED BY '%DB_PASS%';"
"%MYSQL_PATH%\mysql.exe" -u root -e "GRANT ALL PRIVILEGES ON %DB_NAME%.* TO '%DB_USER%'@'localhost';"
"%MYSQL_PATH%\mysql.exe" -u root -e "FLUSH PRIVILEGES;"

:: Create application directory
echo Creating application directory...
echo.
set "APP_PATH=%HTDOCS_PATH%\Salvio2"
if not exist "%APP_PATH%" mkdir "%APP_PATH%"

:: Create storage directories
if not exist "%APP_PATH%\storage" mkdir "%APP_PATH%\storage"
if not exist "%APP_PATH%\storage\logs" mkdir "%APP_PATH%\storage\logs"
if not exist "%APP_PATH%\storage\backups" mkdir "%APP_PATH%\storage\backups"
if not exist "%APP_PATH%\storage\cache" mkdir "%APP_PATH%\storage\cache"
if not exist "%APP_PATH%\storage\uploads" mkdir "%APP_PATH%\storage\uploads"

:: Create database config
echo Creating database configuration...
echo.
(
    echo ^<?php
    echo class Database {
    echo     private $host = 'localhost';
    echo     private $db_name = '%DB_NAME%';
    echo     private $username = '%DB_USER%';
    echo     private $password = '%DB_PASS%';
    echo     public $conn;
    echo.
    echo     public function getConnection^(^) {
    echo         $this-^>conn = null;
    echo         try {
    echo             $this-^>conn = new PDO^(
    echo                 "mysql:host=" . $this-^>host . ";dbname=" . $this-^>db_name,
    echo                 $this-^>username,
    echo                 $this-^>password,
    echo                 array^(PDO::MYSQL_ATTR_INIT_COMMAND =^> "SET NAMES utf8mb4"^)
    echo             ^);
    echo             $this-^>conn-^>setAttribute^(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION^);
    echo         } catch^(PDOException $e^) {
    echo             echo "Connection error: " . $e-^>getMessage^(^);
    echo         }
    echo         return $this-^>conn;
    echo     }
    echo }
    echo ?^>
) > "%APP_PATH%\config\database.php"

:: Install database schema
echo Installing database schema...
echo.
for %%f in (
    create_tables.sql
    create_backup_tables.sql
    create_api_tables.sql
    create_settings_tables.sql
    create_insight_tables.sql
    create_notification_logs_table.sql
) do (
    echo Installing %%f...
    "%MYSQL_PATH%\mysql.exe" -u %DB_USER% -p%DB_PASS% %DB_NAME% < "%APP_PATH%\migrations\%%f"
)

:: Create admin user
echo Creating admin user...
echo.
set "ADMIN_EMAIL=admin@example.com"
:: Generate random admin password
set "ADMIN_PASS="
for /L %%i in (1,1,12) do (
    set /a "rand=!random! %% 62"
    for %%j in (!rand!) do set "ADMIN_PASS=!ADMIN_PASS!!CHARS:~%%j,1!"
)

:: Hash admin password using PHP
"%PHP_PATH%\php.exe" -r "echo password_hash('%ADMIN_PASS%', PASSWORD_DEFAULT);" > temp_hash.txt
set /p ADMIN_PASS_HASH=<temp_hash.txt
del temp_hash.txt

"%MYSQL_PATH%\mysql.exe" -u %DB_USER% -p%DB_PASS% %DB_NAME% -e "INSERT INTO users (username, email, password, role_id) VALUES ('admin', '%ADMIN_EMAIL%', '%ADMIN_PASS_HASH%', (SELECT id FROM roles WHERE name = 'admin'));"

:: Create Windows scheduled tasks for automation
echo Creating scheduled tasks...
echo.

:: Backup task
schtasks /create /tn "Salvio2_Backup" /tr "'%PHP_PATH%\php.exe' '%APP_PATH%\scripts\run_backup.php'" /sc daily /st 02:00 /ru System /f

:: BPOM scraper task
schtasks /create /tn "Salvio2_BPOM_Scraper" /tr "'%PHP_PATH%\php.exe' '%APP_PATH%\scripts\scrape_bpom.php'" /sc daily /st 03:00 /ru System /f

:: Notification processor task
schtasks /create /tn "Salvio2_Notifications" /tr "'%PHP_PATH%\php.exe' '%APP_PATH%\scripts\process_notifications.php'" /sc minute /mo 1 /ru System /f

echo.
echo Installation completed successfully!
echo ================================
echo.
echo Installation Details:
echo -------------------
echo Application URL: http://localhost/Salvio2
echo Admin Username: admin
echo Admin Password: %ADMIN_PASS%
echo.
echo Database Details:
echo ----------------
echo Database Name: %DB_NAME%
echo Database User: %DB_USER%
echo Database Password: %DB_PASS%
echo.
echo Please save these credentials in a secure location!
echo You should change the admin password after first login.
echo.
echo Next steps:
echo 1. Configure email settings in the admin panel
echo 2. Set up WhatsApp API credentials
echo 3. Configure company details
echo 4. Set up user roles and permissions
echo 5. Configure product categories
echo.
pause
