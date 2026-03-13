<?php
/**
 * ACTUALIZAR DASHBOARD
 * Archivo: C:\xampp\htdocs\hotel-system\update_dashboard.php
 * Accede a: http://localhost/hotel-system/update_dashboard.php
 */

$baseDir = __DIR__;

// Nuevo contenido completo del dashboard
$newDashboard = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Super Admin</title>
    <style>
        * {
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
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 20px;
            color: #667eea;
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
        
        .success-message {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #065f46;
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
        
        .subtitle {
            color: #6b7280;
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
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 14px;
        }
        
        .hotels-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        h2 {
            font-size: 24px;
            color: #1f2937;
        }
        
        .btn-primary {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .hotels-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .hotels-table th {
            background: #f9fafb;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .hotels-table td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-suspended {
            background: #fef3c7;
            color: #92400e;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
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
        <div class="navbar-user">
            <span><?= htmlspecialchars($_SESSION["super_user_name"]) ?></span>
            <a href="/super/logout" class="btn-logout">Salir</a>
        </div>
    </nav>
    
    <div class="container">
        <?php if (isset($success)): ?>
            <div class="success-message">
                ✅ <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <div class="header">
            <h1>Panel de Superadministrador</h1>
            <p class="subtitle">Gestiona todos los hoteles del sistema</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= count($hotels) ?></div>
                <div class="stat-label">Total de Hoteles</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count(array_filter($hotels, fn($h) => $h["estado"] === "activo")) ?></div>
                <div class="stat-label">Hoteles Activos</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count(array_filter($hotels, fn($h) => $h["estado"] === "suspendido")) ?></div>
                <div class="stat-label">Hoteles Suspendidos</div>
            </div>
        </div>
        
        <div class="hotels-section">
            <div class="section-header">
                <h2>Hoteles Registrados</h2>
                <a href="/super/hotels/create" class="btn-primary">➕ Crear Nuevo Hotel</a>
            </div>
            
            <?php if (empty($hotels)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🏨</div>
                    <h3>No hay hoteles registrados</h3>
                    <p>Crea tu primer hotel para comenzar</p>
                    <br>
                    <a href="/super/hotels/create" class="btn-primary">➕ Crear Primer Hotel</a>
                </div>
            <?php else: ?>
                <table class="hotels-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Ciudad</th>
                            <th>Plan</th>
                            <th>Estado</th>
                            <th>Base de Datos</th>
                            <th>Admins</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hotels as $hotel): ?>
                            <tr>
                                <td>#<?= $hotel["id"] ?></td>
                                <td><strong><?= htmlspecialchars($hotel["nombre"]) ?></strong></td>
                                <td><?= htmlspecialchars($hotel["ciudad"]) ?></td>
                                <td><?= ucfirst($hotel["plan"]) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $hotel["estado"] ?>">
                                        <?= ucfirst($hotel["estado"]) ?>
                                    </span>
                                </td>
                                <td><code><?= htmlspecialchars($hotel["db_name"]) ?></code></td>
                                <td><?= $hotel["total_admins"] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>';

// Guardar el nuevo dashboard
$dashboardPath = $baseDir . '/views/super/dashboard.php';

if (file_put_contents($dashboardPath, $newDashboard)) {
    $success = true;
} else {
    $error = "No se pudo actualizar el archivo";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Actualizado</title>
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
            margin-top: 20px;
            font-size: 16px;
        }
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .instructions {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
            font-size: 14px;
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($success)): ?>
            <div class="icon">✅</div>
            <h1>¡Dashboard Actualizado!</h1>
            
            <div class="success-box">
                <strong>El dashboard se actualizó correctamente</strong><br><br>
                Ahora incluye:<br>
                ✓ Botón "Crear Nuevo Hotel"<br>
                ✓ Mensajes de éxito<br>
                ✓ Tabla de hoteles mejorada<br>
                ✓ Estadísticas actualizadas
            </div>
            
            <div class="instructions">
                <strong>📋 Instrucciones:</strong><br><br>
                1. Haz click en el botón de abajo<br>
                2. Presiona <strong>Ctrl + F5</strong> para refrescar (importante)<br>
                3. Verás el botón "➕ Crear Nuevo Hotel"<br>
                4. ¡Haz click y crea tu primer hotel!
            </div>
            
            <a href="public/super/dashboard" class="btn">
                🚀 Ir al Dashboard (Ctrl+F5 para refrescar)
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
