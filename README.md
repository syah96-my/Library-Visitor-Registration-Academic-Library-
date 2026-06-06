# Visitor Detail Registration

A lightweight PHP/MySQL visitor registration and reporting app for a local XAMPP-style deployment.

## Features

- Kiosk registration form.
- Location QR links for visitor movement tracking.
- Visitor digital card retrieval by reusable card token.
- One device/check-in counts as one visit.
- Configurable visitor fields from the admin panel.
- Default fields: `Nama`, `Faculty`, and `Semester`.
- Admin report page with date range selection, summary counts, custom field counts, and detailed rows.
- Basic admin management for locations, fields, users, and reports.

## Screenshots

### Visitor Card

![Visitor card](screenshoot/1.%20visitor%20card.png)

### Custom Field Settings

![Custom field settings](screenshoot/2.%20setting%20field.png)

### Registration Form

![Registration form](screenshoot/3.%20registration%20form.png)

### Statistic Report

![Statistic report](screenshoot/4.%20statistic.png)

## Local Setup

1. Place the project in your web root, for example:

```text
c:\xampp-8.0\htdocs\visitor - detail
```

2. Import the database:

```text
database/init.sql
```

3. Check database credentials in:

```text
config/config.php
```

4. Open the kiosk:

```text
http://localhost/visitor%20-%20detail/views/public/kiosk.php
```

5. Open admin:

```text
http://localhost/visitor%20-%20detail/views/admin/log-masuk.php
```

Default admin:

```text
Username: admin
Password: admin123
```

Change the default admin password before any real use.

## Useful URLs

```text
Admin:
http://localhost/visitor%20-%20detail/views/admin/log-masuk.php

Kiosk:
http://localhost/visitor%20-%20detail/views/public/kiosk.php
```

Location QR URLs are generated from the admin Location tab.

## Security Notes

- Admin APIs require login and CSRF tokens.
- Admin login uses a CSRF token.
- Session cookies are configured with `HttpOnly` and `SameSite=Lax`.
- Public visitor IDs are stored in cookies; use HTTPS in production.
- Default credentials are for local setup only.
- Review database credentials before publishing or deployment.

## Third-Party Services

- Bulma CSS CDN.
- Iconify CDN.
- QRTag QR image endpoint for card QR display.
