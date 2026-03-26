# booking_sys_html_php

Native rewrite of `booking_sys` using **PHP + MySQL + Bootstrap**.

## Structure
- `/index.php` public booking
- `/admin` admin/auth/CRUD/report pages
- `/includes` DB/auth/helpers/layout + SMS/report logic
- `/config` config bootstrap/sample
- `/database/schema.sql` schema + sample seed
- `/assets` static styles
- `/uploads` reserved upload path

## Requirements
- PHP 8.1+
- MySQL 8+
- Apache/Nginx with PHP-FPM
- Mail transport configured on host (for report emails)

## Env Compatibility (same key names as old project)
Copy `.env.example` to `.env` and set values:

- SMS:
  - `SMS_ENDPOINT`
  - `SMS_USER`
  - `SMS_PASS`
  - `SMS_FROM`
- OTP limits:
  - `OTP_WINDOW_MINUTES`
  - `OTP_MAX_PER_WINDOW`
  - `OTP_MAX_VERIFY_ATTEMPTS`
  - `OTP_LOCK_MINUTES`
- DB:
  - `DB_HOST` `DB_PORT` `DB_NAME` `DB_USER` `DB_PASS`
- Reports:
  - `REPORTS_DASHBOARD_URL`
  - `REPORT_ADMIN_EMAILS`
  - `DEFAULT_ADMIN_REPORT_EMAIL`
- Mail sender identity:
  - `SMTP_FROM`

> Note: in current PHP implementation, mail send uses host mail transport (`mail()`), while preserving old report behavior and recipient logic.

## Quick Start (Local)
1. Copy env and config:
   - `cp .env.example .env`
   - `cp config/config.sample.php config/config.php`
2. Import DB:
   - `mysql -u root -p < database/schema.sql`
3. Serve app from repo root (document root).
4. Open:
   - Public: `/`
   - Admin: `/admin/login.php`

## Default Admin
- employee_no: `BBSY0001`
- password hash is seeded placeholder; set your own password after install:

```sql
UPDATE dashboard_users
SET password_hash = '$2y$10$replace_with_real_bcrypt_hash_here'
WHERE employee_no='BBSY0001';
```

## Reports / Email
- Daily report rows are generated from booked appointments.
- Admin can trigger send from `/admin/reports.php`.
- Recipients include:
  - `REPORT_ADMIN_EMAILS`
  - active users with `report_email`.
- Dedupe is tracked in `report_email_logs`.

## VPS Deploy (Apache/Nginx)
- Point vhost document root to project root.
- Ensure writable dirs: `uploads/`, `logs/`.
- Configure PHP-FPM and MySQL access.
- Configure server mail transport (sendmail/postfix/msmtp) for report emails.
- Enable HTTPS and secure cookies.

## Notes
- All DB operations use prepared statements.
- Manager/employee views are branch-scoped.
- SMS requests use old project MTN query pattern (`User/Pass/From/Gsm/Msg/Lang`).
