# Feature Mapping (Node booking_sys -> Native PHP/MySQL)

| Original Feature | PHP Rewrite Implementation | Status |
|---|---|---|
| Public booking form (branch/company/day/slot) | `index.php` with dynamic day/slot loading + server-side checks | ✅ |
| CAPTCHA gate | Session numeric captcha in `index.php` | ✅ (compatible variant) |
| OTP send + verify flow | `otp_codes` + `otp_security` + lock/window rules in `includes/functions.php` | ✅ |
| SMS OTP integration (MTN style) | `send_sms_raw()` uses exact query keys: `User,Pass,From,Gsm,Msg,Lang=0` via `SMS_*` env keys | ✅ |
| Booking confirmation SMS | Sent after successful booking via same SMS transport | ✅ |
| Tomorrow-only booking | `booking_date_allowed()` | ✅ |
| Holiday exclusion | `is_holiday()` + date filtering | ✅ |
| Slot capacity (3) | count check before insert | ✅ |
| Admin login/session | `admin/login.php`, PHP session | ✅ |
| Role model (admin/manager/employee/branch_employee) | `require_login()` + branch scope helper | ✅ |
| Manager branch scope | `allowed_branch_clause()` and page restrictions | ✅ |
| CRUD branches | `admin/branches.php` | ✅ |
| CRUD companies | `admin/companies.php` | ✅ |
| CRUD business days | `admin/business_days.php` | ✅ |
| CRUD holidays | `admin/holidays.php` | ✅ |
| CRUD users | `admin/users.php` | ✅ |
| Appointments list/cancel | `admin/appointments.php` | ✅ |
| Daily report view | `admin/reports.php` | ✅ |
| Daily report generate/export | `report_rows_for_date()` live compute + Excel export button | ✅ |
| Daily report email with Excel attachment | `send_daily_reports_emails_if_needed()` + MIME `.xls` attachment | ✅ |
| Report recipients behavior | `REPORT_ADMIN_EMAILS` + active users `report_email` with branch scope | ✅ |
| Report dedupe logs | `report_email_logs` table | ✅ |
