<?php
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/'));
define('MASTER_DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('MASTER_DB_NAME', getenv('DB_NAME') ?: 'hotel_master');
define('MASTER_DB_USER', getenv('DB_USER') ?: 'root');
define('MASTER_DB_PASS', getenv('DB_PASS') ?: '');
date_default_timezone_set('America/Lima');
$isProd = (getenv('APP_ENV') === 'production');
error_reporting($isProd ? 0 : E_ALL);
ini_set('display_errors', $isProd ? '0' : '1');
?>
