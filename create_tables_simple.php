<?php
// Script simple para crear tablas faltantes
try {
    // Conectar a la base de datos del hotel demo
    $pdo = new PDO(
        "mysql:host=localhost;dbname=hotel_demo;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "Conectado a la base de datos hotel_demo\n";

    // Crear tabla huespedes
    $pdo->exec("
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

    echo "Tabla huespedes creada\n";

    // Crear tabla reserva_servicios
    $pdo->exec("
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

    echo "Tabla reserva_servicios creada\n";
    echo "Tablas creadas exitosamente\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}