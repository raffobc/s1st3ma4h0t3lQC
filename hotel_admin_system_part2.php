<?php
/**
 * CONTINUACIÓN: SISTEMA DE LOGIN Y DASHBOARD PARA ADMINISTRADORES DE HOTEL
 * Archivo: C:\xampp\htdocs\hotel-system\complete_hotel_system.php
 * Ejecuta este archivo después del anterior para completar el sistema
 */

$baseDir = __DIR__;
$created = [];

$additionalFiles = [
    // controllers/HotelRoomsController.php
    'controllers/HotelRoomsController.php' => '<?php
class HotelRoomsController {
    private PDO $hotelDb;
    
    public function __construct() {
        $this->checkAuth();
        $this->hotelDb = $this->getHotelConnection();
    }
    
    private function checkAuth(): void {
        if (!isset($_SESSION["hotel_user_id"])) {
            header("Location: /hotel/login");
            exit;
        }
    }
    
    private function getHotelConnection(): PDO {
        $dbName = $_SESSION["hotel_db_name"];
        $credentials = $_SESSION["hotel_db_credentials"];
        
        return new PDO(
            "mysql:host={$credentials[\"host\"]};dbname=$dbName;charset=utf8mb4",
            $credentials["user"],
            $credentials["password"],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }
    
    public function index(): void {
        $stmt = $this->hotelDb->query("
            SELECT h.*, 
                   (SELECT COUNT(*) FROM reservas r 
                    WHERE r.habitacion_id = h.id 
                    AND r.estado IN (\"reservada\", \"ocupada\")) as reservas_activas
            FROM habitaciones h
            ORDER BY h.numero_habitacion
        ");
        
        $rooms = $stmt->fetchAll();
        require_once BASE_PATH . "/views/hotel/rooms.php";
    }
    
    public function create(): void {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $numero = $_POST["numero_habitacion"];
            $tipo = $_POST["tipo"];
            $precio = $_POST["precio_noche"];
            $capacidad = $_POST["capacidad"];
            $descripcion = $_POST["descripcion"] ?? "";
            
            $stmt = $this->hotelDb->prepare("
                INSERT INTO habitaciones (numero_habitacion, tipo, precio_noche, capacidad, descripcion, estado)
                VALUES (?, ?, ?, ?, ?, \"disponible\")
            ");
            
            $stmt->execute([$numero, $tipo, $precio, $capacidad, $descripcion]);
            
            header("Location: /hotel/habitaciones");
            exit;
        }
        
        require_once BASE_PATH . "/views/hotel/room_form.php";
    }
    
    public function edit(): void {
        $id = $_GET["id"] ?? 0;
        
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $stmt = $this->hotelDb->prepare("
                UPDATE habitaciones 
                SET numero_habitacion = ?, tipo = ?, precio_noche = ?, 
                    capacidad = ?, descripcion = ?, estado = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $_POST["numero_habitacion"],
                $_POST["tipo"],
                $_POST["precio_noche"],
                $_POST["capacidad"],
                $_POST["descripcion"] ?? "",
                $_POST["estado"],
                $id
            ]);
            
            header("Location: /hotel/habitaciones");
            exit;
        }
        
        $stmt = $this->hotelDb->prepare("SELECT * FROM habitaciones WHERE id = ?");
        $stmt->execute([$id]);
        $room = $stmt->fetch();
        
        if (!$room) {
            header("Location: /hotel/habitaciones");
            exit;
        }
        
        require_once BASE_PATH . "/views/hotel/room_form.php";
    }
}
?>',

    // controllers/HotelReservationsController.php
    'controllers/HotelReservationsController.php' => '<?php
class HotelReservationsController {
    private PDO $hotelDb;
    
    public function __construct() {
        $this->checkAuth();
        $this->hotelDb = $this->getHotelConnection();
    }
    
    private function checkAuth(): void {
        if (!isset($_SESSION["hotel_user_id"])) {
            header("Location: /hotel/login");
            exit;
        }
    }
    
    private function getHotelConnection(): PDO {
        $dbName = $_SESSION["hotel_db_name"];
        $credentials = $_SESSION["hotel_db_credentials"];
        
        return new PDO(
            "mysql:host={$credentials[\"host\"]};dbname=$dbName;charset=utf8mb4",
            $credentials["user"],
            $credentials["password"],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }
    
    public function index(): void {
        $filter = $_GET["filter"] ?? "all";
        
        $sql = "
            SELECT r.*, 
                   h.numero_habitacion, h.tipo as tipo_habitacion,
                   c.nombre as cliente_nombre, c.documento as cliente_documento,
                   c.email as cliente_email, c.telefono as cliente_telefono
            FROM reservas r
            JOIN habitaciones h ON r.habitacion_id = h.id
            JOIN clientes c ON r.cliente_id = c.id
        ";
        
        if ($filter === "active") {
            $sql .= " WHERE r.estado IN (\"reservada\", \"ocupada\")";
        } elseif ($filter === "finished") {
            $sql .= " WHERE r.estado = \"finalizada\"";
        } elseif ($filter === "cancelled") {
            $sql .= " WHERE r.estado = \"cancelada\"";
        }
        
        $sql .= " ORDER BY r.fecha_entrada DESC";
        
        $reservations = $this->hotelDb->query($sql)->fetchAll();
        require_once BASE_PATH . "/views/hotel/reservations.php";
    }
    
    public function create(): void {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $clienteId = $_POST["cliente_id"];
            $habitacionId = $_POST["habitacion_id"];
            $fechaEntrada = $_POST["fecha_entrada"];
            $fechaSalida = $_POST["fecha_salida"];
            $numHuespedes = $_POST["numero_huespedes"];
            $observaciones = $_POST["observaciones"] ?? "";
            
            // Calcular días y total
            $entrada = new DateTime($fechaEntrada);
            $salida = new DateTime($fechaSalida);
            $dias = $entrada->diff($salida)->days;
            
            // Obtener precio de la habitación
            $stmt = $this->hotelDb->prepare("SELECT precio_noche FROM habitaciones WHERE id = ?");
            $stmt->execute([$habitacionId]);
            $precioNoche = $stmt->fetch()["precio_noche"];
            
            $total = $dias * $precioNoche;
            
            // Crear reserva
            $stmt = $this->hotelDb->prepare("
                INSERT INTO reservas (cliente_id, habitacion_id, fecha_entrada, fecha_salida, 
                                     numero_huespedes, precio_total, observaciones, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, \"reservada\")
            ");
            
            $stmt->execute([
                $clienteId, $habitacionId, $fechaEntrada, $fechaSalida,
                $numHuespedes, $total, $observaciones
            ]);
            
            // Actualizar estado de habitación
            $stmt = $this->hotelDb->prepare("UPDATE habitaciones SET estado = \"reservada\" WHERE id = ?");
            $stmt->execute([$habitacionId]);
            
            header("Location: /hotel/reservas");
            exit;
        }
        
        // Obtener clientes y habitaciones disponibles
        $clientes = $this->hotelDb->query("SELECT * FROM clientes ORDER BY nombre")->fetchAll();
        $habitaciones = $this->hotelDb->query("
            SELECT * FROM habitaciones 
            WHERE estado IN (\"disponible\", \"limpieza\") 
            ORDER BY numero_habitacion
        ")->fetchAll();
        
        require_once BASE_PATH . "/views/hotel/reservation_form.php";
    }
    
    public function updateStatus(): void {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $id = $_POST["reservation_id"];
            $nuevoEstado = $_POST["nuevo_estado"];
            
            $stmt = $this->hotelDb->prepare("UPDATE reservas SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevoEstado, $id]);
            
            // Actualizar estado de habitación según el nuevo estado
            $stmt = $this->hotelDb->prepare("SELECT habitacion_id FROM reservas WHERE id = ?");
            $stmt->execute([$id]);
            $habitacionId = $stmt->fetch()["habitacion_id"];
            
            $estadoHabitacion = "disponible";
            if ($nuevoEstado === "ocupada") {
                $estadoHabitacion = "ocupada";
            } elseif ($nuevoEstado === "reservada") {
                $estadoHabitacion = "reservada";
            } elseif ($nuevoEstado === "finalizada") {
                $estadoHabitacion = "limpieza";
            }
            
            $stmt = $this->hotelDb->prepare("UPDATE habitaciones SET estado = ? WHERE id = ?");
            $stmt->execute([$estadoHabitacion, $habitacionId]);
            
            header("Location: /hotel/reservas");
            exit;
        }
    }
}
?>',

    // controllers/HotelClientsController.php
    'controllers/HotelClientsController.php' => '<?php
class HotelClientsController {
    private PDO $hotelDb;
    
    public function __construct() {
        $this->checkAuth();
        $this->hotelDb = $this->getHotelConnection();
    }
    
    private function checkAuth(): void {
        if (!isset($_SESSION["hotel_user_id"])) {
            header("Location: /hotel/login");
            exit;
        }
    }
    
    private function getHotelConnection(): PDO {
        $dbName = $_SESSION["hotel_db_name"];
        $credentials = $_SESSION["hotel_db_credentials"];
        
        return new PDO(
            "mysql:host={$credentials[\"host\"]};dbname=$dbName;charset=utf8mb4",
            $credentials["user"],
            $credentials["password"],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }
    
    public function index(): void {
        $search = $_GET["search"] ?? "";
        
        $sql = "
            SELECT c.*, 
                   COUNT(DISTINCT r.id) as total_reservas,
                   COALESCE(SUM(p.monto), 0) as total_gastado
            FROM clientes c
            LEFT JOIN reservas r ON c.id = r.cliente_id
            LEFT JOIN pagos p ON r.id = p.reserva_id
        ";
        
        if ($search) {
            $sql .= " WHERE c.nombre LIKE ? OR c.documento LIKE ? OR c.email LIKE ?";
        }
        
        $sql .= " GROUP BY c.id ORDER BY c.created_at DESC";
        
        if ($search) {
            $stmt = $this->hotelDb->prepare($sql);
            $searchParam = "%$search%";
            $stmt->execute([$searchParam, $searchParam, $searchParam]);
            $clients = $stmt->fetchAll();
        } else {
            $clients = $this->hotelDb->query($sql)->fetchAll();
        }
        
        require_once BASE_PATH . "/views/hotel/clients.php";
    }
    
    public function create(): void {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $stmt = $this->hotelDb->prepare("
                INSERT INTO clientes (nombre, documento, email, telefono, direccion, fecha_nacimiento)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_POST["nombre"],
                $_POST["documento"],
                $_POST["email"],
                $_POST["telefono"],
                $_POST["direccion"] ?? "",
                $_POST["fecha_nacimiento"] ?? null
            ]);
            
            header("Location: /hotel/clientes");
            exit;
        }
        
        require_once BASE_PATH . "/views/hotel/client_form.php";
    }
    
    public function view(): void {
        $id = $_GET["id"] ?? 0;
        
        $stmt = $this->hotelDb->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmt->execute([$id]);
        $client = $stmt->fetch();
        
        if (!$client) {
            header("Location: /hotel/clientes");
            exit;
        }
        
        // Obtener historial de reservas
        $stmt = $this->hotelDb->prepare("
            SELECT r.*, h.numero_habitacion, h.tipo
            FROM reservas r
            JOIN habitaciones h ON r.habitacion_id = h.id
            WHERE r.cliente_id = ?
            ORDER BY r.fecha_entrada DESC
        ");
        $stmt->execute([$id]);
        $reservations = $stmt->fetchAll();
        
        require_once BASE_PATH . "/views/hotel/client_detail.php";
    }
}
?>',

    // views/hotel/rooms.php
    'views/hotel/rooms.php' => '<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1>🏠 Habitaciones</h1>
        <p class="subtitle">Gestiona las habitaciones de tu hotel</p>
    </div>
    <a href="/hotel/habitaciones/create" class="btn-primary">+ Nueva Habitación</a>
</div>

<div class="card">
    <div class="rooms-grid">
        <?php foreach ($rooms as $room): ?>
            <div class="room-card">
                <div class="room-header">
                    <div class="room-number"><?= htmlspecialchars($room["numero_habitacion"]) ?></div>
                    <span class="status-badge status-<?= $room["estado"] ?>">
                        <?= ucfirst($room["estado"]) ?>
                    </span>
                </div>
                
                <div class="room-body">
                    <div class="room-type"><?= htmlspecialchars($room["tipo"]) ?></div>
                    <div class="room-info">
                        <span>👥 <?= $room["capacidad"] ?> personas</span>
                        <span>💰 S/ <?= number_format($room["precio_noche"], 2) ?>/noche</span>
                    </div>
                    
                    <?php if ($room["descripcion"]): ?>
                        <p class="room-description"><?= htmlspecialchars($room["descripcion"]) ?></p>
                    <?php endif; ?>
                    
                    <?php if ($room["reservas_activas"] > 0): ?>
                        <div class="room-alert">
                            ⚠️ <?= $room["reservas_activas"] ?> reserva(s) activa(s)
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="room-footer">
                    <a href="/hotel/habitaciones/edit?id=<?= $room["id"] ?>" class="btn-edit">
                        ✏️ Editar
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    .rooms-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        padding: 20px;
    }
    .room-card {
        background: #f9fafb;
        border-radius: 12px;
        overflow: hidden;
        border: 2px solid #e5e7eb;
        transition: all 0.3s;
    }
    .room-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        border-color: #667eea;
    }
    .room-header {
        background: white;
        padding: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #e5e7eb;
    }
    .room-number {
        font-size: 24px;
        font-weight: 700;
        color: #1f2937;
    }
    .room-body {
        padding: 20px;
    }
    .room-type {
        font-size: 18px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 10px;
    }
    .room-info {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
        font-size: 14px;
        color: #6b7280;
    }
    .room-description {
        font-size: 13px;
        color: #6b7280;
        line-height: 1.5;
    }
    .room-alert {
        background: #fef3c7;
        color: #92400e;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12px;
        margin-top: 10px;
    }
    .room-footer {
        padding: 15px;
        background: white;
        border-top: 2px solid #e5e7eb;
    }
    .btn-edit {
        display: block;
        text-align: center;
        padding: 10px;
        background: #667eea;
        color: white;
        text-decoration: none;
        border-radius: 6px;
        font-weight: 600;
        transition: background 0.2s;
    }
    .btn-edit:hover {
        background: #5568d3;
    }
</style>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>',

    // views/hotel/_header.php
    'views/hotel/_header.php' => '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_SESSION["hotel_name"] ?? "Hotel Admin" ?></title>
    <link rel="stylesheet" href="/hotel-system/public/css/hotel-admin.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <div class="navbar-brand-icon">🏨</div>
            <div>
                <div class="navbar-brand-text"><?= htmlspecialchars($_SESSION["hotel_name"]) ?></div>
                <div class="navbar-hotel">Panel de Administración</div>
            </div>
        </div>
        
        <div class="navbar-menu">
            <a href="/hotel/dashboard" class="nav-link">Dashboard</a>
            <a href="/hotel/habitaciones" class="nav-link">Habitaciones</a>
            <a href="/hotel/reservas" class="nav-link">Reservas</a>
            <a href="/hotel/clientes" class="nav-link">Clientes</a>
        </div>
        
        <div class="navbar-user">
            <span style="font-weight: 600;"><?= htmlspecialchars($_SESSION["hotel_user_name"]) ?></span>
            <a href="/hotel/logout" class="btn-logout">Salir</a>
        </div>
    </nav>
    
    <div class="container">',

    // views/hotel/_footer.php
    'views/hotel/_footer.php' => '    </div>
</body>
</html>',

    // public/css/hotel-admin.css
    'public/css/hotel-admin.css' => '* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: #f5f7fa;
}

.navbar {
    background: white;
    padding: 15px 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 1000;
}

.navbar-brand {
    display: flex;
    align-items: center;
    gap: 12px;
}

.navbar-brand-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.navbar-brand-text {
    font-weight: 700;
    font-size: 18px;
    color: #1f2937;
}

.navbar-hotel {
    font-size: 12px;
    color: #6b7280;
}

.navbar-menu {
    display: flex;
    gap: 25px;
    align-items: center;
}

.nav-link {
    color: #6b7280;
    text-decoration: none;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 8px;
    transition: all 0.2s;
    font-size: 14px;
}

.nav-link:hover {
    background: #f3f4f6;
    color: #667eea;
}

.nav-link.active {
    background: #667eea;
    color: white;
}

.navbar-user {
    display: flex;
    align-items: center;
    gap: 15px;
}

.btn-logout {
    padding: 8px 16px;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    font-size: 14px;
}

.container {
    max-width: 1400px;
    margin: 30px auto;
    padding: 0 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.page-header h1 {
    font-size: 32px;
    color: #1f2937;
    margin-bottom: 5px;
}

.subtitle {
    color: #6b7280;
    font-size: 14px;
}

.btn-primary {
    padding: 12px 24px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    font-size: 14px;
    display: inline-block;
    transition: all 0.2s;
}

.btn-primary:hover {
    background: #5568d3;
    transform: translateY(-2px);
}

.card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-disponible { background: #dcfce7; color: #166534; }
.status-ocupada { background: #fee2e2; color: #991b1b; }
.status-reservada { background: #fef3c7; color: #92400e; }
.status-limpieza { background: #dbeafe; color: #1e40af; }
.status-mantenimiento { background: #f3f4f6; color: #374151; }'
];

// Crear archivos adicionales
foreach ($additionalFiles as $path => $content) {
    $fullPath = $baseDir . "/" . $path;
    $dir = dirname($fullPath);
    
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    if (file_put_contents($fullPath, $content)) {
        $created[] = $path;
    }
}

// Actualizar public/index.php con las nuevas rutas
$indexPath = $baseDir . "/public/index.php";
$indexContent = file_get_contents($indexPath);

$hotelRoutesCode = '
    // Rutas adicionales del hotel
    if ($action === "habitaciones") {
        require_once "../controllers/HotelRoomsController.php";
        $subAction = $uri[2] ?? "index";
        $controller = new HotelRoomsController();
        
        if ($subAction === "create") {
            $controller->create();
        } elseif ($subAction === "edit") {
            $controller->edit();
        } else {
            $controller->index();
        }
    } elseif ($action === "reservas") {
        require_once "../controllers/HotelReservationsController.php";
        $subAction = $uri[2] ?? "index";
        $controller = new HotelReservationsController();
        
        if ($subAction === "create") {
            $controller->create();
        } elseif ($subAction === "update-status") {
            $controller->updateStatus();
        } else {
            $controller->index();
        }
    } elseif ($action === "clientes") {
        require_once "../controllers/HotelClientsController.php";
        $subAction = $uri[2] ?? "index";
        $controller = new HotelClientsController();
        
        if ($subAction === "create") {
            $controller->create();
        } elseif ($subAction === "view") {
            $controller->view();
        } else {
            $controller->index();
        }
    }';

// Insertar antes del último else en la sección de hotel
$updatedContent = str_replace(
    '} else {
        http_response_code(404);
        echo "Página no encontrada";
    }
} else {',
    '}' . $hotelRoutesCode . ' else {
        http_response_code(404);
        echo "Página no encontrada";
    }
} else {',
    $indexContent
);

file_put_contents($indexPath, $updatedContent);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Hotel - Instalación Completa</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #1f2937;
            font-size: 32px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .success-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
        }
        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .files-list {
            background: #f9fafb;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            max-height: 400px;
            overflow-y: auto;
        }
        .file-item {
            padding: 10px;
            background: white;
            margin-bottom: 8px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        .file-item::before {
            content: "✅";
            font-size: 16px;
        }
        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            border-radius
