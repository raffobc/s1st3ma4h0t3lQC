<?php
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/'));
define('MASTER_DB_HOST', 'localhost');
define('MASTER_DB_NAME', 'hotel_master');
define('MASTER_DB_USER', 'root');
define('MASTER_DB_PASS', '');
date_default_timezone_set('America/Lima');
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
