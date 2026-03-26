<?php
require_once __DIR__.'/../config/bootstrap.php';
function db(): PDO { static $pdo=null; global $config; if($pdo) return $pdo; $d=$config['db']; $pdo=new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',$d['host'],$d['port'],$d['database'],$d['charset']),$d['username'],$d['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]); return $pdo; }
