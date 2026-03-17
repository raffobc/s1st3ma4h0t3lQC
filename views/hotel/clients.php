<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - <?= htmlspecialchars($_SESSION["hotel_name"]) ?></title>
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
        .page-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        h1 {
            font-size: 32px;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #6b7280;
        }
        .btn-primary {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Card */
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        /* Search Bar */
        .search-bar {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        .search-input-group {
            display: flex;
            gap: 10px;
        }
        .search-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn-search, .btn-clear {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        .btn-search {
            background: #667eea;
            color: white;
        }
        .btn-search:hover {
            background: #5568d3;
        }
        .btn-clear {
            background: #e5e7eb;
            color: #374151;
        }
        .btn-clear:hover {
            background: #d1d5db;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        .empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .empty-state h3 {
            font-size: 24px;
            color: #374151;
            margin-bottom: 10px;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .table thead {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }
        .table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #374151;
        }
        .table td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }
        .table .actions {
            display: flex;
            gap: 8px;
        }
        .btn-action-sm {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
            text-decoration: none;
        }
        .btn-action-sm:hover {
            background: #f0f9ff;
            transform: scale(1.1);
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-info {
            background: #dbeafe;
            color: #0047ab;
        }

        /* Code styling */
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }
            .navbar-menu {
                flex-wrap: wrap;
                justify-content: center;
            }
            .page-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            .search-input-group {
                flex-direction: column;
            }
            .table-responsive {
                font-size: 12px;
            }
            .table th, .table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">
            <div class="navbar-brand-icon">🏨</div>
            <div>
                <div class="navbar-brand-text">HotelSys</div>
                <div class="navbar-hotel"><?= htmlspecialchars($_SESSION["hotel_name"]) ?></div>
            </div>
        </div>
        <div class="navbar-menu">
            <a href="<?= BASE_URL ?>/hotel/dashboard" class="nav-link">Dashboard</a>
            <a href="<?= BASE_URL ?>/hotel/habitaciones" class="nav-link">Habitaciones</a>
            <a href="<?= BASE_URL ?>/hotel/reservas" class="nav-link">Reservas</a>
            <a href="<?= BASE_URL ?>/hotel/clientes" class="nav-link active">Clientes</a>
            <a href="<?= BASE_URL ?>/hotel/calendario" class="nav-link">Calendario</a>
        </div>
        <div class="navbar-user">
            <span>👤 <?= htmlspecialchars($_SESSION["hotel_user_name"] ?? 'Usuario') ?></span>
            <a href="<?= BASE_URL ?>/hotel/password" class="btn-logout" style="background:#2563eb;">Cambiar Clave</a>
            <a href="<?= BASE_URL ?>/hotel/logout" class="btn-logout">Cerrar Sesión</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <div>
                <h1>👥 Clientes</h1>
                <p class="subtitle">Gestiona la base de datos de clientes</p>
            </div>
            <a href="<?= BASE_URL ?>/hotel/clientes/create" class="btn-primary">+ Nuevo Cliente</a>
        </div>

        <?php if (isset($_GET['success']) && $_GET['success'] === '1'): ?>
            <div class="alert alert-success">✅ Cliente actualizado exitosamente</div>
        <?php elseif (isset($_GET['success']) && $_GET['success'] === 'created'): ?>
            <div class="alert alert-success">✅ Cliente registrado exitosamente</div>
        <?php elseif (isset($_GET['error']) && $_GET['error'] === 'duplicate'): ?>
            <div class="alert alert-error">❌ Ya existe un cliente con ese documento</div>
        <?php endif; ?>

        <div class="card">
            <div class="search-bar">
                <form method="GET" action="">
                    <div class="search-input-group">
                        <input type="text" name="search" class="search-input"
                               placeholder="🔍 Buscar por nombre, documento o email..."
                               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        <button type="submit" class="btn-search">Buscar</button>
                        <?php if (isset($_GET['search']) && $_GET['search']): ?>
                            <a href="" class="btn-clear">Limpiar</a>
                        <?php endif; ?>
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
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Documento</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Ubicación</th>
                                <th>Reservas</th>
                                <th>Total Gastado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($client['nombre']) ?></strong></td>
                                    <td><code><?= htmlspecialchars($client['documento']) ?></code></td>
                                    <td><small><?= htmlspecialchars($client['email'] ?? '-') ?></small></td>
                                    <td><?= htmlspecialchars($client['telefono'] ?? '-') ?></td>
                                    <td><small><?= htmlspecialchars($client['ciudad'] ?? '-') ?>, <?= htmlspecialchars($client['pais'] ?? '-') ?></small></td>
                                    <td><span class="badge badge-info"><?= $client['total_reservas'] ?? 0 ?></span></td>
                                    <td><strong>S/ <?= number_format($client['total_gastado'] ?? 0, 2) ?></strong></td>
                                    <td class="actions">
                                        <a href="<?= BASE_URL ?>/hotel/clientes/view?id=<?= $client['id'] ?>" class="btn-action-sm" title="Ver Detalles">👁️</a>
                                        <a href="<?= BASE_URL ?>/hotel/clientes/edit?id=<?= $client['id'] ?>" class="btn-action-sm" title="Editar">✏️</a>
                                        <a href="<?= BASE_URL ?>/hotel/clientes/delete?id=<?= $client['id'] ?>" class="btn-action-sm" title="Eliminar" onclick="return confirm('¿Eliminar este cliente?')">🗑️</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
