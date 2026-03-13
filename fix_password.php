<?php
/**
 * ARREGLAR CONTRASEÑA DEL SUPERUSUARIO
 * Coloca este archivo en: C:\xampp\htdocs\hotel-system\fix_password.php
 * Accede a: http://localhost/hotel-system/fix_password.php
 */

$updated = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=hotel_master", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $email = $_POST['email'];
        $newPassword = $_POST['password'];
        
        // Generar nuevo hash
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Actualizar en la base de datos
        $stmt = $pdo->prepare("UPDATE super_usuarios SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);
        
        if ($stmt->rowCount() > 0) {
            $updated = true;
        } else {
            $error = "No se encontró el usuario con ese email";
        }
        
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Verificar usuarios existentes
try {
    $pdo = new PDO("mysql:host=localhost;dbname=hotel_master", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("SELECT id, nombre, email FROM super_usuarios");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arreglar Contraseña</title>
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
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 28px;
        }
        
        .success-box {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .error-box {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            color: #991b1b;
        }
        
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 14px;
        }
        
        .users-list {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .user-item {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
        }
        
        .user-item:last-child {
            border-bottom: none;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
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
        
        .btn-secondary {
            background: #6b7280;
            margin-top: 10px;
        }
        
        small {
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Arreglar Contraseña</h1>
        
        <?php if ($updated): ?>
            <div class="success-box">
                <strong>✅ ¡Contraseña actualizada exitosamente!</strong><br><br>
                Ahora puedes iniciar sesión con:<br>
                Email: <strong><?= htmlspecialchars($_POST['email']) ?></strong><br>
                Password: <strong><?= htmlspecialchars($_POST['password']) ?></strong>
            </div>
            <a href="public/super/login" class="btn">Ir al Login →</a>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error-box">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$updated): ?>
            <div class="info-box">
                <strong>ℹ️ ¿Qué hace este script?</strong><br>
                Actualiza la contraseña del superusuario para que puedas iniciar sesión.
            </div>
            
            <?php if (!empty($users)): ?>
                <h3 style="margin: 20px 0 10px; color: #374151;">Usuarios en la base de datos:</h3>
                <div class="users-list">
                    <?php foreach ($users as $user): ?>
                        <div class="user-item">
                            <div>
                                <strong><?= htmlspecialchars($user['nombre']) ?></strong><br>
                                <small><?= htmlspecialchars($user['email']) ?></small>
                            </div>
                            <div>
                                <small>ID: <?= $user['id'] ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <h3 style="margin: 20px 0 10px; color: #374151;">Actualizar Contraseña:</h3>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Email del Superusuario *</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?= !empty($users) ? htmlspecialchars($users[0]['email']) : 'super@hotel.com' ?>" 
                           required>
                    <small>El email que usarás para iniciar sesión</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nueva Contraseña *</label>
                    <input type="text" name="password" class="form-control" 
                           value="Admin123!" 
                           required>
                    <small>⚠️ Usa exactamente esta contraseña o cámbiala por una que recuerdes</small>
                </div>
                
                <button type="submit" class="btn">
                    🔐 Actualizar Contraseña
                </button>
            </form>
            
            <div style="margin-top: 20px; padding: 15px; background: #fef3c7; border-radius: 8px;">
                <strong>💡 Tip:</strong> Después de actualizar, usa:<br>
                Email: <code>super@hotel.com</code><br>
                Password: <code>Admin123!</code>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
