# Visitor Detail Registration

PHP/MySQL visitor registration app with configurable visitor fields and admin reporting.

## Flow

- First-time visitor registers at the kiosk or by scanning a location QR.
- The app stores visitor profile data and records one visit.
- Same device scanning another location records movement as another visit.
- Admin can manage locations, form fields, users, and reports.
- Reports open in a separate date-range detail page.

## Default Admin

- Username: `admin`
- Password: `admin123`

Change this before real use.

## Setup

- Import `database/init.sql`.
- Confirm database settings in `config/config.php`.
- Open `views/public/kiosk.php` or `views/admin/log-masuk.php` through Apache.

