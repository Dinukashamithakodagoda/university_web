# University Web

A simple PHP and MySQL student management system for handling admin access, student records, search, and exports.

## Overview

This project is designed to run locally with WAMP, XAMPP, or any similar PHP/MySQL stack. It includes a basic admin login, student registration, dashboard views, search, and an export utility.

## Features

- Admin login and session handling
- Student registration and management
- Search and export tools
- Photo uploads for student profiles
- MySQL database schema included in `database.sql`

## Requirements

- PHP 7.4 or later
- MySQL or MariaDB
- A local web server such as WAMP

## Setup

1. Copy the project into your web server folder, for example `c:\wamp64\www\university_web`.
2. Start Apache and MySQL.
3. Create a database named `university_db`.
4. Import `database.sql` into that database.
5. Open the app in your browser at `http://localhost/university_web/`.

If you prefer the MySQL command line, run:

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS university_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
mysql -u root -p university_db < database.sql
```

Or use the interactive MySQL client:

```sql
CREATE DATABASE IF NOT EXISTS university_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE university_db;
SOURCE database.sql;
```

## Default Admin Account

- Username: `admin`
- Password: `admin123`

The current database import creates the admin password as a secure hash. If you are upgrading an older install with plaintext passwords, the app can accept the legacy password once and upgrade it automatically after a successful login.

## Important Folders and Files

- `index.php` - login page
- `dashboard.php` - admin dashboard
- `register.php` - student registration
- `students.php` - student list and CRUD actions
- `search.php` - search page
- `export_students.php` - export utility
- `includes/db.php` - database connection
- `includes/bootstrap.php` - shared helper functions
- `uploads/student-photos/` - uploaded student images

## Troubleshooting

If database import fails because tables already exist, clear the conflicting tables first or import into a fresh database.

If login does not work:

1. Confirm the `admins` table exists.
2. Confirm the username is `admin`.
3. Regenerate the password hash with PHP if needed.

To generate a new bcrypt hash:

```bash
php -r "echo password_hash('admin123', PASSWORD_BCRYPT) . PHP_EOL;"
```

Then update the admin record in MySQL with the generated hash.

## Notes

- Make sure `uploads/student-photos/` is writable by the web server.
- The included `database.sql` is intended for a clean install.
- If you are migrating old data, export your existing database first before importing the new schema.
