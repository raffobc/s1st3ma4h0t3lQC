<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=mysql', 'root', '');
    echo 'MySQL conectado';
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage();
}