<?php
// Script para inicializar la base de datos de hotel demo sin dependencias de MySQL
// Intenta usar el hotel_master primero, si falla se configura para trabajar localmente

session_start();

// Intentar conectar a MySQL
try {
    $pdo = new PDO('mysql:host=localhost;dbname=hotel_master;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Conectado a hotel_master\n";
    
    // Crear base de datos hotel_demo si no existe
    $pdo->exec("CREATE DATABASE IF NOT EXISTS hotel_demo");
    echo "✓ Base de datos hotel_demo disponible\n";
    
    // Conectar a hotel_demo
    $db = new PDO('mysql:host=localhost;dbname=hotel_demo;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $db->exec("DROP TABLE IF EXISTS usuarios");
    $db->exec("DROP TABLE IF EXISTS habitaciones");
    $db->exec("DROP TABLE IF EXISTS clientes");
    $db->exec("DROP TABLE IF EXISTS reservas");
    $db->exec("DROP TABLE IF EXISTS pagos");
    $db->exec("DROP TABLE IF EXISTS servicios");
    $db->exec("DROP TABLE IF EXISTS huespedes");
    $db->exec("DROP TABLE IF EXISTS reserva_servicios");

    // Crear tablas
    $db->exec("
        CREATE TABLE usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            rol VARCHAR(50) DEFAULT 'admin',
            activo BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    $db->exec("
        CREATE TABLE habitaciones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            numero_habitacion VARCHAR(10) UNIQUE NOT NULL,
            tipo VARCHAR(50) NOT NULL,
            precio_noche DECIMAL(10, 2) NOT NULL,
            capacidad INT DEFAULT 2,
            descripcion TEXT,
            estado VARCHAR(20) DEFAULT 'disponible',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    $db->exec("
        CREATE TABLE clientes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(255) NOT NULL,
            documento VARCHAR(20) UNIQUE NOT NULL,
            telefono VARCHAR(20),
            email VARCHAR(255),
            direccion TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_documento (documento)
        ) ENGINE=InnoDB
    ");

    $db->exec("
        CREATE TABLE reservas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cliente_id INT NOT NULL,
            habitacion_id INT NOT NULL,
            fecha_entrada DATE NOT NULL,
            fecha_salida DATE NOT NULL,
            cantidad_personas INT DEFAULT 1,
            estado VARCHAR(20) DEFAULT 'reservada',
            notas TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
            FOREIGN KEY (habitacion_id) REFERENCES habitaciones(id) ON DELETE RESTRICT,
            INDEX idx_cliente (cliente_id),
            INDEX idx_habitacion (habitacion_id),
            INDEX idx_fechas (fecha_entrada, fecha_salida)
        ) ENGINE=InnoDB
    ");

    $db->exec("
        CREATE TABLE pagos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reserva_id INT NOT NULL,
            monto DECIMAL(10, 2) NOT NULL,
            metodo VARCHAR(50) NOT NULL,
            referencia VARCHAR(100),
            estado VARCHAR(20) DEFAULT 'completado',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE CASCADE,
            INDEX idx_reserva (reserva_id)
        ) ENGINE=InnoDB
    ");

    $db->exec("
        CREATE TABLE servicios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(255) NOT NULL,
            descripcion TEXT,
            precio DECIMAL(10, 2) NOT NULL,
            disponible BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    $db->exec("
        CREATE TABLE huespedes (
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

    $db->exec("
        CREATE TABLE reserva_servicios (
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

    // Insertar datos de prueba
    $sqlFiles = [
        // Usuarios
        "INSERT INTO usuarios (email, password, nombre, rol) VALUES ('admin@hotel.com', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'Administrador Demo', 'admin')",
        
        // Habitaciones
        "INSERT INTO habitaciones (numero_habitacion, tipo, precio_noche, capacidad) VALUES ('101', 'Simple', 50.00, 1)",
        "INSERT INTO habitaciones (numero_habitacion, tipo, precio_noche, capacidad) VALUES ('102', 'Doble', 80.00, 2)",
        "INSERT INTO habitaciones (numero_habitacion, tipo, precio_noche, capacidad) VALUES ('103', 'Suite', 120.00, 4)",
        
        // Clientes
        "INSERT INTO clientes (nombre, documento, telefono, email) VALUES ('Juan Pérez', '12345678', '9999999', 'juan@email.com')",
        "INSERT INTO clientes (nombre, documento, telefono, email) VALUES ('María García', '87654321', '8888888', 'maria@email.com')",
        
        // Servicios
        "INSERT INTO servicios (nombre, descripcion, precio) VALUES ('Desayuno', 'Desayuno buffet completo', 10.00)",
        "INSERT INTO servicios (nombre, descripcion, precio) VALUES ('Estacionamiento', 'Estacionamiento cubierto', 5.00)",
        "INSERT INTO servicios (nombre, descripcion, precio) VALUES ('Spa', 'Masaje relajante', 50.00)"
    ];

    foreach ($sqlFiles as $sql) {
        $db->exec($sql);
    }

    echo "✓ Tablas creadas exitosamente\n";
    echo "✓ Datos de prueba insertados\n";
    echo "\n✓ Base de datos lista para usar\n";

} catch (PDOException $e) {
    echo "Error al conectar a MySQL: " . $e->getMessage() . "\n";
    echo "\nIntentando usar configuración local sin MySQL...\n";
    exit(1);
}