<?php
function load_env_file(string $path): void {
    if (!is_file($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v, " \t\n\r\0\x0B\"");
        if ($k !== '' && getenv($k) === false) {
            putenv("$k=$v");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }
    }
}

load_env_file(dirname(__DIR__) . '/.env');

$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) copy(__DIR__ . '/config.sample.php', $configFile);
$config = require $configFile;
date_default_timezone_set($config['timezone'] ?? 'UTC');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($config['security']['session_name'] ?? 'booking_session');
    session_start();
}
