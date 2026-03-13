<?php
/**
 * CREADOR AUTOMÁTICO DE ARCHIVOS
 * Coloca este archivo en: C:\xampp\htdocs\hotel-system\create_files.php
 * Accede a: http://localhost/hotel-system/create_files.php
 * 
 * Este script creará TODOS los archivos necesarios automáticamente
 */

$baseDir = __DIR__;
$created = [];
$errors = [];

// Definir todos los archivos y su contenido
$files = [
    // config/config.php
    'config/config.php' => '<?php
define(\'BASE_PATH\', dirname(__DIR__));
define(\'MASTER_DB_HOST\', \'localhost\');
define(\'MASTER_DB_NAME\', \'hotel_master\');
define(\'MASTER_DB_USER\', \'root\');
define(\'MASTER_DB_PASS\', \'\');
date_default_timezone_set(\'America/Lima\');
error_reporting(E_ALL);
ini_set(\'display_errors\', 1);
?>',

    // config/MasterDatabase.php
    'config/MasterDatabase.php' => '<?php
class MasterDatabase {
    private static ?PDO $instance = null;
    
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                self::$instance = new PDO(
                    "mysql:host=localhost;dbname=hotel_master;charset=utf8mb4",
                    \'root\',
                    \'\',
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                die("Error de conexión: " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
?>',

    // models/SuperUser.php
    'models/SuperUser.php' => '<?php
class SuperUser {
    private PDO $db;
    
    public function __construct() {
        $this->db = MasterDatabase::getConnection();
    }
    
    public function authenticate(string $email, string $password): ?array {
        $stmt = $this->db->prepare("SELECT * FROM super_usuarios WHERE email = ? AND activo = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user[\'password\'])) {
            unset($user[\'password\']);
            return $user;
        }
        
        return null;
    }
}
?>',

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
    
    public function login(): void {
        if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\') {
            $email = $_POST[\'email\'] ?? \'\';
            $password = $_POST[\'password\'] ?? \'\';
            
            $user = $this->superUserModel->authenticate($email, $password);
            
            if ($user) {
                $_SESSION[\'super_user_id\'] = $user[\'id\'];
                $_SESSION[\'super_user_name\'] = $user[\'nombre\'];
                $_SESSION[\'super_user_email\'] = $user[\'email\'];
                
                header(\'Location: /super/dashboard\');
                exit;
            } else {
                $error = \'Credenciales inválidas\';
                require_once BASE_PATH . \'/views/super/login.php\';
                return;
            }
        }
        
        require_once BASE_PATH . \'/views/super/login.php\';
    }
    
    public function dashboard(): void {
        if (!isset($_SESSION[\'super_user_id\'])) {
            header(\'Location: /super/login\');
            exit;
        }
        
        $hotels = $this->hotelModel->getAllHotels();
        require_once BASE_PATH . \'/views/super/dashboard.php\';
    }
    
    public function logout(): void {
        session_destroy();
        header(\'Location: /super/login\');
        exit;
    }
}
?>',

    // public/index.php
    'public/index.php' => '<?php
session_start();
require_once \'../config/config.php\';
require_once \'../config/MasterDatabase.php\';

$uri = parse_url($_SERVER[\'REQUEST_URI\'], PHP_URL_PATH);
$uri = str_replace(\'/hotel-system/public\', \'\', $uri);
$uri = explode(\'/\', trim($uri, \'/\'));
$uri = array_values(array_filter($uri));

$accessType = $uri[0] ?? \'\';

if ($accessType === \'super\') {
    require_once \'../models/Hotel.php\';
    require_once \'../models/SuperUser.php\';
    require_once \'../controllers/SuperAdminController.php\';
    
    $controller = new SuperAdminController();
    $action = $uri[1] ?? \'login\';
    
    switch($action) {
        case \'login\':
            $controller->login();
            break;
        case \'dashboard\':
            $controller->dashboard();
            break;
        case \'logout\':
            $controller->logout();
            break;
        default:
            http_response_code(404);
            echo "Página no encontrada";
    }
} else {
    echo "<h1>Bienvenido al Sistema Hotel</h1>";
    echo \'<p><a href="/super/login">Ir a Super Admin</a></p>\';
}
?>',

    // views/super/login.php
    'views/super/login.php' => '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Super Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;
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
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">👑</div>
            <h1>Super Admin</h1>
            <p class="subtitle">Sistema de Gestión Hotelera</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="super@hotel.com" required autofocus>
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
            Smart Hotel System v1.0<br>
            © 2025 Todos los derechos reservados
        </p>
    </div>
</body>
</html>',

    // views/super/dashboard.php
    'views/super/dashboard.php' => '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Super Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;
            background: #f5f7fa;
        }
        .navbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 20px;
            color: #667eea;
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
        .header {
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
        }
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #667eea;
        }
        .stat-label {
            color: #6b7280;
            font-size: 14px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
            background: white;
            border-radius: 15px;
        }
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">👑 Super Admin - Smart Hotel</div>
        <div>
            <span><?= htmlspecialchars($_SESSION[\'super_user_name\']) ?></span>
            <a href="/super/logout" class="btn-logout">Salir</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="header">
            <h1>¡Bienvenido al Panel de Superadministrador!</h1>
            <p>Gestiona todos los hoteles del sistema</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= count($hotels) ?></div>
                <div class="stat-label">Total de Hoteles</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count(array_filter($hotels, fn($h) => $h[\'estado\'] === \'activo\')) ?></div>
                <div class="stat-label">Hoteles Activos</div>
            </div>
        </div>
        
        <div class="empty-state">
            <div class="empty-state-icon">🏨</div>
            <h3>¡Sistema Instalado Correctamente!</h3>
            <p>Todo funciona perfecto. Ahora puedes comenzar a crear hoteles.</p>
        </div>
    </div>
</body>
</html>',
];

// Crear los archivos
foreach ($files as $path => $content) {
    $fullPath = $baseDir . '/' . $path;
    $dir = dirname($fullPath);
    
    // Crear directorio si no existe
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Crear archivo
    if (file_put_contents($fullPath, $content)) {
        $created[] = $path;
    } else {
        $errors[] = "No se pudo crear: $path";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archivos Creados</title>
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
        h1 {
            color: #10b981;
            margin-bottom: 20px;
            font-size: 32px;
        }
        .success-box {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
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
        .file-item:last-child {
            border-bottom: none;
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
        .stats {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        .stat {
            flex: 1;
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
        }
        .stat-label {
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ ¡Archivos Creados Exitosamente!</h1>
        
        <div class="success-box">
            <strong>🎉 ¡Todo listo!</strong><br>
            Se han creado todos los archivos necesarios para que el sistema funcione.
        </div>
        
        <div class="stats">
            <div class="stat">
                <div class="stat-value"><?= count($created) ?></div>
                <div class="stat-label">Archivos Creados</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?= count($errors) ?></div>
                <div class="stat-label">Errores</div>
            </div>
        </div>
        
        <h3>Archivos creados:</h3>
        <div class="file-list">
            <?php foreach ($created as $file): ?>
                <div class="file-item">✓ <?= htmlspecialchars($file) ?></div>
            <?php endforeach; ?>
        </div>
        
        <?php if (!empty($errors)): ?>
            <h3 style="color: #ef4444;">Errores:</h3>
            <div class="file-list" style="background: #fee2e2;">
                <?php foreach ($errors as $error): ?>
                    <div class="file-item">❌ <?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; padding: 20px; background: #f0f9ff; border-radius: 8px;">
            <strong>🔑 Credenciales de acceso:</strong><br><br>
            <strong>URL:</strong> <a href="public/super/login" style="color: #667eea;">http://localhost/super/login</a><br>
            <strong>Email:</strong> super@hotel.com<br>
            <strong>Password:</strong> Admin123!
        </div>
        
        <a href="public/super/login" class="btn">
            🚀 Ir al Panel Super Admin
        </a>
        
        <p style="margin-top: 20px; color: #6b7280; font-size: 14px;">
            <strong>Nota:</strong> Puedes eliminar los archivos install.php, quick_install.php y create_files.php por seguridad.
        </p>
    </div>
</body>
</html>
