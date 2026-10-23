# Setup - Hard Work Logistics

**Author: Kondwani Phiri** · `KP-HWL-2025`

## Requirements

- PHP 8.0+ with mysqli/mysqlnd and sessions.
- MySQL 8.0+; the schema uses `utf8mb4_0900_ai_ci`.
- A modern browser. Node.js 18+ is needed only for JavaScript tests.

## 1. Import the database

Create an empty database named `bus_ticket`, select it in phpMyAdmin or your SQL client, and import:

```text
Backend/bus_ticket.sql
```

This is the complete current schema and data. No second SQL file or initial migration is required. Do not import it over a working database; the export does not drop existing tables.

The file preserves historical records and contains bookable departures for 24 October–6 November 2026. Imported dates do not move with the import date. Add or extend trips in the dashboard as needed.

## 2. Configure the application

The working local `.env` already contains its verified database credentials and available account references. Keep that file private. For a different installation, copy `.env.example` to `.env` and fill in:

```dotenv
BUS_DB_HOST="127.0.0.1"
BUS_DB_PORT="3306"
BUS_DB_NAME="bus_ticket"
BUS_DB_USER="your_database_user"
BUS_DB_PASSWORD="your_database_password"
CONTACT_RATE_LIMIT="5"
CONTACT_MIN_LENGTH="20"
CONTACT_MAX_LENGTH="4000"
```

PHP loads `.env` automatically. System environment variables override file values. `ACCOUNT_*` entries are private login references; authentication still uses the password hashes in MySQL. The export never includes `.env` secrets.

## 3. Start the site

Open a terminal in the project root:

```powershell
php -S 127.0.0.1:8080 router.php
```

Visit `http://localhost:8080`. Visitors see the homepage first. Sign up to unlock trip search, booking and account features. Staff accounts enter the operations dashboard according to their role. Refer to `.env` for existing local credentials.

Serve the entire project from one directory. Do not copy the backend into a separate web folder. For Apache 2.4, enable `mod_rewrite` and the included `.htaccess` rules with `AllowOverride AuthConfig FileInfo Options`. Use HTTPS in deployment.

## 4. Check the installation

- Public routes show starting fares and a next departure.
- A customer can sign in, view departures and reserve an available seat.
- Staff can manage their permitted branches and view contact enquiries.
- `.env`, SQL files and tests cannot be downloaded through the web server.

Contact submissions go to **Enquiries** in the staff dashboard. Outgoing email requires a separate SMTP integration.

## Updating an existing installation

Back up the database, then run schema upgrades:

```powershell
php Backend/BusReserve/migrate.php
```

After changing records, regenerate the single SQL snapshot:

```powershell
php Backend/BusReserve/export_database.php
```

The exporter reads the configured working database using a consistent snapshot. It keeps actual date values and password hashes, and leaves the transient login-throttle table empty. Database credentials remain in `.env`.

## Full regression tests

Create a separate empty database such as `bus_reservation_test` and import `Backend/bus_ticket.sql`. In a test server terminal:

```powershell
$env:BUS_DB_NAME = 'bus_reservation_test'
php -S 127.0.0.1:8087 router.php
```

In another terminal, set the same database name and run:

```powershell
$env:BUS_DB_NAME = 'bus_reservation_test'
node tests/integration.js
```

This suite deletes and recreates fixture records in that test database. Never point it at your working database. Ordinary page/asset checks are available with `node tests/static.js`.

## Troubleshooting

- **Database connection fails:** check MySQL, `.env`, and any overriding `BUS_DB_*` system variables.
- **No departures:** add future schedules with active vehicles, valid drivers and valid service dates.
- **Port 8080 is busy:** use another local port and open that address.
- **Google map is blank:** use the Open Kitwe in Google Maps link; map loading needs access to Google.
