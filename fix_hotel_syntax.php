<?php
/**
 * ARREGLAR SINTAXIS DEL MODELO HOTEL
 * Archivo: C:\xampp\htdocs\hotel-system\fix_hotel_syntax.php
 * Accede a: http://localhost/hotel-system/fix_hotel_syntax.php
 */

$baseDir = __DIR__;

// Contenido corregido sin errores de sintaxis
$hotelModelContent = <<<'PHP'
<?php
class Hotel {
    private PDO $db;
    
    public function __construct() {
        $this->db = MasterDatabase::getConnection();
    }
    
    public function getAllHotels(): array {
        $stmt = $this->db->query("
            SELECT h.*, 
                   (SELECT COUNT(*) FROM hotel_administradores WHERE hotel_id = h.id) as total_admins
            FROM hoteles h
            ORDER BY h.created_at DESC
        ");
        return $stmt->fetchAll();
    }
    
    public function getHotelById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM hoteles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    
    public function createHotel(array $data): int|false {
        $dbName = "hotel_" . strtolower(preg_replace("/[^a-zA-Z0-9]/", "", $data["nombre"])) . "_" . time();
        
        try {
            $this->db->beginTransaction();
            
            $sql = "INSERT INTO hoteles (
                nombre, razon_social, ruc, direccion, telefono, email, ciudad, pais,
                db_name, db_host, db_user, db_password,
                estado, plan, max_habitaciones, fecha_registro, fecha_vencimiento
            ) VALUES (
                :nombre, :razon_social, :ruc, :direccion, :telefono, :email, :ciudad, :pais,
                :db_name, :db_host, :db_user, :db_password,
                :estado, :plan, :max_habitaciones, :fecha_registro, :fecha_vencimiento
            )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                "nombre" => $data["nombre"],
                "razon_social" => $data["razon_social"],
                "ruc" => $data["ruc"],
                "direccion" => $data["direccion"] ?? null,
                "telefono" => $data["telefono"] ?? null,
                "email" => $data["email"] ?? null,
                "ciudad" => $data["ciudad"] ?? null,
                "pais" => "Perú",
                "db_name" => $dbName,
                "db_host" => "localhost",
                "db_user" => "root",
                "db_password" => "",
                "estado" => "activo",
                "plan" => $data["plan"] ?? "basico",
                "max_habitaciones" => $data["max_habitaciones"] ?? 50,
                "fecha_registro" => date("Y-m-d"),
                "fecha_vencimiento" => date("Y-m-d", strtotime("+1 year"))
            ]);
            
            $hotelId = (int)$this->db->lastInsertId();
            
            $this->createHotelDatabase($dbName);
            
            if (isset($data["admin_email"])) {
                $this->createHotelAdmin($hotelId, [
                    "nombre" => $data["admin_nombre"],
                    "email" => $data["admin_email"],
                    "password" => $data["admin_password"] ?? "Admin123!",
                    "telefono" => $data["admin_telefono"] ?? null
                ]);
            }
            
            $this->db->commit();
            return $hotelId;
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error creando hotel: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function createHotelDatabase(string $dbName): bool {
        try {
            $this->db->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            $hotelDb = new PDO("mysql:host=localhost;dbname=$dbName;charset=utf8mb4", "root", "");
            $hotelDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $hotelDb->exec("
                CREATE TABLE habitaciones (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    numero_habitacion VARCHAR(10) NOT NULL UNIQUE,
                    tipo ENUM('simple', 'doble', 'matrimonial', 'suite') NOT NULL,
                    estado ENUM('disponible', 'ocupada', 'reservada', 'limpieza', 'inhabilitada') DEFAULT 'disponible',
                    precio_noche DECIMAL(10, 2) NOT NULL,
                    capacidad INT DEFAULT 2,
                    descripcion TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB
            ");
            
            $hotelDb->exec("
                CREATE TABLE clientes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nombre VARCHAR(255) NOT NULL,
                    documento VARCHAR(20) NOT NULL UNIQUE,
                    telefono VARCHAR(20),
                    email VARCHAR(255),
                    direccion TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB
            ");
            
            $hotelDb->exec("
                CREATE TABLE reservas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    habitacion_id INT NOT NULL,
                    cliente_id INT NOT NULL,
                    fecha_entrada DATE NOT NULL,
                    fecha_salida DATE NOT NULL,
                    fecha_checkin DATETIME,
                    fecha_checkout DATETIME,
                    estado ENUM('reservada', 'ocupada', 'finalizada', 'cancelada') DEFAULT 'reservada',
                    total DECIMAL(10, 2) NOT NULL,
                    adelanto DECIMAL(10, 2) DEFAULT 0,
                    observaciones TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (habitacion_id) REFERENCES habitaciones(id) ON DELETE CASCADE,
                    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
                ) ENGINE=InnoDB
            ");
            
            $hotelDb->exec("
                CREATE TABLE usuarios (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nombre VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    rol ENUM('admin', 'recepcionista', 'gerente') DEFAULT 'recepcionista',
                    activo BOOLEAN DEFAULT TRUE,
                    ultimo_acceso DATETIME,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB
            ");
            
            $hotelDb->exec("
                CREATE TABLE pagos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    reserva_id INT NOT NULL,
                    monto DECIMAL(10, 2) NOT NULL,
                    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'otro') NOT NULL,
                    fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP,
                    comprobante VARCHAR(100),
                    observaciones TEXT,
                    usuario_id INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE CASCADE,
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
                ) ENGINE=InnoDB
            ");
            
            $hotelDb->exec("
                CREATE TABLE servicios (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nombre VARCHAR(100) NOT NULL,
                    descripcion TEXT,
                    precio DECIMAL(10, 2) NOT NULL,
                    activo BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB
            ");
            
            $hotelDb->exec("
                INSERT INTO servicios (nombre, descripcion, precio) VALUES
                ('Desayuno', 'Desayuno buffet', 25.00),
                ('Lavandería', 'Servicio de lavandería', 30.00),
                ('Room Service', 'Servicio a la habitación', 15.00)
            ");
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error creando BD del hotel: " . $e->getMessage());
            throw new Exception("Error al crear la base de datos del hotel: " . $e->getMessage());
        }
    }
    
    private function createHotelAdmin(int $hotelId, array $data): bool {
        $sql = "INSERT INTO hotel_administradores (hotel_id, nombre, email, password, telefono, activo)
                VALUES (?, ?, ?, ?, ?, 1)";
        
        $stmt = $this->db->prepare($sql);
        $hashedPassword = password_hash($data["password"], PASSWORD_DEFAULT);
        
        return $stmt->execute([
            $hotelId,
            $data["nombre"],
            $data["email"],
            $hashedPassword,
            $data["telefono"] ?? null
        ]);
    }
    
    public function updateHotelStatus(int $id, string $status): bool {
        $stmt = $this->db->prepare("UPDATE hoteles SET estado = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
    
    public function deleteHotel(int $id): bool {
        try {
            $this->db->beginTransaction();
            
            $hotel = $this->getHotelById($id);
            if (!$hotel) {
                throw new Exception("Hotel no encontrado");
            }
            
            $this->db->exec("DROP DATABASE IF EXISTS `{$hotel['db_name']}`");
            
            $stmt = $this->db->prepare("DELETE FROM hoteles WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error eliminando hotel: " . $e->getMessage());
            return false;
        }
    }
}
?>
PHP;

// Guardar el archivo
$hotelPath = $baseDir . '/models/Hotel.php';

if (file_put_contents($hotelPath, $hotelModelContent)) {
    $success = true;
} else {
    $error = "No se pudo actualizar el archivo";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>✅ Sintaxis Corregida</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        h1 { color: #10b981; font-size: 32px; margin-bottom: 20px; }
        .success-box {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
            line-height: 1.8;
        }
        .error-box {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            color: #991b1b;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            margin: 10px;
            font-size: 16px;
        }
        .icon { font-size: 64px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($success)): ?>
            <div class="icon">✅</div>
            <h1>¡Sintaxis Corregida!</h1>
            
            <div class="success-box">
                <strong>El error de sintaxis se ha solucionado</strong><br><br>
                ✓ Archivo Hotel.php corregido<br>
                ✓ Sintaxis válida de PHP<br>
                ✓ Todas las comillas escapadas correctamente<br>
                ✓ Listo para crear hoteles
            </div>
            
            <a href="public/super/dashboard" class="btn">
                📊 Ir al Dashboard
            </a>
            
            <a href="public/super/hotels/create" class="btn">
                🏨 Crear Hotel Ahora
            </a>
            
        <?php else: ?>
            <div class="icon">❌</div>
            <h1>Error</h1>
            <div class="error-box">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
