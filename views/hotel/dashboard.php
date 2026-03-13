<!DOCTYPE html>
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

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            .navbar-menu {
                display: none;
            }
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
            <a href="<?= BASE_URL ?>/hotel/dashboard" class="nav-link active">Dashboard</a>
            <a href="<?= BASE_URL ?>/hotel/habitaciones" class="nav-link">Habitaciones</a>
            <a href="<?= BASE_URL ?>/hotel/reservas" class="nav-link">Reservas</a>
            <a href="<?= BASE_URL ?>/hotel/clientes" class="nav-link">Clientes</a>
            <a href="<?= BASE_URL ?>/hotel/calendario" class="nav-link">Calendario</a>
        </div>

        <div class="navbar-user">
            <span style="font-weight: 600;"><?= htmlspecialchars($_SESSION["hotel_user_name"]) ?></span>
            <a href="<?= BASE_URL ?>/hotel/logout" class="btn-logout">Salir</a>
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
                    <a href="<?= BASE_URL ?>/hotel/reservas" class="btn-primary">Ver Todas</a>
                </div>

                <?php if (empty($recentReservations)): ?>
                    <div class="empty-state">
                        <p>📋 No hay reservas registradas aún</p>
                        <p style="font-size: 12px; margin-top: 10px;">Las reservas aparecerán aquí cuando se creen</p>
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

                <?php if (empty($roomsByStatus)): ?>
                    <div class="empty-state">
                        <p>🏠 No hay habitaciones registradas</p>
                        <p style="font-size: 12px; margin-top: 10px;">Crea habitaciones para comenzar</p>
                    </div>
                <?php else: ?>
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
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
