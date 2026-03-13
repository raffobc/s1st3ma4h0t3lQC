<?php
/**
 * CORREGIR CONTROLADORES DEL HOTEL
 * Archivo: C:\xampp\htdocs\hotel-system\fix_controllers.php
 * Ejecuta este archivo para corregir los errores de sintaxis
 */

$baseDir = __DIR__;
$fixed = [];

// Corregir HotelRoomsController.php
$roomsController = <<<'EOD'
<?php
class HotelRoomsController {
    private $hotelDb;
    
    public function __construct() {
        $this->checkAuth();
        $this->hotelDb = $this->getHotelConnection();
    }
    
    private function checkAuth() {
        if (!isset($_SESSION["hotel_user_id"])) {
            header("Location: /hotel/login");
            exit;
        }
    }
    
    private function getHotelConnection() {
        $dbName = $_SESSION["hotel_db_name"];
        $credentials = $_SESSION["hotel_db_credentials"];
        
        return new PDO(
            "mysql:host={$credentials['host']};dbname=$dbName;charset=utf8mb4",
            $credentials["user"],
            $credentials["password"],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }
    
    public function index() {
        $stmt = $this->hotelDb->query("
            SELECT h.*, 
                   (SELECT COUNT(*) FROM reservas r 
                    WHERE r.habitacion_id = h.id 
                    AND r.estado IN ('reservada', 'ocupada')) as reservas_activas
            FROM habitaciones h
            ORDER BY h.numero_habitacion
        ");
        
        $rooms = $stmt->fetchAll();
        require_once BASE_PATH . "/views/hotel/rooms.php";
    }
    
    public function create() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $numero = $_POST["numero_habitacion"];
            $tipo = $_POST["tipo"];
            $precio = $_POST["precio_noche"];
            $capacidad = $_POST["capacidad"];
            $descripcion = $_POST["descripcion"] ?? "";
            
            $stmt = $this->hotelDb->prepare("
                INSERT INTO habitaciones (numero_habitacion, tipo, precio_noche, capacidad, descripcion, estado)
                VALUES (?, ?, ?, ?, ?, 'disponible')
            ");
            
            $stmt->execute([$numero, $tipo, $precio, $capacidad, $descripcion]);
            
            header("Location: /hotel/habitaciones");
            exit;
        }
        
        require_once BASE_PATH . "/views/hotel/room_form.php";
    }
    
    public function edit() {
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
EOD;

file_put_contents($baseDir . "/controllers/HotelRoomsController.php", $roomsController);
$fixed[] = "HotelRoomsController.php";

// Corregir HotelReservationsController.php
$reservationsController = <<<'EOD'
<?php
class HotelReservationsController {
    private $hotelDb;
    
    public function __construct() {
        $this->checkAuth();
        $this->hotelDb = $this->getHotelConnection();
    }
    
    private function checkAuth() {
        if (!isset($_SESSION["hotel_user_id"])) {
            header("Location: /hotel/login");
            exit;
        }
    }
    
    private function getHotelConnection() {
        $dbName = $_SESSION["hotel_db_name"];
        $credentials = $_SESSION["hotel_db_credentials"];
        
        return new PDO(
            "mysql:host={$credentials['host']};dbname=$dbName;charset=utf8mb4",
            $credentials["user"],
            $credentials["password"],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }
    
    public function index() {
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
            $sql .= " WHERE r.estado IN ('reservada', 'ocupada')";
        } elseif ($filter === "finished") {
            $sql .= " WHERE r.estado = 'finalizada'";
        } elseif ($filter === "cancelled") {
            $sql .= " WHERE r.estado = 'cancelada'";
        }
        
        $sql .= " ORDER BY r.fecha_entrada DESC";
        
        $reservations = $this->hotelDb->query($sql)->fetchAll();
        require_once BASE_PATH . "/views/hotel/reservations.php";
    }
    
    public function create() {
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
                VALUES (?, ?, ?, ?, ?, ?, ?, 'reservada')
            ");
            
            $stmt->execute([
                $clienteId, $habitacionId, $fechaEntrada, $fechaSalida,
                $numHuespedes, $total, $observaciones
            ]);
            
            // Actualizar estado de habitación
            $stmt = $this->hotelDb->prepare("UPDATE habitaciones SET estado = 'reservada' WHERE id = ?");
            $stmt->execute([$habitacionId]);
            
            header("Location: /hotel/reservas");
            exit;
        }
        
        // Obtener clientes y habitaciones disponibles
        $clientes = $this->hotelDb->query("SELECT * FROM clientes ORDER BY nombre")->fetchAll();
        $habitaciones = $this->hotelDb->query("
            SELECT * FROM habitaciones 
            WHERE estado IN ('disponible', 'limpieza') 
            ORDER BY numero_habitacion
        ")->fetchAll();
        
        require_once BASE_PATH . "/views/hotel/reservation_form.php";
    }
    
    public function updateStatus() {
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
EOD;

file_put_contents($baseDir . "/controllers/HotelReservationsController.php", $reservationsController);
$fixed[] = "HotelReservationsController.php";

// Corregir HotelClientsController.php
$clientsController = <<<'EOD'
<?php
class HotelClientsController {
    private $hotelDb;
    
    public function __construct() {
        $this->checkAuth();
        $this->hotelDb = $this->getHotelConnection();
    }
    
    private function checkAuth() {
        if (!isset($_SESSION["hotel_user_id"])) {
            header("Location: /hotel/login");
            exit;
        }
    }
    
    private function getHotelConnection() {
        $dbName = $_SESSION["hotel_db_name"];
        $credentials = $_SESSION["hotel_db_credentials"];
        
        return new PDO(
            "mysql:host={$credentials['host']};dbname=$dbName;charset=utf8mb4",
            $credentials["user"],
            $credentials["password"],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }
    
    public function index() {
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
    
    public function create() {
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
    
    public function view() {
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
EOD;

file_put_contents($baseDir . "/controllers/HotelClientsController.php", $clientsController);
$fixed[] = "HotelClientsController.php";

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>✅ Controladores Corregidos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 40px;
            margin: 0;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        h1 {
            color: #1f2937;
            text-align: center;
            font-size: 32px;
            margin-bottom: 20px;
        }
        .success {
            background: #dcfce7;
            border-left: 4px solid #10b981;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .file-list {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
        }
        .file-item {
            padding: 12px;
            background: white;
            margin: 8px 0;
            border-radius: 6px;
            border-left: 3px solid #10b981;
            font-weight: 600;
        }
        .btn-link {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin: 10px 5px;
        }
        .btn-link:hover {
            background: #5568d3;
        }
        .text-center {
            text-align: center;
            margin-top: 30px;
        }
        .icon {
            font-size: 64px;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">✅</div>
        <h1>¡Controladores Corregidos!</h1>
        
        <div class="success">
            <strong>🎉 Perfecto!</strong> Se han corregido todos los errores de sintaxis en los controladores.
        </div>
        
        <h3>📁 Archivos Corregidos:</h3>
        <div class="file-list">
            <?php foreach ($fixed as $file): ?>
                <div class="file-item">✓ controllers/<?= htmlspecialchars($file) ?></div>
            <?php endforeach; ?>
        </div>
        
        <div class="success" style="background: #dbeafe; border-color: #3b82f6;">
            <strong>ℹ️ Cambios realizados:</strong>
            <ul style="margin: 10px 0 0 20px;">
                <li>Corregidas las comillas en arrays</li>
                <li>Removidos type hints PDO incompatibles</li>
                <li>Corregida sintaxis en métodos privados</li>
            </ul>
        </div>
        
        <div class="text-center">
            <a href="/hotel/dashboard" class="btn-link">
                📊 Ir al Dashboard
            </a>
            <a href="/hotel/habitaciones" class="btn-link">
                🏠 Ver Habitaciones
            </a>
        </div>
    </div>
</body>
</html>
