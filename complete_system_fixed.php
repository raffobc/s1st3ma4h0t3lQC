<?php
/**
 * COMPLETAR SISTEMA - VERSIÓN CORREGIDA
 * Archivo: C:\xampp\htdocs\hotel-system\complete_system_fixed.php
 * Accede a: http://localhost/hotel-system/complete_system_fixed.php
 */

$baseDir = __DIR__;
$created = [];

// Contenido del archivo create_hotel.php
$createHotelContent = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Nuevo Hotel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f7fa; }
        .navbar { background: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-weight: 700; font-size: 20px; color: #667eea; }
        .btn-back { padding: 8px 16px; background: #6b7280; color: white; border: none; border-radius: 6px; text-decoration: none; font-weight: 600; }
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h1 { font-size: 28px; color: #1f2937; margin-bottom: 10px; }
        h2 { font-size: 20px; color: #374151; margin: 25px 0 15px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #374151; font-size: 14px; }
        .form-control { width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; }
        .form-control:focus { outline: none; border-color: #667eea; }
        select.form-control { 
            appearance: none; 
            background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'%236b7280\'%3E%3Cpath stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M19 9l-7 7-7-7\'%3E%3C/path%3E%3C/svg%3E"); 
            background-repeat: no-repeat; 
            background-position: right 10px center; 
            background-size: 20px; 
            padding-right: 40px; 
        }
        .btn-primary { width: 100%; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 10px; font-weight: 600; font-size: 16px; cursor: pointer; margin-top: 20px; }
        .btn-primary:hover { transform: translateY(-2px); }
        .info-box { background: #f0f9ff; border-left: 4px solid #3b82f6; padding: 15px; border-radius: 8px; margin: 20px 0; font-size: 14px; }
        small { color: #6b7280; font-size: 12px; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">👑 Super Admin - Crear Hotel</div>
        <a href="/super/dashboard" class="btn-back">← Volver</a>
    </nav>
    
    <div class="container">
        <div class="card">
            <h1>🏨 Crear Nuevo Hotel</h1>
            <p style="color: #6b7280; margin-bottom: 30px;">Complete el formulario para crear un nuevo hotel en el sistema</p>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <h2>📋 Información del Hotel</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nombre del Hotel *</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Hotel Paradise Lima" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Razón Social *</label>
                        <input type="text" name="razon_social" class="form-control" placeholder="Ej: Paradise Hotels SAC" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">RUC *</label>
                        <input type="text" name="ruc" class="form-control" placeholder="20123456789" required maxlength="11">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" name="telefono" class="form-control" placeholder="+51 999 888 777">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="contacto@hotel.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ciudad *</label>
                        <input type="text" name="ciudad" class="form-control" placeholder="Lima" required>
                    </div>
                </div>
                <div class="form-group full">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="direccion" class="form-control" placeholder="Av. Principal 123, Distrito">
                </div>
                
                <h2>⚙️ Configuración del Sistema</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Plan *</label>
                        <select name="plan" class="form-control" required>
                            <option value="basico">Básico - 50 habitaciones</option>
                            <option value="profesional">Profesional - 100 habitaciones</option>
                            <option value="empresarial">Empresarial - Ilimitado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Máximo de Habitaciones</label>
                        <input type="number" name="max_habitaciones" class="form-control" value="50" min="1">
                    </div>
                </div>
                
                <div class="info-box">
                    <strong>ℹ️ Importante:</strong> Se creará automáticamente una base de datos individual para este hotel.
                </div>
                
                <h2>👤 Administrador del Hotel</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nombre Completo *</label>
                        <input type="text" name="admin_nombre" class="form-control" placeholder="Juan Pérez García" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="admin_email" class="form-control" placeholder="admin@hotel.com" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" name="admin_telefono" class="form-control" placeholder="+51 999 888 777">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contraseña *</label>
                        <input type="password" name="admin_password" class="form-control" value="Admin123!" required>
                        <small>Por defecto: Admin123!</small>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">✓ Crear Hotel y Base de Datos</button>
            </form>
        </div>
    </div>
</body>
</html>';

$files = [
    // models/Hotel.php
    'models/Hotel.php' => '<?php
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
        
        $this->db->beginTransaction();
        
        try {
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
            $this->db->rollBack();
            error_log("Error creando hotel: " . $e->getMessage());
            return false;
        }
    }
    
    private function createHotelDatabase(string $dbName): bool {
        try {
            $this->db->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            $hotelDb = new PDO("mysql:host=localhost;dbname=$dbName", "root", "");
            $hotelDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $schema = "
            CREATE TABLE habitaciones (
                id INT AUTO_INCREMENT PRIMARY KEY,
                numero_habitacion VARCHAR(10) NOT NULL UNIQUE,
                tipo ENUM(\"simple\", \"doble\", \"matrimonial\", \"suite\") NOT NULL,
                estado ENUM(\"disponible\", \"ocupada\", \"reservada\", \"limpieza\", \"inhabilitada\") DEFAULT \"disponible\",
                precio_noche DECIMAL(10, 2) NOT NULL,
                capacidad INT DEFAULT 2,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;

            CREATE TABLE clientes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(255) NOT NULL,
                documento VARCHAR(20) NOT NULL UNIQUE,
                telefono VARCHAR(20),
                email VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;

            CREATE TABLE reservas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                habitacion_id INT NOT NULL,
                cliente_id INT NOT NULL,
                fecha_entrada DATE NOT NULL,
                fecha_salida DATE NOT NULL,
                estado ENUM(\"reservada\", \"ocupada\", \"finalizada\", \"cancelada\") DEFAULT \"reservada\",
                total DECIMAL(10, 2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (habitacion_id) REFERENCES habitaciones(id),
                FOREIGN KEY (cliente_id) REFERENCES clientes(id)
            ) ENGINE=InnoDB;

            CREATE TABLE usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                rol ENUM(\"admin\", \"recepcionista\") DEFAULT \"recepcionista\",
                activo BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;
            ";
            
            foreach (explode(";", $schema) as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    $hotelDb->exec($statement);
                }
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error creando BD del hotel: " . $e->getMessage());
            return false;
        }
    }
    
    private function createHotelAdmin(int $hotelId, array $data): bool {
        $sql = "INSERT INTO hotel_administradores (hotel_id, nombre, email, password, telefono)
                VALUES (?, ?, ?, ?, ?)";
        
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
}
?>',

    // controllers/SuperAdminController.php
    'controllers/SuperAdminController.php' => '<?php
class SuperAdminController {
    private SuperUser $superUserModel;
    private Hotel $hotelModel;
    
    public function __construct() {
        $this->superUserModel = new SuperUser();
        $this->hotelModel = new Hotel();
    }
    
    private function checkAuth(): void {
        if (!isset($_SESSION["super_user_id"])) {
            header("Location: /super/login");
            exit;
        }
    }
    
    public function login(): void {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $email = $_POST["email"] ?? "";
            $password = $_POST["password"] ?? "";
            
            $user = $this->superUserModel->authenticate($email, $password);
            
            if ($user) {
                $_SESSION["super_user_id"] = $user["id"];
                $_SESSION["super_user_name"] = $user["nombre"];
                $_SESSION["super_user_email"] = $user["email"];
                
                header("Location: /super/dashboard");
                exit;
            } else {
                $error = "Credenciales inválidas";
                require_once BASE_PATH . "/views/super/login.php";
                return;
            }
        }
        
        require_once BASE_PATH . "/views/super/login.php";
    }
    
    public function dashboard(): void {
        $this->checkAuth();
        $hotels = $this->hotelModel->getAllHotels();
        
        if (isset($_SESSION["success_message"])) {
            $success = $_SESSION["success_message"];
            unset($_SESSION["success_message"]);
        }
        
        require_once BASE_PATH . "/views/super/dashboard.php";
    }
    
    public function createHotel(): void {
        $this->checkAuth();
        
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $data = [
                "nombre" => $_POST["nombre"],
                "razon_social" => $_POST["razon_social"],
                "ruc" => $_POST["ruc"],
                "direccion" => $_POST["direccion"] ?? null,
                "telefono" => $_POST["telefono"] ?? null,
                "email" => $_POST["email"] ?? null,
                "ciudad" => $_POST["ciudad"] ?? null,
                "plan" => $_POST["plan"] ?? "basico",
                "max_habitaciones" => $_POST["max_habitaciones"] ?? 50,
                "admin_nombre" => $_POST["admin_nombre"],
                "admin_email" => $_POST["admin_email"],
                "admin_password" => $_POST["admin_password"],
                "admin_telefono" => $_POST["admin_telefono"] ?? null
            ];
            
            $hotelId = $this->hotelModel->createHotel($data);
            
            if ($hotelId) {
                $_SESSION["success_message"] = "Hotel creado exitosamente";
                header("Location: /super/dashboard");
                exit;
            } else {
                $error = "Error al crear el hotel";
            }
        }
        
        require_once BASE_PATH . "/views/super/create_hotel.php";
    }
    
    public function logout(): void {
        session_destroy();
        header("Location: /super/login");
        exit;
    }
}
?>',

    // public/index.php
    'public/index.php' => '<?php
session_start();
require_once "../config/config.php";
require_once "../config/MasterDatabase.php";

$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$uri = str_replace("/hotel-system/public", "", $uri);
$uri = explode("/", trim($uri, "/"));
$uri = array_values(array_filter($uri));

$accessType = $uri[0] ?? "";

if ($accessType === "super") {
    require_once "../models/Hotel.php";
    require_once "../models/SuperUser.php";
    require_once "../controllers/SuperAdminController.php";
    
    $controller = new SuperAdminController();
    $action = $uri[1] ?? "login";
    
    if ($action === "login") {
        $controller->login();
    } elseif ($action === "dashboard") {
        $controller->dashboard();
    } elseif ($action === "hotels" && isset($uri[2]) && $uri[2] === "create") {
        $controller->createHotel();
    } elseif ($action === "logout") {
        $controller->logout();
    } else {
        http_response_code(404);
        echo "Página no encontrada";
    }
} else {
    echo "<h1>Bienvenido al Sistema Hotel</h1>";
    echo "<p><a href=\"/super/login\">Ir a Super Admin</a></p>";
}
?>',

    // views/super/create_hotel.php
    'views/super/create_hotel.php' => $createHotelContent,
];

// Crear/Actualizar archivos
foreach ($files as $path => $content) {
    $fullPath = $baseDir . '/' . $path;
    $dir = dirname($fullPath);
    
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    if (file_put_contents($fullPath, $content)) {
        $created[] = $path;
    }
}

// Actualizar dashboard para agregar el botón funcional
$dashboardPath = $baseDir . '/views/super/dashboard.php';
if (file_exists($dashboardPath)) {
    $dashboardContent = file_get_contents($dashboardPath);
    $dashboardContent = str_replace(
        '<a href="#" class="btn-primary">➕ Crear Nuevo Hotel</a>',
        '<a href="/super/hotels/create" class="btn-primary">➕ Crear Nuevo Hotel</a>',
        $dashboardContent
    );
    
    // Agregar mensaje de éxito
    $dashboardContent = str_replace(
        '<div class="header">',
        '<?php if (isset($success)): ?>
            <div style="background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                ✅ <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        <div class="header">',
        $dashboardContent
    );
    
    file_put_contents($dashboardPath, $dashboardContent);
    $created[] = 'views/super/dashboard.php (actualizado)';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>✅ Sistema Completado</title>
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
            max-width: 700px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { color: #10b981; font-size: 32px; margin-bottom: 20px; }
        .success-box {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            line-height: 1.8;
        }
        .file-list {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            max-height: 300px;
            overflow-y: auto;
        }
        .file-item {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            margin-top: 20px;
        }
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 14px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ ¡Sistema Completado!</h1>
        
        <div class="success-box">
            <strong>🎉 ¡Todas las funcionalidades están listas!</strong><br><br>
            Ahora puedes:<br>
            ✓ Crear hoteles nuevos<br>
            ✓ Ver lista de hoteles<br>
            ✓ Gestionar hoteles<br>
            ✓ Cada hotel tendrá su propia base de datos automáticamente
        </div>
        
        <h3>Archivos creados/actualizados:</h3>
        <div class="file-list">
            <?php foreach ($created as $file): ?>
                <div class="file-item">✓ <?= htmlspecialchars($file) ?></div>
            <?php endforeach; ?>
        </div>
        
        <div class="info-box">
            <strong>📚 Cómo crear un hotel:</strong><br><br>
            1. Ve al Dashboard (botón de abajo)<br>
            2. Click en "➕ Crear Nuevo Hotel"<br>
            3. Llena el formulario<br>
            4. El sistema creará:<br>
            &nbsp;&nbsp;&nbsp;• Base de datos del hotel<br>
            &nbsp;&nbsp;&nbsp;• Tablas (habitaciones, reservas, clientes, etc.)<br>
            &nbsp;&nbsp;&nbsp;• Usuario administrador del hotel<br>
            5. ¡Listo!
        </div>
        
        <a href="public/super/dashboard" class="btn">
            🚀 Ir al Dashboard y Crear Primer Hotel
        </a>
        
        <p style="margin-top: 20px; color: #6b7280; font-size: 14px; text-align: center;">
            <strong>💡 Tip:</strong> Puedes eliminar los archivos de instalación por seguridad
        </p>
    </div>
</body>
</html>
