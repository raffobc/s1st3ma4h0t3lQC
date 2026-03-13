<?php
/**
 * INSTALADOR RÁPIDO - Una Sola Página
 * Coloca este archivo en: C:\xampp\htdocs\hotel-system\quick_install.php
 * Accede a: http://localhost/hotel-system/quick_install.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        // Configuración de base de datos
        $host = 'localhost';
        $user = 'root';
        $pass = '';
        $dbname = 'hotel_master';
        
        // 1. Conectar a MySQL
        $pdo = new PDO("mysql:host=$host", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 2. Crear base de datos
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");
        
        $message .= "✅ Base de datos '$dbname' creada/verificada<br>";
        
        // 3. Crear tablas
        $pdo->exec("
        CREATE TABLE IF NOT EXISTS super_usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            activo BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email)
        ) ENGINE=InnoDB;
        ");
        
        $pdo->exec("
        CREATE TABLE IF NOT EXISTS hoteles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(255) NOT NULL,
            razon_social VARCHAR(255) NOT NULL,
            ruc VARCHAR(20) NOT NULL UNIQUE,
            direccion TEXT,
            telefono VARCHAR(20),
            email VARCHAR(255),
            ciudad VARCHAR(100),
            pais VARCHAR(100) DEFAULT 'Perú',
            db_name VARCHAR(100) NOT NULL UNIQUE,
            db_host VARCHAR(100) DEFAULT 'localhost',
            db_user VARCHAR(100),
            db_password VARCHAR(255),
            estado ENUM('activo', 'suspendido', 'inactivo') DEFAULT 'activo',
            plan ENUM('basico', 'profesional', 'empresarial') DEFAULT 'basico',
            max_habitaciones INT DEFAULT 50,
            max_usuarios INT DEFAULT 5,
            fecha_registro DATE NOT NULL,
            fecha_vencimiento DATE,
            zona_horaria VARCHAR(50) DEFAULT 'America/Lima',
            moneda_principal VARCHAR(3) DEFAULT 'PEN',
            idioma VARCHAR(5) DEFAULT 'es',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_estado (estado),
            INDEX idx_db_name (db_name)
        ) ENGINE=InnoDB;
        ");
        
        $pdo->exec("
        CREATE TABLE IF NOT EXISTS hotel_administradores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hotel_id INT NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            telefono VARCHAR(20),
            activo BOOLEAN DEFAULT TRUE,
            ultimo_acceso DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
            UNIQUE KEY unique_email_hotel (email, hotel_id),
            INDEX idx_hotel (hotel_id),
            INDEX idx_email (email)
        ) ENGINE=InnoDB;
        ");
        
        $pdo->exec("
        CREATE TABLE IF NOT EXISTS auditoria (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hotel_id INT,
            usuario_tipo ENUM('super', 'admin') NOT NULL,
            usuario_id INT NOT NULL,
            accion VARCHAR(100) NOT NULL,
            tabla_afectada VARCHAR(100),
            registro_id INT,
            datos_anteriores JSON,
            datos_nuevos JSON,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE SET NULL,
            INDEX idx_hotel (hotel_id),
            INDEX idx_fecha (created_at)
        ) ENGINE=InnoDB;
        ");
        
        $pdo->exec("
        CREATE TABLE IF NOT EXISTS licencias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hotel_id INT NOT NULL,
            plan ENUM('basico', 'profesional', 'empresarial') NOT NULL,
            precio DECIMAL(10, 2) NOT NULL,
            fecha_inicio DATE NOT NULL,
            fecha_fin DATE NOT NULL,
            estado ENUM('activa', 'vencida', 'cancelada') DEFAULT 'activa',
            metodo_pago VARCHAR(50),
            transaccion_id VARCHAR(100),
            notas TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
            INDEX idx_hotel (hotel_id),
            INDEX idx_estado (estado)
        ) ENGINE=InnoDB;
        ");
        
        $message .= "✅ Todas las tablas creadas correctamente<br>";
        
        // 4. Crear superusuario
        $email = $_POST['super_email'];
        $password = password_hash($_POST['super_password'], PASSWORD_DEFAULT);
        $nombre = $_POST['super_name'];
        
        // Verificar si ya existe
        $stmt = $pdo->prepare("SELECT id FROM super_usuarios WHERE email = ?");
        $stmt->execute([$email]);
        
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO super_usuarios (nombre, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$nombre, $email, $password]);
            $message .= "✅ Superusuario creado: $email<br>";
        } else {
            $message .= "⚠️ El superusuario ya existe<br>";
        }
        
        // 5. Crear estructura de carpetas
        $baseDir = __DIR__;
        $directories = [
            'config', 'models', 'controllers', 'helpers', 
            'views', 'views/super', 'views/auth', 'views/rooms', 
            'views/reservations', 'public', 'storage', 'storage/logs'
        ];
        
        $created = 0;
        foreach ($directories as $dir) {
            $path = $baseDir . '/' . $dir;
            if (!file_exists($path)) {
                if (mkdir($path, 0755, true)) {
                    $created++;
                }
            }
        }
        
        $message .= "✅ Carpetas creadas: $created<br>";
        
        // 6. Crear archivo .htaccess
        $htaccess = $baseDir . '/public/.htaccess';
        if (!file_exists($htaccess)) {
            $htaccessContent = "RewriteEngine On\nRewriteBase /hotel-system/public/\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule ^(.*)$ index.php [QSA,L]";
            file_put_contents($htaccess, $htaccessContent);
            $message .= "✅ Archivo .htaccess creado<br>";
        }
        
        // 7. Crear config.php básico
        $configFile = $baseDir . '/config/config.php';
        if (!file_exists($configFile)) {
            $configContent = "<?php\ndefine('BASE_PATH', dirname(__DIR__));\ndefine('MASTER_DB_HOST', 'localhost');\ndefine('MASTER_DB_NAME', 'hotel_master');\ndefine('MASTER_DB_USER', 'root');\ndefine('MASTER_DB_PASS', '');\ndate_default_timezone_set('America/Lima');\nerror_reporting(E_ALL);\nini_set('display_errors', 1);\n?>";
            file_put_contents($configFile, $configContent);
            $message .= "✅ Archivo config.php creado<br>";
        }
        
        $message .= "<br><strong style='color: #10b981; font-size: 20px;'>🎉 ¡INSTALACIÓN COMPLETADA EXITOSAMENTE!</strong><br><br>";
        $message .= "<strong>Credenciales de acceso:</strong><br>";
        $message .= "URL: <a href='public/super/login' style='color: #667eea;'>http://localhost/super/login</a><br>";
        $message .= "Email: <strong>$email</strong><br>";
        $message .= "Contraseña: <strong>(la que ingresaste)</strong><br><br>";
        $message .= "<a href='public/super/login' style='display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;'>Ir al Panel Super Admin →</a>";
        
    } catch (PDOException $e) {
        $error = "❌ Error de base de datos: " . $e->getMessage();
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador Rápido - Hotel System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
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
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
        }
        
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .message {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            line-height: 1.8;
        }
        
        .error {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #991b1b;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        small {
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Instalador Rápido</h1>
        <p class="subtitle">Sistema de Gestión Hotelera Multi-Tenant</p>
        
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if (!$message): ?>
            <div class="info-box">
                <strong>✅ Tu test.php funcionó!</strong><br><br>
                Este instalador usará la misma configuración:<br>
                • Host: <code>localhost</code><br>
                • Usuario: <code>root</code><br>
                • Contraseña: <em>(vacío)</em><br>
                • Base de datos: <code>hotel_master</code>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Nombre del Super Administrador *</label>
                    <input type="text" name="super_name" class="form-control" value="Super Administrador" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="super_email" class="form-control" value="super@hotel.com" required>
                    <small>Usarás este email para iniciar sesión</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Contraseña *</label>
                    <input type="password" name="super_password" class="form-control" placeholder="Mínimo 8 caracteres" required minlength="8">
                    <small>Recuerda esta contraseña, la necesitarás para entrar</small>
                </div>
                
                <button type="submit" name="install" class="btn">
                    ⚡ Instalar Todo Ahora (1 Click)
                </button>
            </form>
            
            <div style="margin-top: 20px; text-align: center; color: #6b7280; font-size: 13px;">
                Este instalador creará automáticamente:<br>
                ✓ Base de datos • ✓ Tablas • ✓ Superusuario • ✓ Carpetas • ✓ Archivos
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
