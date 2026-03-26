<?php
$configFile=__DIR__.'/config.php'; if(!file_exists($configFile)) copy(__DIR__.'/config.sample.php',$configFile);
$config=require $configFile; date_default_timezone_set($config['timezone']??'UTC');
if(session_status()!==PHP_SESSION_ACTIVE){session_name($config['security']['session_name']??'booking_session');session_start();}
