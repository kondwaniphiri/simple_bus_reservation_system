# Hard Work Logistics - Bus Reservation System

**Project author and maintainer: Kondwani Phiri**
Attribution marker: `KP-HWL-2025` · [Authorship](AUTHORSHIP.md)

A bus reservation application built with plain PHP, MySQL, HTML, CSS and jQuery. No framework or frontend build step is required.

## Features

- Public homepage with routes, fares, next departures, branch contacts, travel information and a contact form.
- Customer registration, sign-in, seat reservations, printable tickets and booking history.
- Central administration and branch-scoped manager access.
- Fleet, drivers, trip schedules, maintenance records and passenger manifests.
- Staff enquiry inbox, activity history and CSV exports.
- Transactional seat allocation, session authentication, CSRF protection and prepared queries.

Online reservations do not collect payments. Contact enquiries are stored in the staff inbox; outgoing email is not configured.

## Run the project

Follow [SETUP.md](SETUP.md) for database import, `.env` configuration and server startup.

```powershell
php -S 127.0.0.1:8080 router.php
```

Open `http://localhost:8080`. The public homepage is `index.html`; account forms are in `account.html`. Booking requires sign-in. Existing local account credentials are recorded privately in `.env`.

## Database

**`Backend/bus_ticket.sql` is the only database export.** It contains the current working database schema and records, including users, branches, trips, reservations, fleet, drivers, maintenance, contact enquiries and audit history. Import it into an empty MySQL 8 database.

The snapshot preserves original 2025 history and the current operating schedule for 24 October–6 November 2026. Date values stay fixed on import. Passwords remain hashed; `.env` secrets are not exported. Temporary login-throttle state starts empty.

Regenerate the export from the database configured in `.env`:

```powershell
php Backend/BusReserve/export_database.php
```

For existing installations, `php Backend/BusReserve/migrate.php` applies repeatable schema upgrades without reimporting the snapshot. Optional `seed_demo.php` and `seed_operational.php` populate the supplied historical and operating datasets; normal operations should use the dashboard.

## Tests

```powershell
node tests/static.js
php tests/environment.php
node tests/homepage-public.js
node tests/customer-access.js
node tests/contact-flow.js
php tests/demo-data.php
php tests/operational-data.php
```

HTTP tests use the running local server. Contact tests create enquiry records; `public-flow.js` creates a uniquely named onboarding account and prints its email. Use a disposable database for these write tests.

The full integration suite resets records and **must use a separate database whose name ends in `_test`**. Import the SQL there, configure `BUS_DB_NAME` for that database in both server and test terminals, start PHP on port 8087, then run `node tests/integration.js`. See [SETUP.md](SETUP.md).

## Project structure

- `index.html`, `account.html`, `reserve.html`: public, account and booking pages.
- `admin.dashboard.html`: staff operations.
- `Backend/BusReserve/`: API, database access, configuration and CLI tools.
- `Backend/bus_ticket.sql`: complete current database snapshot.
- `js/`, `style/`, `img/`: active application assets.
- `tests/`: regression and data-consistency checks.
- `.env`: private local configuration; `.env.example`: shareable template.

The PHP router and Apache rules block hidden files, SQL exports and tests from web access. Keep the application and API on the same origin. Vendor jQuery retains its own attribution; Kondwani Phiri's project attribution appears in custom source headers and page credits.
