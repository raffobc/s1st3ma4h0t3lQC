<?php
$db = new PDO('mysql:host=localhost;dbname=hotel_master;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$result = $db->query('DESCRIBE reservas')->fetchAll(PDO::FETCH_ASSOC);
foreach($result as $row) {
    echo $row['Field'] . PHP_EOL;
}
?>
