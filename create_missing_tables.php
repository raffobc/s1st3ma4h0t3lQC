<?php
require_once __DIR__ . "/config/MasterDatabase.php";

$masterDb = MasterDatabase::getConnection();
$hotels = $masterDb->query("SELECT * FROM hoteles WHERE estado = 'activo'")->fetchAll();

foreach ($hotels as $hotel) {
    try {
        $hotelDb = new PDO(
            "mysql:host={$hotel['db_host']};dbname={$hotel['db_name']};charset=utf8mb4",
            $hotel['db_user'],
            $hotel['db_password']
        );
        $hotelDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Crear tabla huespedes si no existe
        $hotelDb->exec("
            CREATE TABLE IF NOT EXISTS huespedes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                reserva_id INT NOT NULL,
                nombre VARCHAR(255) NOT NULL,
                documento VARCHAR(20) NOT NULL,
                fecha_nacimiento DATE,
                telefono VARCHAR(20),
                email VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE CASCADE,
                INDEX idx_reserva (reserva_id),
                INDEX idx_documento (documento)
            ) ENGINE=InnoDB
        ");

        // Crear tabla reserva_servicios si no existe
        $hotelDb->exec("
            CREATE TABLE IF NOT EXISTS reserva_servicios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                reserva_id INT NOT NULL,
                servicio_id INT NOT NULL,
                cantidad INT DEFAULT 1,
                precio_unitario DECIMAL(10, 2) NOT NULL,
                notas TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE CASCADE,
                FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE CASCADE,
                INDEX idx_reserva (reserva_id),
                INDEX idx_servicio (servicio_id)
            ) ENGINE=InnoDB
        ");

        echo "Tablas creadas/actualizadas para hotel: {$hotel['nombre']}\n";
    } catch (Exception $e) {
        echo "Error en hotel {$hotel['nombre']}: {$e->getMessage()}\n";
    }
}

echo "Proceso completado.\n";
?>
