<?php
/**
 * INSTALADOR AUTOMÁTICO - Sistema Hotel Management
 * 
 * INSTRUCCIONES:
 * 1. Coloca este archivo en: C:\xampp\htdocs\hotel-system\
 * 2. Accede a: http://localhost/hotel-system/install.php
 * 3. Sigue los pasos del instalador
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Variables de instalación
$step = $_GET['step'] ?? 1;
$errors = [];
$success = [];

// Función para verificar requisitos
function checkRequirements() {
    $requirements = [
        'php_version' => [
            'name' => 'PHP 8.0 o superior',
            'check' => version_compare(PHP_VERSION, '8.0.0', '>='),
            'current' => PHP_VERSION
        ],
        'pdo_mysql' => [
            'name' => 'PDO MySQL',
            'check' => extension_loaded('pdo_mysql'),
            'current' => extension_loaded('pdo_mysql') ? 'Instalado' : 'No instalado'
        ],
        'json' => [
            'name' => 'JSON',
            'check' => extension_loaded('json'),
            'current' => extension_loaded('json') ? 'Instalado' : 'No instalado'
        ],
        'mbstring' => [
            'name' => 'MBString',
            'check' => extension_loaded('mbstring'),
            'current' => extension_loaded('mbstring') ? 'Instalado' : 'No instalado'
        ]
    ];
    
    return $requirements;
}

// Función para crear estructura de carpetas
function createDirectoryStructure($baseDir) {
    $directories = [
        'config',
        'models',
        'controllers',
        'helpers',
        'views',
        'views/super',
        'views/auth',
        'views/dashboard',
        'views/rooms',
        'views/reservations',
        'views/pos',
        'public',
        'database',
        'storage',
        'storage/logs'
    ];
    
    $created = [];
    $errors = [];
    
    foreach ($directories as $dir) {
        $path = $baseDir . '/' . $dir;
        if (!file_exists($path)) {
            if (mkdir($path, 0755, true)) {
                $created[] = $dir;
            } else {
                $errors[] = "No se pudo crear: $dir";
            }
        }
    }
    
    return ['created' => $created, 'errors' => $errors];
}

// Función para crear base de datos
function createMasterDatabase($host, $user, $pass, $dbname) {
    try {
        $pdo = new PDO("mysql:host=$host", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Crear base de datos
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");
        
        // Crear tablas
        $sql = "
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
        ";
        
        foreach (explode(';', $sql) as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        return true;
    } catch (PDOException $e) {
        throw new Exception("Error en base de datos: " . $e->getMessage());
    }
}

// Función para crear superusuario
function createSuperUser($host, $user, $pass, $dbname, $email, $password, $nombre) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO super_usuarios (nombre, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $email, $hashedPassword]);
        
        return true;
    } catch (PDOException $e) {
        throw new Exception("Error creando superusuario: " . $e->getMessage());
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 2) {
        // Verificar conexión
        try {
            $host = $_POST['db_host'];
            $user = $_POST['db_user'];
            $pass = $_POST['db_pass'];
            $dbname = $_POST['db_name'];
            
            $pdo = new PDO("mysql:host=$host", $user, $pass);
            $_SESSION['db_config'] = [
                'host' => $host,
                'user' => $user,
                'pass' => $pass,
                'dbname' => $dbname
            ];
            $success[] = "Conexión exitosa";
            $step = 3;
        } catch (PDOException $e) {
            $errors[] = "Error de conexión: " . $e->getMessage();
        }
    } elseif ($step == 3) {
        // Crear estructura
        $baseDir = __DIR__;
        $result = createDirectoryStructure($baseDir);
        
        if (empty($result['errors'])) {
            $success[] = "Estructura de carpetas creada correctamente";
            $step = 4;
        } else {
            $errors = $result['errors'];
        }
    } elseif ($step == 4) {
        // Crear base de datos y superusuario
        try {
            $config = $_SESSION['db_config'];
            createMasterDatabase($config['host'], $config['user'], $config['pass'], $config['dbname']);
            $success[] = "Base de datos creada correctamente";
            
            createSuperUser(
                $config['host'], 
                $config['user'], 
                $config['pass'], 
                $config['dbname'],
                $_POST['super_email'],
                $_POST['super_password'],
                $_POST['super_name']
            );
            $success[] = "Superusuario creado correctamente";
            
            $step = 5;
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador - Hotel Management System</title>
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
        
        .installer {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .steps {
            display: flex;
            justify-content: space-between;
            padding: 20px 30px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            border-radius: 8px;
            font-weight: 600;
            color: #9ca3af;
        }
        
        .step.active {
            background: #667eea;
            color: white;
        }
        
        .step.completed {
            background: #10b981;
            color: white;
        }
        
        .content {
            padding: 40px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
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
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .requirements {
            list-style: none;
        }
        
        .requirements li {
            padding: 12px;
            margin-bottom: 10px;
            background: #f9fafb;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .requirements .status {
            font-weight: 600;
        }
        
        .requirements .status.ok {
            color: #10b981;
        }
        
        .requirements .status.fail {
            color: #ef4444;
        }
        
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="installer">
        <div class="header">
            <h1>🏨 Instalador Sistema Hotel Management</h1>
            <p>Instalación paso a paso</p>
        </div>
        
        <div class="steps">
            <div class="step <?= $step >= 1 ? ($step == 1 ? 'active' : 'completed') : '' ?>">1. Requisitos</div>
            <div class="step <?= $step >= 2 ? ($step == 2 ? 'active' : 'completed') : '' ?>">2. Base de Datos</div>
            <div class="step <?= $step >= 3 ? ($step == 3 ? 'active' : 'completed') : '' ?>">3. Estructura</div>
            <div class="step <?= $step >= 4 ? ($step == 4 ? 'active' : 'completed') : '' ?>">4. Super Admin</div>
            <div class="step <?= $step >= 5 ? 'active' : '' ?>">5. Completado</div>
        </div>
        
        <div class="content">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p>❌ <?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php foreach ($success as $msg): ?>
                        <p>✅ <?= htmlspecialchars($msg) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($step == 1): ?>
                <h2>Verificación de Requisitos</h2>
                <p>Verificando que tu servidor cumple con los requisitos...</p>
                
                <ul class="requirements">
                    <?php foreach (checkRequirements() as $req): ?>
                        <li>
                            <span>
                                <?= $req['name'] ?>
                                <small style="color: #6b7280;">(<?= $req['current'] ?>)</small>
                            </span>
                            <span class="status <?= $req['check'] ? 'ok' : 'fail' ?>">
                                <?= $req['check'] ? '✅ OK' : '❌ FALTA' ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <div style="margin-top: 30px;">
                    <a href="?step=2" class="btn btn-primary">Continuar →</a>
                </div>
                
            <?php elseif ($step == 2): ?>
                <h2>Configuración de Base de Datos</h2>
                <p>Ingresa los datos de conexión a MySQL</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Host de MySQL</label>
                        <input type="text" name="db_host" class="form-control" value="localhost" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Usuario de MySQL</label>
                        <input type="text" name="db_user" class="form-control" value="root" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Contraseña de MySQL</label>
                        <input type="password" name="db_pass" class="form-control" placeholder="Dejar vacío si no tiene">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nombre de la Base de Datos</label>
                        <input type="text" name="db_name" class="form-control" value="hotel_master" required>
                    </div>
                    
                    <div class="info-box">
                        <p><strong>ℹ️ Nota:</strong> La base de datos se creará automáticamente si no existe.</p>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Probar Conexión y Continuar →</button>
                </form>
                
            <?php elseif ($step == 3): ?>
                <h2>Crear Estructura de Carpetas</h2>
                <p>Se crearán las carpetas necesarias para el sistema</p>
                
                <div class="info-box">
                    <p><strong>📁 Se crearán:</strong> config, models, controllers, helpers, views, public, storage</p>
                </div>
                
                <form method="POST">
                    <button type="submit" class="btn btn-primary">Crear Estructura →</button>
                </form>
                
            <?php elseif ($step == 4): ?>
                <h2>Crear Superusuario</h2>
                <p>Crea el usuario administrador principal del sistema</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Nombre Completo</label>
                        <input type="text" name="super_name" class="form-control" value="Super Administrador" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="super_email" class="form-control" value="super@hotel.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="super_password" class="form-control" placeholder="Mínimo 8 caracteres" required>
                    </div>
                    
                    <button type="submit" class="btn btn-success">Finalizar Instalación ✓</button>
                </form>
                
            <?php elseif ($step == 5): ?>
                <h2>🎉 ¡Instalación Completada!</h2>
                
                <div class="alert alert-success">
                    <p><strong>✅ El sistema se ha instalado correctamente</strong></p>
                </div>
                
                <div class="info-box">
                    <h3>Próximos Pasos:</h3>
                    <ol style="margin-left: 20px; margin-top: 10px; line-height: 1.8;">
                        <li>Elimina o renombra el archivo <code>install.php</code> por seguridad</li>
                        <li>Accede al panel de Super Admin</li>
                        <li>Crea tu primer hotel</li>
                        <li>¡Comienza a usar el sistema!</li>
                    </ol>
                </div>
                
                <div style="margin-top: 30px;">
                    <a href="public/super/login" class="btn btn-success">Ir al Panel Super Admin →</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
