<?php
// Lightweight loop runner (safe for screen/tmux/systemd). Not required when cron is used.
require_once dirname(__DIR__) . '/includes/functions.php';

$interval = max(60, (int)($argv[1] ?? 60));
echo "report_scheduler_loop started, interval={$interval}s" . PHP_EOL;
while (true) {
    $res = run_reports_scheduler_guard(true);
    $line = '[' . date('c') . '] ' . (($res['ok'] ?? false) ? 'ok' : ('error: ' . ($res['error'] ?? 'unknown')));
    if (!empty($res['skipped'])) $line .= ' (' . $res['skipped'] . ')';
    echo $line . PHP_EOL;
    sleep($interval);
}
