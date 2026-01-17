# Online Library Management System (OLMS)

A comprehensive Library Management System build with PHP and MySQL. This system allows for efficient management of books, users, and borrowing transactions.

## Features
- **Admin Dashboard**: Manage books, categories, users, and view system analytics.
- **Librarian Dashboard**: Manage book issues and returns.
- **Member Portal**: Browse books, request to borrow, and view reading history.
- **Reports**: Generate detailed reports for books and users with CSV export.
- **Responsive Design**: Modern, neumorphic UI built with Bootstrap 5.

## Technologies
- **Backend**: PHP 8.x
- **Database**: MySQL / MariaDB
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Server**: Apache (via XAMPP/LAMP)

## Installation Guide

### Prerequisites
- XAMPP, WAMP, or LAMP stack installed.
- PHP 8.0 or higher.
- MySQL/MariaDB.

### Steps
1.  **Clone or Download**:
    -   Place the `OLMS` folder inside your web server's root directory:
        -   XAMPP: `C:\xampp\htdocs\OLMS`
        -   Linux (LAMP): `/var/www/html/OLMS` or `/opt/lampp/htdocs/OLMS`

2.  **Database Setup**:
    -   Open phpMyAdmin (usually `http://localhost/phpmyadmin`).
    -   Create a new database named `library_db`.
    -   Import the `DB/library_db.sql` file located in the project folder.

3.  **Configuration**:
    -   The database connection is configured in `includes/db_connection.php`.
    -   Default settings:
        ```php
        $servername = "localhost";
        $username = "root";
        $password = "";
        $database = "library_db";
        ```
    -   Update these credentials if your local database uses a password.

4.  **Run the Application**:
    -   Open your browser and navigate to: `http://localhost/OLMS`

## Default Credentials

### Administrator
-   **Email**: `admin@library.com`
-   **Password**: `admin123`

### Librarian
-   **Email**: `librarian@library.com`
-   **Password**: `library`

## Usage
1.  Log in as **Admin** to add books, categories, and manage users.
2.  Log in as **Librarian** to approve borrow requests and mark books as returned.
3.  Register a new account or log in as a **Member** to browse and request books.

## Reporting
Admins and Librarians can access the **Reports** section to generate detailed lists of books and users, filterable by various criteria, with options to export data to CSV.
