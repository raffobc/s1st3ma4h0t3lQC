<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

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

        /* Rooms specific styles */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .grid-content {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .room-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s;
        }

        .room-card:hover {
            transform: translateY(-5px);
        }

        .room-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .room-number {
            font-size: 24px;
            font-weight: 700;
        }

        .room-card-body {
            padding: 20px;
        }

        .room-type {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 15px;
        }

        .room-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .room-info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .room-info-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .room-info-value {
            font-size: 14px;
            color: #374151;
            font-weight: 600;
        }

        .room-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .room-actions a, .room-actions button {
            flex: 1;
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
        }

        .room-actions a:hover, .room-actions button:hover {
            transform: translateY(-2px);
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            .navbar-menu {
                display: none;
            }
            .grid-content {
                grid-template-columns: 1fr;
            }
            .room-info {
                grid-template-columns: 1fr;
            }
            .page-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
        }
    </style>

<div class="container">
        <div class="dashboard-header">
            <h1>🏠 Habitaciones</h1>
            <p class="subtitle">Gestiona todas las habitaciones de tu hotel</p>
        </div>

        <div style="text-align: right; margin-bottom: 30px;">
            <a href="<?= BASE_URL ?>/hotel/habitaciones/create" class="btn-primary">+ Nueva Habitación</a>
        </div>

        <?php if (empty($rooms)): ?>
            <div class="card">
                <div class="empty-state">
                    <div class="empty-icon">🏠</div>
                    <h3>No hay habitaciones registradas</h3>
                    <p>Comienza creando tu primera habitación para gestionar tu hotel</p>
                </div>
            </div>
        <?php else: ?>
            <div class="grid-content">
                <?php foreach ($rooms as $room): ?>
                    <div class="room-card">
                        <div class="room-card-header">
                            <div class="room-number">#<?= htmlspecialchars($room["numero_habitacion"]) ?></div>
                            <span class="status-badge status-<?= $room["estado"] ?>">
                                <?= ucfirst($room["estado"]) ?>
                            </span>
                        </div>

                        <div class="room-card-body">
                            <div class="room-type"><?= htmlspecialchars($room["tipo"]) ?></div>

                            <div class="room-info">
                                <div class="room-info-item">
                                    <div class="room-info-label">Capacidad</div>
                                    <div class="room-info-value">👥 <?= $room["capacidad"] ?> personas</div>
                                </div>
                                <div class="room-info-item">
                                    <div class="room-info-label">Tarifa</div>
                                    <div class="room-info-value">S/ <?= number_format($room["precio_noche"], 2) ?></div>
                                </div>
                            </div>

                            <?php if ($room["descripcion"]): ?>
                                <p style="font-size: 13px; color: #6b7280; line-height: 1.5; margin: 15px 0;">
                                    <?= htmlspecialchars($room["descripcion"]) ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($room["reservas_activas"] > 0): ?>
                                <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e; padding: 10px 12px; border-radius: 8px; font-size: 13px; margin-top: 10px; font-weight: 600;">
                                    ⚠️ <?= $room["reservas_activas"] ?> reserva(s) activa(s)
                                </div>
                            <?php endif; ?>

                            <div class="room-actions">
                                <a href="<?= BASE_URL ?>/hotel/habitaciones/edit?id=<?= $room["id"] ?>" style="background: linear-gradient(135deg, #667eea 0%, #5568d3 100%); color: white;">
                                    ✏️ Editar
                                </a>
                                <form method="POST" action="<?= BASE_URL ?>/hotel/habitaciones/delete" onsubmit="return confirm('¿Eliminar esta habitación?')" style="margin: 0;">
                                    <input type="hidden" name="id" value="<?= (int)$room["id"] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <button type="submit" style="background: #fee2e2; color: #991b1b; border: none; border-radius: 8px; padding: 10px 14px; cursor: pointer; font-weight: 600;">
                                        🗑️ Eliminar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
