# Metals Test-Tool - PHP Web Application

This is the core web application for the Metals Testing Lab software. It provides all the necessary forms, reports, and backend logic for creating customer receipts and test reports. This application is designed to be run on a local server environment like Laragon and accessed directly or served via a custom desktop application.

## Core Features

-   **Receipt Management:** Create, view, print, and search customer receipts for metal drop-offs.
-   **Test Report Generation:** Form to input detailed metal purity analysis results.
-   **Automated Notifications:** Sends test reports to customers via SMS (Fast2SMS) and WhatsApp upon creation.
-   **Dynamic Sr. No. System:** Automatically generates unique serial numbers based on a letter-per-month and counter system.
-   **Data Export:** Export receipt and test report lists to PDF and Excel formats.
-   **Database Backups:** Includes a script for creating local gzipped SQL backups.
-   **Configurable Settings:** An easy-to-use config page allows for updating API keys and database credentials without changing code.

## Directory Structure

/metals
├── autofill.php # Provides suggestions for name/mobile fields
├── backup.php # Script for local database backups
├── backups/ # Default folder for local SQL backups
├── composer.json # PHP dependencies (FPDF, PhpSpreadsheet)
├── config.json # Main configuration file (DB, APIs)
├── config.php # Web interface for editing config.json
├── fetch_receipt_edit.php # Fetches data for pre-filling the receipt form
├── fetch_report.php # Fetches receipt data for the test report form
├── index.php # Main receipt creation form (Reception)
├── logs.php # Displays SMS and WhatsApp message logs
├── pre_fetch_report.php # Fetches existing test report data for editing
├── receipts.php # Page for viewing/searching all receipts
├── reports.php # Page for viewing/searching all test reports
├── testreportform.php # Main test report entry form (Lab)
└── vendor/ # Folder for composer dependencies


## Technical Stack

-   **Backend:** PHP
-   **Database:** MySQL
-   **Frontend:** HTML, CSS, Bootstrap, JavaScript
-- **Server:** Apache (Managed via Laragon)

## Setup and Installation

This application is intended to run on a WAMP stack. Laragon is the recommended environment.

1.  **Clone/Copy Project:** Place the entire `metals` directory into your Laragon `www` folder (e.g., `C:/laragon/www/metals`).
2.  **Database Setup:**
    -   Using Laragon's database manager (HeidiSQL), create a new database named `metal_store`.
    -   Import the database schema if you have one, or let the application create the tables. *Note: You will need to provide the SQL for table creation.*
3.  **Install Dependencies:**
    -   Navigate to the `metals` directory in your terminal.
    -   Run `composer install` to download the required PHP libraries (FPDF for PDF generation and PhpSpreadsheet for Excel).
4.  **Configuration:**
    -   Rename `config-example.json` to `config.json`.
    -   Open `config.json` and fill in your database credentials (`db_host`, `db_user`, `db_password`, `db_name`).
    -   Fill in the API keys for **Fast2SMS** and **WhatsApp**.
5.  **Start Services:** Launch Laragon and click **Start All** to run the Apache and MySQL servers.
6.  **Access the Application:**
    -   **Reception Form:** `http://localhost/metals/index.php`
    -   **Lab Form:** `http://localhost/metals/testreportform.php`

---