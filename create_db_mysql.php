<?php
// Crear base de datos y tablas en MySQL
$pdo = new PDO('mysql:host=localhost', 'root', '');

// Crear base de datos
$pdo->exec("CREATE DATABASE IF NOT EXISTS hotel_master");
$pdo->exec("USE hotel_master");

// Crear tabla usuarios
$pdo->exec("
    CREATE TABLE IF NOT EXISTS usuarios (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nombre VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        rol VARCHAR(50) DEFAULT 'staff',
        activo TINYINT DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

// Crear tabla habitaciones
$pdo->exec("
    CREATE TABLE IF NOT EXISTS habitaciones (
        id INT PRIMARY KEY AUTO_INCREMENT,
        numero_habitacion VARCHAR(10) UNIQUE NOT NULL,
        tipo VARCHAR(50) NOT NULL,
        capacidad INT NOT NULL,
        precio_noche DECIMAL(10,2) NOT NULL,
        estado VARCHAR(50) DEFAULT 'disponible',
        descripcion TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

// Crear tabla clientes
$pdo->exec("
    CREATE TABLE IF NOT EXISTS clientes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nombre VARCHAR(100) NOT NULL,
        documento VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100),
        telefono VARCHAR(20),
        ciudad VARCHAR(100),
        pais VARCHAR(100),
        direccion TEXT,
        fecha_nacimiento DATE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

// Crear tabla reservas
$pdo->exec("
    CREATE TABLE IF NOT EXISTS reservas (
        id INT PRIMARY KEY AUTO_INCREMENT,
        cliente_id INT NOT NULL,
        habitacion_id INT NOT NULL,
        fecha_entrada DATE NOT NULL,
        hora_entrada TIME DEFAULT '15:00:00',
        fecha_salida DATE NOT NULL,
        hora_salida TIME DEFAULT '12:00:00',
        numero_huespedes INT DEFAULT 1,
        precio_total DECIMAL(10,2) DEFAULT 0.00,
        total DECIMAL(10,2) DEFAULT 0.00,
        observaciones TEXT,
        notas TEXT,
        early_checkin TINYINT DEFAULT 0,
        late_checkout TINYINT DEFAULT 0,
        cargo_extra DECIMAL(10,2) DEFAULT 0.00,
        fecha_checkin DATETIME NULL,
        fecha_checkout DATETIME NULL,
        adelanto DECIMAL(10,2) DEFAULT 0.00,
        estado VARCHAR(50) DEFAULT 'reservada',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (cliente_id) REFERENCES clientes(id),
        FOREIGN KEY (habitacion_id) REFERENCES habitaciones(id)
    )
");

// Crear tabla pagos
$pdo->exec("
    CREATE TABLE IF NOT EXISTS pagos (
        id INT PRIMARY KEY AUTO_INCREMENT,
        reserva_id INT NOT NULL,
        monto DECIMAL(10,2) NOT NULL,
        metodo_pago VARCHAR(50) NOT NULL,
        fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP,
        comprobante VARCHAR(255),
        observaciones TEXT,
        usuario_id INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reserva_id) REFERENCES reservas(id),
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    )
");

// Crear tabla huespedes
$pdo->exec("
    CREATE TABLE IF NOT EXISTS huespedes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        reserva_id INT NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        documento VARCHAR(50),
        tipo_documento VARCHAR(20) DEFAULT 'DNI',
        fecha_nacimiento DATE NULL,
        nacionalidad VARCHAR(100) DEFAULT 'Peruana',
        email VARCHAR(100),
        telefono VARCHAR(20),
        es_titular TINYINT DEFAULT 0,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reserva_id) REFERENCES reservas(id)
    )
");

// Crear tabla servicios
$pdo->exec("
    CREATE TABLE IF NOT EXISTS servicios (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nombre VARCHAR(100) NOT NULL,
        descripcion TEXT,
        precio DECIMAL(10,2) NOT NULL,
        activo TINYINT DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

// Crear tabla reserva_servicios
$pdo->exec("
    CREATE TABLE IF NOT EXISTS reserva_servicios (
        id INT PRIMARY KEY AUTO_INCREMENT,
        reserva_id INT NOT NULL,
        servicio_id INT NOT NULL,
        cantidad INT DEFAULT 1,
        precio DECIMAL(10,2) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reserva_id) REFERENCES reservas(id),
        FOREIGN KEY (servicio_id) REFERENCES servicios(id)
    )
");

// Insertar usuario admin
$stmt = $pdo->prepare("INSERT IGNORE INTO usuarios (nombre, email, password, rol, activo) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([
    'Admin Hotel',
    'admin@hotel.com',
    password_hash('admin123', PASSWORD_BCRYPT),
    'admin',
    1
]);

echo "✅ Base de datos y tablas creadas exitosamente\n";
echo "✅ Usuario admin creado: admin@hotel.com / admin123\n";
?>
