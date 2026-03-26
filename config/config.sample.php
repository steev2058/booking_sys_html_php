<?php
return [
'app_name' => 'Booking System PHP',
'base_url' => getenv('BASE_URL') ?: 'http://localhost',
'timezone' => 'Asia/Damascus',
'db' => [
  'host' => getenv('DB_HOST') ?: '127.0.0.1',
  'port' => (int)(getenv('DB_PORT') ?: 3306),
  'database' => getenv('DB_NAME') ?: 'booking_sys_php',
  'username' => getenv('DB_USER') ?: 'booking_user',
  'password' => getenv('DB_PASS') ?: 'booking_pass',
  'charset' => 'utf8mb4'
],
'security' => [
  'session_name' => 'booking_php_session',
  'csrf_key' => '_csrf',
  'employee_prefix' => getenv('EMPLOYEE_PREFIX') ?: 'BBSY0',
  'manager_scoped_to_branch' => (getenv('MANAGER_SCOPED_TO_BRANCH') ?: '1') === '1',
  'otp_expire_minutes' => (int)(getenv('OTP_EXPIRE_MINUTES') ?: 5)
],
'sms' => [
  'endpoint' => getenv('SMS_ENDPOINT') ?: 'https://services.mtnsyr.com:7443/General/MTNSERVICES/ConcatenatedSender.aspx',
  'user' => getenv('SMS_USER') ?: 'ALbaraka2013',
  'pass' => getenv('SMS_PASS') ?: 'Jj2013',
  'from' => getenv('SMS_FROM') ?: 'AL-Baraka'
],
'reports' => [
  'dashboard_url' => getenv('REPORTS_DASHBOARD_URL') ?: 'http://localhost/admin/reports.php',
  'admin_emails' => array_values(array_filter(array_map('trim', explode(',', (string)(getenv('REPORT_ADMIN_EMAILS') ?: ''))))),
],
'smtp' => [
  'host' => getenv('SMTP_HOST') ?: '',
  'port' => (int)(getenv('SMTP_PORT') ?: 587),
  'user' => getenv('SMTP_USER') ?: '',
  'pass' => getenv('SMTP_PASS') ?: '',
  'from' => getenv('SMTP_FROM') ?: (getenv('SMTP_USER') ?: 'no-reply@example.com'),
],
];
