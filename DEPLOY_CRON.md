# Daily Report Scheduler Deploy

## Recommended (cron)
Run every 5 minutes (safe due to built-in throttle + dedupe):

```cron
*/5 * * * * cd /root/.openclaw/workspace/booking_sys_html_php && /usr/bin/php scripts/report_scheduler.php >> logs/report_scheduler.log 2>&1
```

- Uses SMTP auth (`SMTP_HOST/SMTP_PORT/SMTP_USER/SMTP_PASS/SMTP_FROM`).
- Auto-generates daily report snapshots in `daily_reports` and dispatches email deduped via `report_email_logs`.
- In-app guard also triggers from web requests, but cron is preferred for parity with periodic scheduler behavior.

## Optional loop runner
If cron is unavailable:

```bash
php scripts/report_scheduler_loop.php 60
```

This runs every 60s and is safe with dedupe protections.
