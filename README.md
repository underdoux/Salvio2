# Salvio POS & Pharmaceutical Distribution Management System

A comprehensive system for managing pharmaceutical distribution, sales tracking, and profit sharing.

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled
- Composer (for future package management)

## Project Structure

```
/project-root/
├── app/
│   ├── controllers/    # Application controllers
│   ├── models/        # Database models
│   ├── views/         # View templates
│   └── helpers/       # Helper functions
├── public/           # Public-facing files
│   ├── index.php     # Entry point
│   ├── assets/       # CSS, JS, images
│   └── uploads/      # User uploads
├── config/          # Configuration files
├── database/        # Database migrations & seeds
└── storage/         # Logs and cache
```

## Installation

1. Clone the repository:
```bash
git clone [repository-url]
cd salvio-pos
```

2. Create a MySQL database:
```sql
CREATE DATABASE salvio_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. Import the database schema:
```bash
mysql -u root -p salvio_pos < database/migrations/001_initial_schema.sql
```

4. Configure your web server:
- Point the document root to the `public` directory
- Ensure mod_rewrite is enabled
- Set appropriate permissions:
```bash
chmod -R 755 public/uploads
chmod -R 755 storage/logs
```

5. Update database configuration:
- Copy `config/database.php` to `config/database.local.php`
- Update the database credentials in `database.local.php`

## Default Access

- URL: http://localhost/
- Admin Username: admin
- Admin Password: admin123

## Features

1. User & Role Management
   - Login/logout functionality
   - Role-based access control
   - User management (Admin only)

2. Investor & Capital Management
   - Add investors and capital amounts
   - Calculate ownership percentages
   - Monthly profit distribution reports

3. Product & Inventory Management
   - Product management (stocked/by-order)
   - BPOM data integration
   - Automatic category assignment
   - Stock tracking

4. Sales & Order Management
   - Sales transaction input
   - Discount validation
   - Order status tracking
   - Payment handling (Cash/Installments)

5. Commission Management
   - Commission calculation
   - Multi-level commission rates
   - Sales commission reports

6. Profit Sharing
   - Net profit calculation
   - Investor profit distribution
   - Transparent reporting

7. Reporting & Analytics
   - Sales reports
   - Commission reports
   - Price adjustment logs
   - Profit distribution reports
   - Market response tracking

## Security

- Password hashing using bcrypt
- CSRF protection
- XSS prevention
- SQL injection prevention
- Input validation
- Role-based access control

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is proprietary software. All rights reserved.
