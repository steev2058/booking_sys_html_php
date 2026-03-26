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

## Adapted
- Captcha implemented as server-side session numeric code instead of SVG challenge token.
- OTP workflow kept but SMS dispatch not hardwired (local OTP shown in flash for test environments).

## Skipped (with reason)
- SMTP report email auto-send: skipped for initial native baseline (adds external dependency/config complexity).
- MTN SMS endpoint call: left as extension point; many deployments require carrier-side credentials and allowlist.

## Deployment Notes
- For production, disable OTP preview message and wire real SMS provider.
- Add HTTPS + secure session cookie settings at web server level.
