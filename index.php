<?php
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$target = ($base === '' ? '' : $base) . '/public/hotel/login';
header('Location: ' . $target, true, 302);
exit;
