# CONVERSION_REPORT

## Rebuilt
- Full native PHP/MySQL app (no Node runtime/framework reuse).
- Bootstrap responsive UI for booking + admin.
- Session auth, role checks, branch-scoped manager behavior.
- Core booking rules:
  - tomorrow-only booking,
  - holiday blocking,
  - per-slot capacity 3,
  - validations: employee_no `BBSY0xxx`, phone `09xxxxxxxx`, full_name letters-only (Arabic/English), transfer number alnum English only.
- Admin CRUDs: branches, companies, business days, holidays, users, appointments, reports.
- SQL schema + seed data + sample config.

## Parity Upgrade (this phase)
- Added `.env` loader and old-project-compatible env keys.
- Implemented real SMS transport using old request style:
  - endpoint `SMS_ENDPOINT`
  - query params: `User`, `Pass`, `From`, `Gsm`, `Msg`, `Lang=0`
- OTP hardening parity:
  - `OTP_WINDOW_MINUTES`
  - `OTP_MAX_PER_WINDOW`
  - `OTP_MAX_VERIFY_ATTEMPTS`
  - `OTP_LOCK_MINUTES`
- Added booking confirmation SMS after successful booking.
- Implemented daily reports generation + dedupe email logs.
- Implemented email send with Excel attachment (`.xls` HTML table) and recipients parity:
  - `REPORT_ADMIN_EMAILS`
  - active dashboard users with `report_email`
  - manager/employee branch-scoped report rows.

## Adapted
- Captcha remains server-side session numeric code (not SVG token format).
- Email sending in PHP uses MIME + `mail()` transport (works with configured MTA/sendmail). This preserves behavior/output shape; SMTP-auth-native transport would require adding a mail library.

## Remaining gaps to 100%
1. **Frontend parity**: current UI is simpler than original SPA-like JS UI/UX.
2. **SMTP auth transport parity**: Node used authenticated SMTP directly via nodemailer. PHP version currently relies on server mail transport unless external mailer library is added.
3. **Automatic minute scheduler parity**: Node ran periodic report checks every minute. PHP version provides generation on booking events + manual generate button; true periodic scheduler should be wired via cron.
