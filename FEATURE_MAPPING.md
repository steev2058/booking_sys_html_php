# Feature Mapping (Node booking_sys -> Native PHP/MySQL)

| Original Feature | PHP Rewrite Implementation | Status |
|---|---|---|
| Public booking form (branch/company/day/slot) | `index.php` with dynamic day/slot loading via GET + server-side checks | ✅ |
| CAPTCHA gate | Session numeric captcha in `index.php` | ✅ (compatible, simplified) |
| OTP send + verify flow | OTP row in `otp_codes`; verify before booking confirm | ✅ (SMS provider hook not wired) |
| Tomorrow-only booking | `booking_date_allowed()` in `includes/functions.php` | ✅ |
| Holiday exclusion | `is_holiday()` + day filtering | ✅ |
| Slot capacity (3) | count check before insert into `appointments` | ✅ |
| Admin login/session | `admin/login.php`, PHP session | ✅ |
| Role model (admin/manager/employee/branch_employee) | `require_login()` + branch scope helper | ✅ |
| Manager branch scope | `allowed_branch_clause()` and page-level restrictions | ✅ |
| CRUD branches | `admin/branches.php` | ✅ |
| CRUD companies | `admin/companies.php` | ✅ |
| CRUD business days | `admin/business_days.php` | ✅ |
| CRUD holidays | `admin/holidays.php` | ✅ |
| CRUD users | `admin/users.php` | ✅ |
| Appointments list/cancel | `admin/appointments.php` | ✅ |
| Daily report view | `admin/reports.php` (filter by date) | ✅ |
| Emailing reports | Not implemented in this pass | ⏭️ |
| MTN SMS integration | Config-ready only, not connected | ⏭️ |
