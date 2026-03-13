<?php
/**
 * CREAR SISTEMA DE LOGIN Y DASHBOARD PARA ADMINISTRADORES DE HOTEL
 * Archivo: C:\xampp\htdocs\hotel-system\create_hotel_admin.php
 * Accede a: http://localhost/hotel-system/create_hotel_admin.php
 */

$baseDir = __DIR__;
$created = [];

$files = [
    // controllers/HotelAuthController.php
    'controllers/HotelAuthController.php' => '<?php
class HotelAuthController {
    private PDO $masterDb;
    
    public function __construct() {
        $this->masterDb = MasterDatabase::getConnection();
    }
    
    public function login(): void {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $email = $_POST["email"] ?? "";
            $password = $_POST["password"] ?? "";
            
            // Buscar administrador en la base maestra
            $stmt = $this->masterDb->prepare("
                SELECT ha.*, h.id as hotel_id, h.nombre as hotel_nombre, 
                       h.db_name, h.db_host, h.db_user, h.db_password, h.estado
                FROM hotel_administradores ha
                JOIN hoteles h ON ha.hotel_id = h.id
                WHERE ha.email = ? AND ha.activo = 1 AND h.estado = \"activo\"
            ");
            
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin["password"])) {
                // Configurar sesión
                $_SESSION["hotel_user_id"] = $admin["id"];
                $_SESSION["hotel_user_name"] = $admin["nombre"];
                $_SESSION["hotel_user_email"] = $admin["email"];
                $_SESSION["hotel_id"] = $admin["hotel_id"];
                $_SESSION["hotel_name"] = $admin["hotel_nombre"];
                $_SESSION["hotel_db_name"] = $admin["db_name"];
                $_SESSION["hotel_db_credentials"] = [
                    "host" => $admin["db_host"],
                    "user" => $admin["db_user"],
                    "password" => $admin["db_password"]
                ];
                
                // Actualizar último acceso
                $stmt = $this->masterDb->prepare("
                    UPDATE hotel_administradores 
                    SET ultimo_acceso = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$admin["id"]]);
                
                header("Location: /hotel/dashboard");
                exit;
            } else {
                $error = "Credenciales inválidas o hotel inactivo";
            }
        }
        
        require_once BASE_PATH . "/views/hotel/login.php";
    }
    
    public function logout(): void {
        session_destroy();
        header("Location: /hotel/login");
        exit;
    }
}
?>',

    // controllers/HotelDashboardController.php
    'controllers/HotelDashboardController.php' => '<?php
class HotelDashboardController {
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
        
        $pdo = new PDO(
            "mysql:host={$credentials[\"host\"]};dbname=$dbName;charset=utf8mb4",
            $credentials["user"],
            $credentials["password"],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        return $pdo;
    }
    
    public function dashboard(): void {
        $stats = $this->getStats();
        $recentReservations = $this->getRecentReservations();
        $roomsByStatus = $this->getRoomsByStatus();
        
        require_once BASE_PATH . "/views/hotel/dashboard.php";
    }
    
    private function getStats(): array {
        // Total de habitaciones
        $stmt = $this->hotelDb->query("SELECT COUNT(*) as total FROM habitaciones");
        $totalRooms = $stmt->fetch()["total"];
        
        // Habitaciones disponibles
        $stmt = $this->hotelDb->query("SELECT COUNT(*) as total FROM habitaciones WHERE estado = \"disponible\"");
        $availableRooms = $stmt->fetch()["total"];
        
        // Habitaciones ocupadas
        $stmt = $this->hotelDb->query("SELECT COUNT(*) as total FROM habitaciones WHERE estado = \"ocupada\"");
        $occupiedRooms = $stmt->fetch()["total"];
        
        // Reservas activas
        $stmt = $this->hotelDb->query("SELECT COUNT(*) as total FROM reservas WHERE estado IN (\"reservada\", \"ocupada\")");
        $activeReservations = $stmt->fetch()["total"];
        
        // Total clientes
        $stmt = $this->hotelDb->query("SELECT COUNT(*) as total FROM clientes");
        $totalClients = $stmt->fetch()["total"];
        
        // Ingresos del mes
        $stmt = $this->hotelDb->query("
            SELECT COALESCE(SUM(monto), 0) as total 
            FROM pagos 
            WHERE MONTH(fecha_pago) = MONTH(CURRENT_DATE())
            AND YEAR(fecha_pago) = YEAR(CURRENT_DATE())
        ");
        $monthlyRevenue = $stmt->fetch()["total"];
        
        return [
            "total_rooms" => $totalRooms,
            "available_rooms" => $availableRooms,
            "occupied_rooms" => $occupiedRooms,
            "active_reservations" => $activeReservations,
            "total_clients" => $totalClients,
            "monthly_revenue" => $monthlyRevenue,
            "occupancy_rate" => $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0
        ];
    }
    
    private function getRecentReservations(): array {
        $stmt = $this->hotelDb->query("
            SELECT r.*, 
                   h.numero_habitacion, h.tipo as tipo_habitacion,
                   c.nombre as cliente_nombre, c.documento as cliente_documento
            FROM reservas r
            JOIN habitaciones h ON r.habitacion_id = h.id
            JOIN clientes c ON r.cliente_id = c.id
            ORDER BY r.created_at DESC
            LIMIT 5
        ");
        
        return $stmt->fetchAll();
    }
    
    private function getRoomsByStatus(): array {
        $stmt = $this->hotelDb->query("
            SELECT estado, COUNT(*) as cantidad
            FROM habitaciones
            GROUP BY estado
        ");
        
        return $stmt->fetchAll();
    }
}
?>',

    // views/hotel/login.php
    'views/hotel/login.php' => '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hotel Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            padding: 50px 40px;
        }
        .logo {
            text-align: center;
            margin-bottom: 40px;
        }
        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin-bottom: 20px;
        }
        h1 {
            color: #1f2937;
            font-size: 28px;
            text-align: center;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #6b7280;
            text-align: center;
            margin-bottom: 40px;
            font-size: 14px;
        }
        .form-group { margin-bottom: 25px; }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            transition: border 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn-login {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s;
            margin-top: 10px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        .alert {
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        .footer-text {
            text-align: center;
            margin-top: 30px;
            color: #6b7280;
            font-size: 13px;
        }
        .footer-text a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">🏨</div>
            <h1>Portal del Hotel</h1>
            <p class="subtitle">Acceso para Administradores</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="admin@hotel.com" required autofocus>
            </div>
            
            <div class="form-group">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="btn-login">
                Iniciar Sesión →
            </button>
        </form>
        
        <p class="footer-text">
            ¿Eres Super Admin? <a href="/super/login">Ir al Panel Super</a><br><br>
            Smart Hotel System © 2025
        </p>
    </div>
</body>
</html>',

    // views/hotel/dashboard.php - PARTE 1
    'views/hotel/dashboard.php' => '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($_SESSION["hotel_name"]) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f7fa;
        }
        
        /* Navbar */
        .navbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        
        /* Container */
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        /* Header */
        .dashboard-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            font-size: 32px;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #6b7280;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .stat-icon.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-icon.warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .stat-icon.danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .stat-icon.info { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #6b7280;
            font-size: 14px;
            font-weight: 600;
        }
        
        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        /* Card */
        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
        }
        .btn-primary {
            padding: 8px 16px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
        }
        
        /* Table */
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th {
            background: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 13px;
            border-bottom: 2px solid #e5e7eb;
        }
        .table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-reservada { background: #dbeafe; color: #1e40af; }
        .status-ocupada { background: #dcfce7; color: #166534; }
        .status-finalizada { background: #f3f4f6; color: #374151; }
        
        /* Room Status */
        .room-status-grid {
            display: grid;
            gap: 15px;
        }
        .room-status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f9fafb;
            border-radius: 10px;
        }
        .room-status-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #374151;
        }
        .room-status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        .dot-disponible { background: #10b981; }
        .dot-ocupada { background: #ef4444; }
        .dot-reservada { background: #f59e0b; }
        .dot-limpieza { background: #3b82f6; }
        .room-status-count {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
    </style>
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
            <a href="/hotel/dashboard" class="nav-link active">Dashboard</a>
            <a href="/hotel/habitaciones" class="nav-link">Habitaciones</a>
            <a href="/hotel/reservas" class="nav-link">Reservas</a>
            <a href="/hotel/clientes" class="nav-link">Clientes</a>
        </div>
        
        <div class="navbar-user">
            <span style="font-weight: 600;"><?= htmlspecialchars($_SESSION["hotel_user_name"]) ?></span>
            <a href="/hotel/logout" class="btn-logout">Salir</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>¡Bienvenido de nuevo! 👋</h1>
            <p class="subtitle">Aquí está un resumen de tu hotel hoy</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats["total_rooms"] ?></div>
                        <div class="stat-label">Total Habitaciones</div>
                    </div>
                    <div class="stat-icon primary">🏠</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats["available_rooms"] ?></div>
                        <div class="stat-label">Habitaciones Disponibles</div>
                    </div>
                    <div class="stat-icon success">✓</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats["occupied_rooms"] ?></div>
                        <div class="stat-label">Habitaciones Ocupadas</div>
                    </div>
                    <div class="stat-icon danger">🔒</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats["occupancy_rate"] ?>%</div>
                        <div class="stat-label">Tasa de Ocupación</div>
                    </div>
                    <div class="stat-icon info">📊</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats["active_reservations"] ?></div>
                        <div class="stat-label">Reservas Activas</div>
                    </div>
                    <div class="stat-icon warning">📅</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value">S/ <?= number_format($stats["monthly_revenue"], 2) ?></div>
                        <div class="stat-label">Ingresos del Mes</div>
                    </div>
                    <div class="stat-icon success">💰</div>
                </div>
            </div>
        </div>
        
        <div class="content-grid">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Reservas Recientes</h2>
                    <a href="/hotel/reservas" class="btn-primary">Ver Todas</a>
                </div>
                
                <?php if (empty($recentReservations)): ?>
                    <div class="empty-state">
                        <p>No hay reservas registradas</p>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Habitación</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentReservations as $reserva): ?>
                                <tr>
                                    <td><?= htmlspecialchars($reserva["cliente_nombre"]) ?></td>
                                    <td><?= htmlspecialchars($reserva["numero_habitacion"]) ?></td>
                                    <td><?= date("d/m/Y", strtotime($reserva["fecha_entrada"])) ?></td>
                                    <td><?= date("d/m/Y", strtotime($reserva["fecha_salida"])) ?></td>
                                    <td><span class="status-badge status-<?= $reserva["estado"] ?>"><?= ucfirst($reserva["estado"]) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Estado de Habitaciones</h2>
                </div>
                
                <div class="room-status-grid">
                    <?php foreach ($roomsByStatus as $status): ?>
                        <div class="room-status-item">
                            <div class="room-status-label">
                                <span class="room-status-dot dot-<?= $status["estado"] ?>"></span>
                                <?= ucfirst($status["estado"]) ?>
                            </div>
                            <div class="room-status-count"><?= $status["cantidad"] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>'
];

// Crear archivos
foreach ($files as $path => $content) {
    $fullPath = $baseDir . "/" . $path;
    $dir = dirname($fullPath);
    
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    if (file_put_contents($fullPath, $content)) {
        $created[] = $path;
    }
}

// Actualizar public/index.php para incluir rutas del hotel
$indexPath = $baseDir . "/public/index.php";
$indexContent = file_get_contents($indexPath);

// Buscar el else final y reemplazarlo
$newIndexContent = str_replace(
    \'} else {
    echo "<h1>Bienvenido al Sistema Hotel</h1>";
    echo "<p><a href=\\"/super/login\\">Ir a Super Admin</a></p>";
}\',
    \'} elseif ($accessType === "hotel") {
    require_once "../models/Hotel.php";
    require_once "../models/SuperUser.php";
    require_once "../config/MasterDatabase.php";
    require_once "../controllers/HotelAuthController.php";
    require_once "../controllers/HotelDashboardController.php";
    
    $action = $uri[1] ?? "login";
    
    if ($action === "login") {
        $controller = new HotelAuthController();
        $controller->login();
    } elseif ($action === "logout") {
        $controller = new HotelAuthController();
        $controller->logout();
    } elseif ($action === "dashboard") {
        $controller = new HotelDashboardController();
        $controller->dashboard();
    } else {
        http_response_code(404);
        echo "Página no encontrada";
    }
} else {
    echo "<h1>Bienvenido al Sistema Hotel</h1>";
    echo "<p><a href=\\"/super/login\\">Super Admin</a> | <a href=\\"/hotel/login\\">Admin Hotel</a></p>";
}\',
    $indexContent
);

file_put_contents($indexPath, $newIndexContent);
$created[] = "public/index.php (actualizado)";

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>✅ Sistema de Login del Hotel Creado</title>
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
            max-width: 800px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { color: #10b981; font-size: 32px; margin-bottom: 20px; text-align: center; }
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
        .credentials-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
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
        .icon { font-size: 64px; margin-bottom: 20px; text-align: center; }
        .features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }
        .feature-item {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🎉</div>
        <h1>¡Sistema de Login del Hotel Creado!</h1>
        
        <div class="success-box">
            <strong>✅ Todo está listo para que los administradores de hotel accedan a su sistema</strong>
        </div>
        
        <h3>Archivos creados:</h3>
        <div class="file-list">
            <?php foreach ($created as $file): ?>
                <div class="file-item">✓ <?= htmlspecialchars($file) ?></div>
            <?php endforeach; ?>
        </div>
        
        <h3>🎯 Funcionalidades incluidas:</h3>
        <div class="features">
            <div class="feature-item">
                <span>🔐</span>
                <span>Login seguro para admins de hotel</span>
            </div>
            <div class="feature-item">
                <span>📊</span>
                <span>Dashboard con estadísticas</span>
            </div>
            <div class="feature-item">
                <span>🏠</span>
                <span>Total de habitaciones</span>
            </div>
            <div class="feature-item">
                <span>✓</span>
                <span>Habitaciones disponibles</span>
            </div>
            <div class="feature-item">
                <span>🔒</span>
                <span>Habitaciones ocupadas</span>
            </div>
            <div class="feature-item">
                <span>📈</span>
                <span>Tasa de ocupación</span>
            </div>
            <div class="feature-item">
                <span>📅</span>
                <span>Reservas activas</span>
            </div>
            <div class="feature-item">
                <span>💰</span>
                <span>Ingresos del mes</span>
            </div>
            <div class="feature-item">
                <span>📋</span>
                <span>Lista de reservas recientes</span>
            </div>
            <div class="feature-item">
                <span>🎨</span>
                <span>Estado por tipo de habitación</span>
            </div>
        </div>
        
        <div class="credentials-box">
            <h3 style="margin-bottom: 15px;">🔑 Credenciales de Acceso</h3>
            <p><strong>URL del Hotel:</strong> <a href="http://localhost/hotel/login" style="color: #667eea;">http://localhost/hotel/login</a></p>
            <p style="margin-top: 10px;"><strong>Email:</strong> admin@paradise.com (o el que creaste)</p>
            <p><strong>Password:</strong> Admin123! (o el que creaste)</p>
            <hr style="margin: 15px 0; border: none; border-top: 1px solid #cbd5e1;">
            <p><strong>URL Super Admin:</strong> <a href="http://localhost/super/login" style="color: #667eea;">http://localhost/super/login</a></p>
            <p style="margin-top: 10px;"><strong>Email:</strong> super@hotel.com</p>
            <p><strong>Password:</strong> Admin123!</p>
        </div>
        
        <div style="text-align: center;">
            <a href="public/hotel/login" class="btn">
                🏨 Ir al Login del Hotel
            </a>
            <a href="public/super/login" class="btn">
                👑 Ir al Super Admin
            </a>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #fffbeb; border-radius: 8px;">
            <h3 style="margin-bottom: 10px;">📚 Próximos Pasos:</h3>
            <ol style="margin-left: 20px; line-height: 1.8;">
                <li>Inicia sesión como admin del hotel</li>
                <li>Verás el dashboard con todas las estadísticas</li>
                <li>Por ahora no hay habitaciones ni reservas (aparecerán 0)</li>
                <li>Siguiente: Crear módulo de gestión de habitaciones</li>
                <li>Luego: Sistema de reservas completo</li>
            </ol>
        </div>
    </div>
</body>
</html>
