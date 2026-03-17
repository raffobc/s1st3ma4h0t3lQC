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

        .management-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }
        .kpi-item {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px;
        }
        .kpi-label {
            color: #6b7280;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .kpi-value {
            color: #111827;
            font-size: 22px;
            font-weight: 700;
            line-height: 1.1;
        }
        .kpi-subvalue {
            color: #6b7280;
            font-size: 12px;
            margin-top: 6px;
        }
        .methods-list {
            display: grid;
            gap: 10px;
        }
        .filter-card {
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 18px;
            margin-bottom: 20px;
        }
        .filter-form {
            display: grid;
            grid-template-columns: 1.2fr 1fr 1fr auto;
            gap: 12px;
            align-items: end;
        }
        .filter-group {
            display: grid;
            gap: 6px;
        }
        .filter-label {
            font-size: 12px;
            font-weight: 700;
            color: #374151;
        }
        .filter-input {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            background: #fff;
            color: #111827;
        }
        .filter-help {
            margin-top: 8px;
            color: #6b7280;
            font-size: 12px;
        }
        .method-item {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .method-name {
            font-weight: 700;
            color: #374151;
            text-transform: capitalize;
        }
        .method-meta {
            font-size: 12px;
            color: #6b7280;
            margin-top: 3px;
        }
        .method-total {
            font-weight: 700;
            color: #111827;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            .management-grid {
                grid-template-columns: 1fr;
            }
            .filter-form {
                grid-template-columns: 1fr;
            }
            .navbar-menu {
                display: none;
            }
        }
    </style>

<div class="container">
        <div class="dashboard-header">
            <h1>¡Bienvenido de nuevo! 👋</h1>
            <p class="subtitle">Aquí está un resumen de tu hotel hoy</p>
        </div>

        <div class="filter-card">
            <form method="GET" action="<?= BASE_URL ?>/hotel/dashboard" class="filter-form">
                <div class="filter-group">
                    <label class="filter-label" for="period">Periodo</label>
                    <select name="period" id="period" class="filter-input">
                        <option value="today" <?= $dateFilter["period"] === "today" ? "selected" : "" ?>>Hoy</option>
                        <option value="week" <?= $dateFilter["period"] === "week" ? "selected" : "" ?>>Semana actual</option>
                        <option value="month" <?= $dateFilter["period"] === "month" ? "selected" : "" ?>>Mes actual</option>
                        <option value="custom" <?= $dateFilter["period"] === "custom" ? "selected" : "" ?>>Personalizado</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label" for="from">Desde</label>
                    <input class="filter-input" type="date" id="from" name="from" value="<?= htmlspecialchars($dateFilter["start"]) ?>">
                </div>
                <div class="filter-group">
                    <label class="filter-label" for="to">Hasta</label>
                    <input class="filter-input" type="date" id="to" name="to" value="<?= htmlspecialchars($dateFilter["end"]) ?>">
                </div>
                <button type="submit" class="btn-primary">Aplicar</button>
            </form>
            <p class="filter-help">Periodo aplicado: <strong><?= htmlspecialchars($dateFilter["label"]) ?></strong> (<?= date("d/m/Y", strtotime($dateFilter["start"])) ?> al <?= date("d/m/Y", strtotime($dateFilter["end"])) ?>)</p>
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
                        <div class="stat-label">Ingresos del Periodo</div>
                    </div>
                    <div class="stat-icon success">💰</div>
                </div>
            </div>
        </div>

        <div class="management-grid">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Estadisticas de Gestion (<?= htmlspecialchars($dateFilter["label"]) ?>)</h2>
                </div>
                <div class="kpi-grid">
                    <div class="kpi-item">
                        <div class="kpi-label">Check-ins en periodo</div>
                        <div class="kpi-value"><?= $managementStats["checkins_hoy"] ?></div>
                    </div>
                    <div class="kpi-item">
                        <div class="kpi-label">Check-outs en periodo</div>
                        <div class="kpi-value"><?= $managementStats["checkouts_hoy"] ?></div>
                    </div>
                    <div class="kpi-item">
                        <div class="kpi-label">Reservas del periodo</div>
                        <div class="kpi-value"><?= $managementStats["reservas_mes"] ?></div>
                        <div class="kpi-subvalue">Finalizadas: <?= $managementStats["finalizadas_mes"] ?></div>
                    </div>
                    <div class="kpi-item">
                        <div class="kpi-label">Tasa de cancelacion</div>
                        <div class="kpi-value"><?= $managementStats["tasa_cancelacion_mes"] ?>%</div>
                        <div class="kpi-subvalue">Canceladas: <?= $managementStats["canceladas_mes"] ?></div>
                    </div>
                    <div class="kpi-item">
                        <div class="kpi-label">Ingresos del periodo</div>
                        <div class="kpi-value">S/ <?= number_format($managementStats["ingresos_hoy"], 2) ?></div>
                    </div>
                    <div class="kpi-item">
                        <div class="kpi-label">Ticket promedio del periodo</div>
                        <div class="kpi-value">S/ <?= number_format($managementStats["ticket_promedio_mes"], 2) ?></div>
                    </div>
                    <div class="kpi-item">
                        <div class="kpi-label">Saldo pendiente total</div>
                        <div class="kpi-value">S/ <?= number_format($managementStats["saldo_pendiente_total"], 2) ?></div>
                        <div class="kpi-subvalue">Reservas activas</div>
                    </div>
                    <div class="kpi-item">
                        <div class="kpi-label">Estancia promedio</div>
                        <div class="kpi-value"><?= $managementStats["estancia_promedio"] ?> noches</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Cobros por Metodo (Periodo)</h2>
                </div>
                <?php if (empty($paymentsByMethod)): ?>
                    <div class="empty-state" style="padding: 16px;">
                        <p>No hay pagos registrados en este periodo</p>
                    </div>
                <?php else: ?>
                    <div class="methods-list">
                        <?php foreach ($paymentsByMethod as $method): ?>
                            <div class="method-item">
                                <div>
                                    <div class="method-name"><?= htmlspecialchars($method["metodo_pago"]) ?></div>
                                    <div class="method-meta"><?= (int)$method["cantidad"] ?> pagos</div>
                                </div>
                                <div class="method-total">S/ <?= number_format((float)$method["total"], 2) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
