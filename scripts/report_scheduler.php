<?php
// Cron-friendly safe runner for daily report snapshots + SMTP email dispatch.
require_once dirname(__DIR__) . '/includes/functions.php';

$date = $argv[1] ?? date('Y-m-d');
$force = in_array('--force', $argv, true);

try {
    if ($force) {
        generate_daily_reports_if_needed($date);
        $res = ['ok' => true, 'forced' => true];
    } else {
        $res = run_reports_scheduler_guard(false);
        if (($res['ok'] ?? false) && empty($res['skipped']) && $date !== date('Y-m-d')) {
            generate_daily_reports_if_needed($date);
        }
    }
} catch (Throwable $e) {
    $res = ['ok' => false, 'error' => $e->getMessage()];
}

if (!($res['ok'] ?? false)) {
    fwrite(STDERR, "Scheduler failed: " . ($res['error'] ?? 'unknown') . PHP_EOL);
    exit(1);
}

echo "Scheduler OK for {$date}" . (!empty($res['skipped']) ? " ({$res['skipped']})" : '') . (!empty($res['forced']) ? ' (forced)' : '') . PHP_EOL;
