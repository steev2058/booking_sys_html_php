<?php
require_once __DIR__.'/db.php';

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function cfg(string $key, $default = null) {
    global $config;
    $parts = explode('.', $key);
    $cur = $config;
    foreach ($parts as $p) {
        if (!is_array($cur) || !array_key_exists($p, $cur)) return $default;
        $cur = $cur[$p];
    }
    return $cur;
}

function csrf_token(): string {
    $k = cfg('security.csrf_key', '_csrf');
    if (empty($_SESSION[$k])) $_SESSION[$k] = bin2hex(random_bytes(16));
    return $_SESSION[$k];
}

function verify_csrf(): void {
    $k = cfg('security.csrf_key', '_csrf');
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals($_SESSION[$k] ?? '', $_POST[$k] ?? '')) {
        die('Invalid CSRF');
    }
}

function flash($k, $v = null) {
    if ($v !== null) { $_SESSION['flash'][$k] = $v; return; }
    $o = $_SESSION['flash'][$k] ?? null; unset($_SESSION['flash'][$k]); return $o;
}

function is_valid_phone($p): bool { return preg_match('/^09\d{8}$/', trim($p)) === 1; }
function is_valid_full_name($n): bool { return preg_match('/^[A-Za-z\x{0600}-\x{06FF}\s]{3,}$/u', trim($n)) === 1; }
function is_valid_transfer_number($v): bool { return preg_match('/^[A-Za-z0-9]+$/', trim($v)) === 1; }
function is_valid_employee_no($v): bool {
    $p = preg_quote(strtoupper(cfg('security.employee_prefix', 'BBSY0')), '/');
    return preg_match('/^' . $p . '\d{3,}$/i', trim($v)) === 1;
}

function role(): string { return $_SESSION['user']['role'] ?? ''; }
function user_branch_id() { return $_SESSION['user']['branch_id'] ?? null; }

function require_login($roles = []): void {
    if (empty($_SESSION['user'])) { header('Location: /admin/login.php'); exit; }
    if ($roles && !in_array(role(), $roles, true)) { http_response_code(403); die('Forbidden'); }
}

function manager_scoped(): bool { return (bool)cfg('security.manager_scoped_to_branch', true); }

function allowed_branch_clause(&$params): string {
    $r = role();
    if ($r === 'employee' || $r === 'branch_employee' || ($r === 'manager' && manager_scoped())) {
        $params[':branch_id'] = (int)user_branch_id();
        return ' AND a.branch_id=:branch_id ';
    }
    return '';
}

function tomorrow_ymd(): string { return date('Y-m-d', strtotime('+1 day')); }
function booking_date_allowed($d): bool { return $d >= tomorrow_ymd(); }

function is_holiday($date): bool {
    $s = db()->prepare('SELECT 1 FROM holidays WHERE date=? AND active=1');
    $s->execute([$date]);
    return (bool)$s->fetchColumn();
}

function otp_locked($phone): bool {
    $s = db()->prepare('SELECT locked_until FROM otp_security WHERE phone=?');
    $s->execute([$phone]);
    $r = $s->fetch();
    return $r && !empty($r['locked_until']) && strtotime($r['locked_until']) > time();
}

function send_sms_raw(string $phone, string $msg): array {
    $endpoint = trim((string)cfg('sms.endpoint', ''));
    $user = trim((string)cfg('sms.user', ''));
    $pass = trim((string)cfg('sms.pass', ''));
    $from = trim((string)cfg('sms.from', 'AL-Baraka'));
    if (!$endpoint || !$user || !$pass) return ['ok' => false, 'error' => 'SMS config missing'];

    $qs = http_build_query([
        'User' => $user,
        'Pass' => $pass,
        'From' => $from,
        'Gsm'  => $phone,
        'Msg'  => $msg,
        'Lang' => '0'
    ]);
    $url = $endpoint . '?' . $qs;

    $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 8]]);
    $resp = @file_get_contents($url, false, $ctx);
    $ok = $resp !== false;
    return ['ok' => $ok, 'text' => $ok ? substr((string)$resp, 0, 500) : 'request failed'];
}

function send_sms_otp(string $phone, string $code): array {
    return send_sms_raw($phone, "رمز التحقق الخاص بك هو: {$code}");
}

function send_sms_booking_confirmation(string $phone, string $fullName, string $branchName, string $transfer, string $slotFrom, string $slotTo, string $date): array {
    $msg = "السيد {$fullName} تم حجز دور لمراجعة فرع {$branchName} لاستلام حوالة {$transfer} من الساعة {$slotFrom} إلى الساعة {$slotTo} بتاريخ {$date}.";
    return send_sms_raw($phone, $msg);
}

function report_rows_for_date(string $dateYmd, ?int $branchId = null): array {
    $pdo = db();
    $sql = 'SELECT a.full_name,a.phone,a.booking_date,a.slot_time,a.slot_to,b.name AS branch_name
            FROM appointments a
            LEFT JOIN branches b ON b.id=a.branch_id
            WHERE a.booking_date=:d AND a.status="booked"';
    $params = [':d' => $dateYmd];
    if ($branchId) { $sql .= ' AND a.branch_id=:b'; $params[':b'] = $branchId; }
    $sql .= ' ORDER BY a.branch_id,a.slot_time';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll() ?: [];
}

function build_excel_html(array $rows): string {
    $trs = '';
    foreach ($rows as $r) {
        $trs .= '<tr>'
            . '<td>' . e($r['full_name'] ?? '-') . '</td>'
            . '<td style="mso-number-format:\'\\@\'">' . e($r['phone'] ?? '-') . '</td>'
            . '<td>' . e($r['booking_date'] ?? '-') . '</td>'
            . '<td>' . e(($r['slot_time'] ?? '-') . ' - ' . ($r['slot_to'] ?? '-')) . '</td>'
            . '<td>' . e($r['branch_name'] ?? '-') . '</td>'
            . '</tr>';
    }
    return '<html><head><meta charset="UTF-8"></head><body dir="rtl"><table border="1" cellspacing="0" cellpadding="6"><thead><tr><th>الاسم</th><th>رقم الموبايل</th><th>تاريخ الحجز</th><th>وقت الحجز</th><th>اسم الفرع</th></tr></thead><tbody>' . $trs . '</tbody></table></body></html>';
}

function otp_can_send(string $phone): array {
    $pdo = db();
    $windowMinutes = (int)(getenv('OTP_WINDOW_MINUTES') ?: 10);
    $maxPerWindow = (int)(getenv('OTP_MAX_PER_WINDOW') ?: 5);
    $st = $pdo->prepare('SELECT * FROM otp_security WHERE phone=? LIMIT 1');
    $st->execute([$phone]);
    $row = $st->fetch();
    if (!$row) {
        $pdo->prepare('INSERT INTO otp_security (phone,send_count,window_start,verify_fail_count,locked_until) VALUES (?,1,NOW(),0,NULL)')->execute([$phone]);
        return [true, null];
    }
    $ws = !empty($row['window_start']) ? strtotime($row['window_start']) : 0;
    if (!$ws || (time() - $ws) > ($windowMinutes * 60)) {
        $pdo->prepare('UPDATE otp_security SET send_count=1, window_start=NOW() WHERE phone=?')->execute([$phone]);
        return [true, null];
    }
    if ((int)$row['send_count'] >= $maxPerWindow) return [false, "Too many OTP requests. Max {$maxPerWindow} every {$windowMinutes} minutes"];
    $pdo->prepare('UPDATE otp_security SET send_count=send_count+1 WHERE phone=?')->execute([$phone]);
    return [true, null];
}

function otp_track_fail(string $phone): bool {
    $pdo = db();
    $maxFails = (int)(getenv('OTP_MAX_VERIFY_ATTEMPTS') ?: 5);
    $lockMinutes = (int)(getenv('OTP_LOCK_MINUTES') ?: 30);
    $st = $pdo->prepare('SELECT * FROM otp_security WHERE phone=? LIMIT 1');
    $st->execute([$phone]);
    $row = $st->fetch();
    if (!$row) {
        $pdo->prepare('INSERT INTO otp_security (phone,send_count,window_start,verify_fail_count,locked_until) VALUES (?,0,NOW(),1,NULL)')->execute([$phone]);
        return false;
    }
    $fails = ((int)$row['verify_fail_count']) + 1;
    if ($fails >= $maxFails) {
        $pdo->prepare('UPDATE otp_security SET verify_fail_count=0, locked_until=DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE phone=?')->execute([$lockMinutes, $phone]);
        return true;
    }
    $pdo->prepare('UPDATE otp_security SET verify_fail_count=? WHERE phone=?')->execute([$fails, $phone]);
    return false;
}

function otp_reset_fail(string $phone): void {
    db()->prepare('UPDATE otp_security SET verify_fail_count=0, locked_until=NULL WHERE phone=?')->execute([$phone]);
}
function otp_track_verify_fail(string $phone): bool { return otp_track_fail($phone); }
function otp_reset_verify_fail(string $phone): void { otp_reset_fail($phone); }

function send_report_email(string $to, string $dateYmd, array $rows): array {
    $host = trim((string)cfg('smtp.host', ''));
    $port = (int)cfg('smtp.port', 587);
    $user = trim((string)cfg('smtp.user', ''));
    $pass = trim((string)cfg('smtp.pass', ''));
    $from = trim((string)cfg('smtp.from', $user ?: 'no-reply@albarakasyria.com'));
    if (!$to) return ['ok' => false, 'error' => 'recipient missing'];

    // lightweight SMTP via mail() fallback for shared hosting
    $boundary = 'b_' . md5((string)microtime(true));
    $subject = "تقرير حجوزات منصة الدور - {$dateYmd}";
    $htmlBody = '<div dir="rtl" style="font-family:Arial"><h3>تقرير الحجوزات اليومية - ' . e($dateYmd) . '</h3><p>مرفق ملف Excel.</p></div>';
    $xls = build_excel_html($rows);
    $filename = "daily_booking_report_{$dateYmd}.xls";

    $headers = [];
    $headers[] = "From: {$from}";
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";

    $body = "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $body .= $htmlBody . "\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: application/vnd.ms-excel; name=\"{$filename}\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
    $body .= chunk_split(base64_encode($xls)) . "\r\n";
    $body .= "--{$boundary}--";

    $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
    return ['ok' => (bool)$ok, 'error' => $ok ? null : 'mail() failed'];
}

function generate_daily_reports_if_needed(?string $dateYmd = null): void {
    $dateYmd = $dateYmd ?: date('Y-m-d');
    $pdo = db();

    $emails = array_filter(array_map('trim', explode(',', (string)getenv('REPORT_ADMIN_EMAILS'))));
    $defaultAdmin = trim((string)getenv('DEFAULT_ADMIN_REPORT_EMAIL'));
    if ($defaultAdmin !== '') $emails[] = $defaultAdmin;

    $users = $pdo->query("SELECT role, branch_id, report_email FROM dashboard_users WHERE active=1 AND report_email IS NOT NULL AND report_email<>''")->fetchAll() ?: [];
    $recipients = [];
    foreach ($emails as $email) {
      $clean = strtolower(trim((string)$email));
      if ($clean) $recipients[$clean] = ['branch_id' => null];
    }
    foreach ($users as $u) {
      $clean = strtolower(trim((string)$u['report_email']));
      if (!$clean) continue;
      $role = strtolower(trim((string)$u['role']));
      $branchId = in_array($role, ['manager','employee','branch_employee'], true) ? ((int)$u['branch_id'] ?: null) : null;
      $recipients[$clean] = ['branch_id' => $branchId];
    }

    foreach ($recipients as $email => $meta) {
      $scope = $meta['branch_id'] ? (int)$meta['branch_id'] : null;
      $dedupe = sha1($dateYmd.'::'.$email.'::'.($scope ?: 'all'));
      $ck = $pdo->prepare('SELECT 1 FROM report_email_logs WHERE dedupe_key=? LIMIT 1');
      $ck->execute([$dedupe]);
      if ($ck->fetchColumn()) continue;

      $rows = report_rows_for_date($dateYmd, $scope);
      if (!$rows) continue;
      $send = send_report_email($email, $dateYmd, $rows);
      if ($send['ok']) {
        $pdo->prepare('INSERT INTO report_email_logs (dedupe_key,email,report_date,branch_id,sent_at) VALUES (?,?,?,?,NOW())')->execute([$dedupe, $email, $dateYmd, $scope]);
      }
    }
}

function send_daily_reports_emails_if_needed(?string $dateYmd = null): void {
    generate_daily_reports_if_needed($dateYmd);
}
