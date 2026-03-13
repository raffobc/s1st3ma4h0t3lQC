<?php
// Script para crear la base de datos en MySQL
echo "Creando base de datos en MySQL...\n";

try {
    // Conectar a MySQL sin especificar base de datos
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "✓ Conectado a MySQL\n";

    // Crear base de datos hotel_demo
    $pdo->exec("DROP DATABASE IF EXISTS hotel_demo");
    $pdo->exec("CREATE DATABASE hotel_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "✓ Base de datos hotel_demo creada\n";

    // Cambiar a la base de datos
    $pdo->exec("USE hotel_demo");

    // Crear tabla usuarios
    $pdo->exec("
        CREATE TABLE usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            rol VARCHAR(50) DEFAULT 'admin',
            activo BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✓ Tabla usuarios creada\n";

    // Crear tabla habitaciones
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✓ Tabla habitaciones creada\n";

    // Crear tabla clientes
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✓ Tabla clientes creada\n";

    // Crear tabla reservas
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✓ Tabla reservas creada\n";

    // Crear tabla pagos
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✓ Tabla pagos creada\n";

    // Crear tabla servicios
    $pdo->exec("
        CREATE TABLE servicios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(255) NOT NULL,
            descripcion TEXT,
            precio DECIMAL(10, 2) NOT NULL,
            disponible BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✓ Tabla servicios creada\n";

    // Crear tabla huespedes
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✓ Tabla huespedes creada\n";

    // Crear tabla reserva_servicios
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✓ Tabla reserva_servicios creada\n";

    // Insertar datos de prueba
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    
    $pdo->exec("INSERT INTO usuarios (email, password, nombre, rol) VALUES ('admin@hotel.com', '$password', 'Administrador Demo', 'admin')");
    echo "✓ Usuario admin creado\n";

    $pdo->exec("INSERT INTO habitaciones (numero_habitacion, tipo, precio_noche, capacidad) VALUES ('101', 'Simple', 50.00, 1)");
    $pdo->exec("INSERT INTO habitaciones (numero_habitacion, tipo, precio_noche, capacidad) VALUES ('102', 'Doble', 80.00, 2)");
    $pdo->exec("INSERT INTO habitaciones (numero_habitacion, tipo, precio_noche, capacidad) VALUES ('103', 'Suite', 120.00, 4)");
    echo "✓ Habitaciones creadas\n";

    $pdo->exec("INSERT INTO clientes (nombre, documento, telefono, email) VALUES ('Juan Pérez', '12345678', '9999999', 'juan@email.com')");
    $pdo->exec("INSERT INTO clientes (nombre, documento, telefono, email) VALUES ('María García', '87654321', '8888888', 'maria@email.com')");
    echo "✓ Clientes creados\n";

    $pdo->exec("INSERT INTO servicios (nombre, descripcion, precio) VALUES ('Desayuno', 'Desayuno buffet completo', 10.00)");
    $pdo->exec("INSERT INTO servicios (nombre, descripcion, precio) VALUES ('Estacionamiento', 'Estacionamiento cubierto', 5.00)");
    $pdo->exec("INSERT INTO servicios (nombre, descripcion, precio) VALUES ('Spa', 'Masaje relajante', 50.00)");
    echo "✓ Servicios creados\n";

    echo "\n✓✓✓ Base de datos MySQL configurada exitosamente ✓✓✓\n";

} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}