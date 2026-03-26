<?php
require_once __DIR__.'/db.php';
function e($v): string {return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function csrf_token(): string {global $config;$k=$config['security']['csrf_key']??'_csrf';if(empty($_SESSION[$k]))$_SESSION[$k]=bin2hex(random_bytes(16));return $_SESSION[$k];}
function verify_csrf(): void {global $config;$k=$config['security']['csrf_key']??'_csrf';if($_SERVER['REQUEST_METHOD']==='POST'&&!hash_equals($_SESSION[$k]??'',$_POST[$k]??''))die('Invalid CSRF');}
function flash($k,$v=null){if($v!==null){$_SESSION['flash'][$k]=$v;return;} $o=$_SESSION['flash'][$k]??null; unset($_SESSION['flash'][$k]); return $o;}
function is_valid_phone($p): bool {return preg_match('/^09\d{8}$/',trim($p))===1;}
function is_valid_full_name($n): bool {return preg_match('/^[A-Za-z\x{0600}-\x{06FF}\s]{3,}$/u',trim($n))===1;}
function is_valid_transfer_number($v): bool {return preg_match('/^[A-Za-z0-9]+$/',trim($v))===1;}
function is_valid_employee_no($v): bool {global $config;$p=preg_quote(strtoupper($config['security']['employee_prefix']??'BBSY0'),'/');return preg_match('/^'.$p.'\d{3,}$/i',trim($v))===1;}
function role(): string {return $_SESSION['user']['role']??'';} function user_branch_id(){return $_SESSION['user']['branch_id']??null;}
function require_login($roles=[]): void {if(empty($_SESSION['user'])){header('Location: /admin/login.php');exit;} if($roles&&!in_array(role(),$roles,true)){http_response_code(403);die('Forbidden');}}
function manager_scoped(): bool {global $config; return (bool)($config['security']['manager_scoped_to_branch']??true);} 
function allowed_branch_clause(&$params): string {$r=role(); if($r==='employee'||$r==='branch_employee'||($r==='manager'&&manager_scoped())){$params[':branch_id']=(int)user_branch_id(); return ' AND a.branch_id=:branch_id ';} return '';}
function tomorrow_ymd(): string {return date('Y-m-d',strtotime('+1 day'));} function booking_date_allowed($d): bool {return $d>=tomorrow_ymd();}
function is_holiday($date): bool {$s=db()->prepare('SELECT 1 FROM holidays WHERE date=? AND active=1');$s->execute([$date]); return (bool)$s->fetchColumn();}
function otp_locked($phone): bool {$s=db()->prepare('SELECT locked_until FROM otp_security WHERE phone=?');$s->execute([$phone]);$r=$s->fetch(); return $r&&!empty($r['locked_until'])&&strtotime($r['locked_until'])>time();}
