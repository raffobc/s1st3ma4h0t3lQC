<?php
/**
 * CREAR VISTAS DEL SISTEMA HOTEL - VERSION CORREGIDA
 * Archivo: C:\xampp\htdocs\hotel-system\create_views_fix.php
 */

$baseDir = __DIR__;
$created = array();

// Crear views/hotel/clients.php
$clientsView = <<<'EOD'
<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1>👥 Clientes</h1>
        <p class="subtitle">Gestiona la base de datos de clientes</p>
    </div>
    <a href="/hotel/clientes/create" class="btn-primary">+ Nuevo Cliente</a>
</div>

<div class="card">
    <div class="search-bar">
        <form method="GET" action="">
            <div class="search-input-group">
                <input type="text" name="search" class="search-input" 
                       placeholder="🔍 Buscar por nombre, documento o email..." 
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <button type="submit" class="btn-search">Buscar</button>
            </div>
        </form>
    </div>
    
    <?php if (empty($clients)): ?>
        <div class="empty-state">
            <div class="empty-icon">👥</div>
            <h3>No se encontraron clientes</h3>
            <p><?= isset($_GET['search']) ? 'Intenta con otro término de búsqueda' : 'Comienza registrando un nuevo cliente' ?></p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Documento</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Total Reservas</th>
                        <th>Total Gastado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><strong>#<?= $client['id'] ?></strong></td>
                            <td><?= htmlspecialchars($client['nombre']) ?></td>
                            <td><?= htmlspecialchars($client['documento']) ?></td>
                            <td><?= htmlspecialchars($client['email']) ?></td>
                            <td><?= htmlspecialchars($client['telefono']) ?></td>
                            <td><span class="badge-count"><?= $client['total_reservas'] ?> 📅</span></td>
                            <td><strong>S/ <?= number_format($client['total_gastado'], 2) ?></strong></td>
                            <td>
                                <a href="/hotel/clientes/view?id=<?= $client['id'] ?>" 
                                   class="btn-action btn-primary" title="Ver Detalles">👁️</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
EOD;

file_put_contents($baseDir . "/views/hotel/clients.php", $clientsView);
$created[] = "views/hotel/clients.php";

// Crear views/hotel/client_detail.php
$clientDetailView = <<<'EOD'
<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<div class="page-header">
    <div>
        <h1>👤 <?= htmlspecialchars($client['nombre']) ?></h1>
        <p class="subtitle">Perfil completo del cliente</p>
    </div>
    <a href="/hotel/clientes" class="btn-secondary">← Volver</a>
</div>

<div class="content-grid">
    <div>
        <div class="card" style="margin-bottom: 20px;">
            <h3 class="card-title">📋 Información Personal</h3>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Documento</div>
                    <div class="info-value"><?= htmlspecialchars($client['documento']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?= htmlspecialchars($client['email']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Teléfono</div>
                    <div class="info-value"><?= htmlspecialchars($client['telefono']) ?></div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h3 class="card-title">📅 Historial de Reservas</h3>
            
            <?php if (empty($reservations)): ?>
                <div class="empty-state-small">
                    <p>Este cliente no tiene reservas registradas</p>
                </div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($reservations as $reserva): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker status-<?= $reserva['estado'] ?>"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="room-badge">🏠 <?= htmlspecialchars($reserva['numero_habitacion']) ?></span>
                                    <span class="status-badge status-<?= $reserva['estado'] ?>">
                                        <?= ucfirst($reserva['estado']) ?>
                                    </span>
                                </div>
                                <div class="timeline-body">
                                    <div class="timeline-dates">
                                        📅 <?= date('d/m/Y', strtotime($reserva['fecha_entrada'])) ?> 
                                        → <?= date('d/m/Y', strtotime($reserva['fecha_salida'])) ?>
                                    </div>
                                    <div class="timeline-price">
                                        💰 S/ <?= number_format($reserva['precio_total'], 2) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
EOD;

file_put_contents($baseDir . "/views/hotel/client_detail.php", $clientDetailView);
$created[] = "views/hotel/client_detail.php";

// Agregar CSS extendido
$cssExtended = <<<'EOD'

/* EXTENDED STYLES */

.search-bar {
    padding: 20px;
    background: #f9fafb;
    border-bottom: 2px solid #e5e7eb;
}

.search-input-group {
    display: flex;
    gap: 10px;
}

.search-input {
    flex: 1;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 15px;
}

.btn-search {
    padding: 12px 24px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.badge-count {
    background: #f3f4f6;
    color: #374151;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.info-item {
    padding: 15px;
    background: #f9fafb;
    border-radius: 8px;
}

.info-label {
    font-size: 12px;
    color: #6b7280;
    font-weight: 600;
    margin-bottom: 5px;
    text-transform: uppercase;
}

.info-value {
    font-size: 16px;
    color: #1f2937;
    font-weight: 600;
}

.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline::before {
    content: "";
    position: absolute;
    left: 20px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e5e7eb;
}

.timeline-item {
    position: relative;
    padding-left: 50px;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: 12px;
    top: 5px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 0 0 2px #e5e7eb;
}

.timeline-marker.status-reservada { background: #f59e0b; }
.timeline-marker.status-ocupada { background: #10b981; }
.timeline-marker.status-finalizada { background: #6b7280; }
.timeline-marker.status-cancelada { background: #ef4444; }

.timeline-content {
    background: #f9fafb;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #667eea;
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.timeline-body {
    font-size: 14px;
}

.timeline-dates {
    color: #6b7280;
    margin-bottom: 5px;
}

.timeline-price {
    color: #059669;
    font-weight: 700;
}

.empty-state-small {
    text-align: center;
    padding: 40px 20px;
    color: #6b7280;
}
EOD;

$cssFile = $baseDir . "/public/css/hotel-admin.css";
if (file_exists($cssFile)) {
    $currentCSS = file_get_contents($cssFile);
    file_put_contents($cssFile, $currentCSS . $cssExtended);
    $created[] = "public/css/hotel-admin.css (actualizado)";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>✅ Vistas Creadas Exitosamente</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            margin: 0;
        }
        .container {
            max-width: 800px;
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
            margin-bottom: 30px;
        }
        .success {
            background: #dcfce7;
            border-left: 4px solid #10b981;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .file-list {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .file-item {
            padding: 10px;
            background: white;
            margin: 8px 0;
            border-radius: 6px;
            border-left: 3px solid #667eea;
        }
        .btn-link {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin: 10px;
        }
        .btn-link:hover {
            background: #5568d3;
        }
        .text-center {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ ¡Vistas Creadas Exitosamente!</h1>
        
        <div class="success">
            <strong>✨ Perfecto!</strong> Se han creado <?= count($created) ?> archivos correctamente.
        </div>
        
        <h3>📁 Archivos Creados:</h3>
        <div class="file-list">
            <?php foreach ($created as $file): ?>
                <div class="file-item">✓ <?= htmlspecialchars($file) ?></div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center">
            <a href="/hotel/login" class="btn-link">
                🏨 Ir al Login de Hotel
            </a>
            <a href="/hotel/dashboard" class="btn-link">
                📊 Ver Dashboard
            </a>
        </div>
    </div>
</body>
</html>
