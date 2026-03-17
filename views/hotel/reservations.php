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
        .btn-success {
            padding: 8px 16px;
            background: #10b981;
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
        .status-cancelada { background: #fee2e2; color: #991b1b; }

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

        /* Reservations specific styles */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .filters-bar {
            display: flex;
            gap: 10px;
            padding: 20px;
            border-bottom: 2px solid #e5e7eb;
            background: #f9fafb;
            border-radius: 15px 15px 0 0;
        }

        .filter-btn {
            padding: 10px 20px;
            background: white;
            color: #6b7280;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            border: 2px solid #e5e7eb;
            transition: all 0.2s;
        }

        .filter-btn:hover {
            background: #f3f4f6;
            border-color: #667eea;
            color: #667eea;
        }

        .filter-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .reservation-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 15px;
            overflow: hidden;
            transition: all 0.2s;
        }

        .reservation-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
        }

        .reservation-card.status-ocupada {
            border-color: #10b981;
            background: #f0fdf4;
        }

        .reservation-card.status-reservada {
            border-color: #f59e0b;
            background: #fffbeb;
        }

        .reservation-card.status-finalizada {
            border-color: #6b7280;
            background: #f9fafb;
        }

        .reservation-card.status-cancelada {
            border-color: #ef4444;
            background: #fef2f2;
        }

        .reservation-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .reservation-client {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .reservation-card-body {
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .reservation-detail {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .detail-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: 14px;
            color: #374151;
            font-weight: 600;
        }

        .detail-price {
            font-size: 18px;
            color: #10b981;
            font-weight: 700;
        }

        .reservation-actions {
            display: flex;
            gap: 10px;
            padding: 20px;
            border-top: 2px solid #e5e7eb;
            background: #f9fafb;
        }

        .reservation-actions button,
        .reservation-actions a {
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

        .reservation-actions button:hover,
        .reservation-actions a:hover {
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
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

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            overflow-y: auto;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }

        .modal-large {
            max-width: 700px;
        }

        .modal-header {
            padding: 25px 30px;
            border-bottom: 2px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .modal-header h2 {
            margin: 0;
            color: #1f2937;
            font-size: 24px;
            font-weight: 700;
        }

        .btn-close {
            background: none;
            border: none;
            font-size: 32px;
            color: #6b7280;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-body {
            padding: 30px;
            overflow-y: auto;
            flex: 1;
        }

        .huespedes-section {
            margin-top: 20px;
        }

        .huesped-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            position: relative;
        }

        .huesped-card.titular {
            border-color: #10b981;
            background: #f0fdf4;
        }

        .huesped-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .titular-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 6px;
            font-size: 13px;
            text-transform: uppercase;
        }

        .form-group input,
        .form-group select {
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .btn-remove-huesped {
            background: #fee2e2;
            color: #991b1b;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
        }

        .btn-remove-huesped:hover {
            background: #fecaca;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 10px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .info-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 14px;
            color: #374151;
            font-weight: 600;
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
            .reservation-card-body {
                grid-template-columns: 1fr;
            }
            .filters-bar {
                flex-wrap: wrap;
            }
            .page-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            .reservation-actions {
                flex-direction: column;
            }
        }
    </style>

<div class="container">
        <div class="dashboard-header">
            <h1>📅 Reservas</h1>
            <p class="subtitle">Gestiona todas las reservas de tu hotel</p>
        </div>

        <div style="text-align: right; margin-bottom: 30px;">
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <a href="<?= BASE_URL ?>/hotel/reservas/create" class="btn-primary">+ Nueva Reserva</a>
                <a href="<?= BASE_URL ?>/hotel/reservas/walkin" class="btn-success">🚶 Check-in Directo</a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                ✅ Operación realizada exitosamente
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                ❌ Error al procesar la operación. Por favor intenta nuevamente.
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="filters-bar">
                <a href="?filter=all" class="filter-btn <?= ($filter ?? 'all') === 'all' ? 'active' : '' ?>">
                    📋 Todas
                </a>
                <a href="?filter=active" class="filter-btn <?= ($filter ?? '') === 'active' ? 'active' : '' ?>">
                    ✅ Activas
                </a>
                <a href="?filter=finished" class="filter-btn <?= ($filter ?? '') === 'finished' ? 'active' : '' ?>">
                    ✔️ Finalizadas
                </a>
                <a href="?filter=cancelled" class="filter-btn <?= ($filter ?? '') === 'cancelled' ? 'active' : '' ?>">
                    ❌ Canceladas
                </a>
            </div>

            <?php if (empty($reservations)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📅</div>
                    <h3>No hay reservas</h3>
                    <p>Comienza creando una nueva reserva para gestionar tu hotel</p>
                </div>
            <?php else: ?>
                <div style="padding: 20px;">
                    <?php foreach ($reservations as $reserva): ?>
                        <div class="reservation-card status-<?= $reserva['estado'] ?>">
                            <div class="reservation-card-header">
                                <div>
                                    <div class="reservation-client"><?= htmlspecialchars($reserva['cliente_nombre']) ?></div>
                                    <small>📅 Reserva #<?= $reserva['id'] ?></small>
                                </div>
                                <span class="status-badge status-<?= $reserva['estado'] ?>">
                                    <?= ucfirst($reserva['estado']) ?>
                                </span>
                            </div>

                            <div class="reservation-card-body">
                                <div class="reservation-detail">
                                    <div class="detail-label">Cliente</div>
                                    <div style="color: #374151;">
                                        <strong><?= htmlspecialchars($reserva['cliente_nombre']) ?></strong><br>
                                        <small style="color: #6b7280;">📧 <?= htmlspecialchars($reserva['cliente_email']) ?></small><br>
                                        <small style="color: #6b7280;">📱 <?= htmlspecialchars($reserva['cliente_telefono']) ?></small>
                                    </div>
                                </div>

                                <div class="reservation-detail">
                                    <div class="detail-label">Habitación</div>
                                    <div class="detail-value">
                                        🏠 #<?= htmlspecialchars($reserva['numero_habitacion']) ?><br>
                                        <small style="color: #6b7280;"><?= htmlspecialchars($reserva['tipo_habitacion']) ?></small>
                                    </div>
                                </div>

                                <div class="reservation-detail">
                                    <div class="detail-label">Check-in</div>
                                    <div class="detail-value">
                                        <?= date('d/m/Y', strtotime($reserva['fecha_entrada'])) ?><br>
                                        <small style="color: #6b7280;">⏰ <?= isset($reserva['hora_entrada']) ? date('g:i A', strtotime($reserva['hora_entrada'])) : '3:00 PM' ?></small>
                                    </div>
                                </div>

                                <div class="reservation-detail">
                                    <div class="detail-label">Check-out</div>
                                    <div class="detail-value">
                                        <?= date('d/m/Y', strtotime($reserva['fecha_salida'])) ?><br>
                                        <small style="color: #6b7280;">⏰ <?= isset($reserva['hora_salida']) ? date('g:i A', strtotime($reserva['hora_salida'])) : '12:00 PM' ?></small>
                                    </div>
                                </div>

                                <div class="reservation-detail">
                                    <div class="detail-label">Huéspedes</div>
                                    <div class="detail-value">👥 <?= $reserva['numero_huespedes'] ?> personas</div>
                                </div>

                                <div class="reservation-detail">
                                    <div class="detail-label">Total</div>
                                    <div class="detail-price">S/ <?= number_format($reserva['precio_total'], 2) ?></div>
                                </div>
                            </div>

                            <div class="reservation-actions">
                                <?php if ($reserva['estado'] === 'reservada'): ?>
                                    <a href="<?= BASE_URL ?>/hotel/reservas/edit?id=<?= $reserva['id'] ?>"
                                       style="background: #eef2ff; color: #3730a3; text-decoration: none;">
                                        ✏️ Editar
                                    </a>
                                    <button onclick="mostrarCheckinModal(<?= htmlspecialchars(json_encode($reserva)) ?>)"
                                            style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                                        ✅ Check-in
                                    </button>
                                <?php endif; ?>

                                <?php if ($reserva['estado'] === 'ocupada'): ?>
                                    <button onclick="abrirCheckoutModal(<?= $reserva['id'] ?>)"
                                            style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white;">
                                        🏁 Check-out
                                    </button>
                                <?php endif; ?>

                                <?php if ($reserva['estado'] === 'finalizada'): ?>
                                    <a href="<?= BASE_URL ?>/hotel/reservas/recibo?id=<?= $reserva['id'] ?>"
                                       style="background: #dcfce7; color: #166534; text-decoration: none;">
                                        🧾 Ver Recibo
                                    </a>
                                <?php endif; ?>

                                <?php if (in_array($reserva['estado'], ['reservada', 'ocupada'])): ?>
                                    <button onclick="cambiarEstado(<?= $reserva['id'] ?>, 'cancelada')"
                                            style="background: #fee2e2; color: #991b1b;">
                                        ❌ Cancelar
                                    </button>
                                <?php endif; ?>

                                <a href="<?= BASE_URL ?>/hotel/clientes/view?id=<?= $reserva['cliente_id'] ?>" style="background: #dbeafe; color: #0047ab; text-decoration: none;">
                                    👁️ Ver Cliente
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de Check-in con Huéspedes -->
    <div id="checkinModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2>✅ Check-in - Registro de Huéspedes</h2>
                <button onclick="cerrarModalCheckin()" class="btn-close">×</button>
            </div>
            <div class="modal-body">
                <form id="checkinForm" onsubmit="guardarCheckin(event)">
                    <input type="hidden" name="reserva_id" id="reservaId">

                    <div class="alert alert-warning" style="margin: 0 0 20px 0;">
                        <strong>📋 Información Importante:</strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <li>Solicitar documento de identidad de TODOS los huéspedes</li>
                            <li>El primer huésped registrado será el titular</li>
                            <li>Puedes agregar más huéspedes si es necesario</li>
                            <li>Verifica que los datos sean correctos</li>
                        </ul>
                    </div>

                    <div class="info-grid" id="reservaSummary">
                        <!-- Se llenará con JavaScript -->
                    </div>

                    <div class="huespedes-section">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 style="margin: 0; color: #1f2937; font-size: 18px; font-weight: 700;">👥 Registro de Huéspedes</h3>
                            <button type="button" onclick="agregarHuesped()" style="padding: 10px 16px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 13px;">
                                + Agregar Huésped
                            </button>
                        </div>

                        <div id="huespedesContainer">
                            <!-- Se llenarán los huéspedes con JavaScript -->
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; margin-top: 25px; padding-top: 25px; border-top: 2px solid #e5e7eb;">
                        <button type="submit" style="flex: 1; padding: 14px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; font-size: 14px;">
                            ✅ Confirmar Check-in
                        </button>
                        <button type="button" onclick="cerrarModalCheckin()" style="flex: 1; padding: 14px; background: #e5e7eb; color: #374151; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 14px;">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="checkoutModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>🏁 Confirmar Check-out</h2>
                <button onclick="cerrarCheckoutModal()" class="btn-close">×</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 16px; color: #374151;">Selecciona el metodo de pago para cerrar la reserva y generar el recibo.</p>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="metodoPagoCheckout">Metodo de pago</label>
                    <select id="metodoPagoCheckout" class="form-control">
                        <option value="efectivo">Efectivo</option>
                        <option value="yape">Yape</option>
                        <option value="plin">Plin</option>
                        <option value="tarjeta_debito">Tarjeta de debito</option>
                        <option value="tarjeta_credito">Tarjeta de credito</option>
                        <option value="transferencia_bancaria">Transferencia bancaria</option>
                        <option value="deposito_bancario">Deposito bancario</option>
                        <option value="billetera_digital">Billetera digital</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="confirmarCheckout()" style="flex: 1; padding: 12px; border: none; border-radius: 8px; font-weight: 700; background: #2563eb; color: white; cursor: pointer;">Confirmar y emitir recibo</button>
                    <button type="button" onclick="cerrarCheckoutModal()" style="flex: 1; padding: 12px; border: none; border-radius: 8px; font-weight: 700; background: #e5e7eb; color: #111827; cursor: pointer;">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <form id="statusForm" method="POST" action="<?= BASE_URL ?>/hotel/reservas/update-status" style="display: none;">
        <input type="hidden" name="reservation_id" id="reservationId">
        <input type="hidden" name="nuevo_estado" id="nuevoEstado">
        <input type="hidden" name="metodo_pago" id="metodoPagoHidden" value="efectivo">
    </form>

    <script>
        function mostrarCheckinModal(reserva) {
            document.getElementById('checkinModal').style.display = 'flex';
            document.getElementById('reservaId').value = reserva.id;

            // Mostrar resumen de la reserva
            const summary = `
                <div class="info-item">
                    <div class="info-label">Cliente</div>
                    <div class="info-value">${reserva.cliente_nombre}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Habitación</div>
                    <div class="info-value">#${reserva.numero_habitacion} - ${reserva.tipo_habitacion}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Período</div>
                    <div class="info-value">${new Date(reserva.fecha_entrada).toLocaleDateString('es-PE')} - ${new Date(reserva.fecha_salida).toLocaleDateString('es-PE')}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Total a Pagar</div>
                    <div class="info-value" style="color: #10b981; font-size: 18px;">S/ ${parseFloat(reserva.precio_total).toFixed(2)}</div>
                </div>
            `;
            document.getElementById('reservaSummary').innerHTML = summary;

            // Inicializar huéspedes: el cliente titular se carga como primer huésped.
            document.getElementById('huespedesContainer').innerHTML = '';
            agregarHuesped(true, {
                nombre: reserva.cliente_nombre || '',
                documento: reserva.cliente_documento || '',
                tipo_documento: detectarTipoDocumento(reserva.cliente_documento || '')
            });
        }

        function cerrarModalCheckin() {
            document.getElementById('checkinModal').style.display = 'none';
        }

        function detectarTipoDocumento(documento = '') {
            const doc = String(documento).trim();
            if (/^\d{8}$/.test(doc)) return 'DNI';
            if (doc.length >= 6 && /[A-Za-z0-9]/.test(doc)) return 'Pasaporte';
            return 'Otro';
        }

        function agregarHuesped(esTitular = false, datosIniciales = null) {
            const container = document.getElementById('huespedesContainer');
            const nombre = datosIniciales?.nombre || '';
            const documento = datosIniciales?.documento || '';
            const tipoDocumento = datosIniciales?.tipo_documento || 'DNI';

            const card = document.createElement('div');
            card.className = 'huesped-card ' + (esTitular ? 'titular' : '');
            card.innerHTML = `
                <div class="huesped-header">
                    <h4 style="margin: 0; color: #1f2937;">👤 Huésped ${container.children.length + 1}</h4>
                    ${esTitular ? '<span class="titular-badge">TITULAR</span>' : '<button type="button" class="btn-remove-huesped" onclick="this.parentElement.parentElement.remove()">Eliminar</button>'}
                </div>
                <div class="form-group">
                    <label>Nombre Completo*</label>
                    <input type="text" name="huesped_nombre[]" required placeholder="Ej: Juan Pérez García" value="${nombre.replace(/"/g, '&quot;')}">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Documento*</label>
                        <input type="text" name="huesped_documento[]" required placeholder="DNI/Pasaporte" value="${documento.replace(/"/g, '&quot;')}">
                    </div>
                    <div class="form-group">
                        <label>Tipo Documento</label>
                        <select name="huesped_tipo_documento[]">
                            <option value="DNI" ${tipoDocumento === 'DNI' ? 'selected' : ''}>DNI</option>
                            <option value="Pasaporte" ${tipoDocumento === 'Pasaporte' ? 'selected' : ''}>Pasaporte</option>
                            <option value="Otro" ${tipoDocumento === 'Otro' ? 'selected' : ''}>Otro</option>
                        </select>
                    </div>
                </div>
            `;
            container.appendChild(card);
        }

        async function guardarCheckin(event) {
            event.preventDefault();

            const form = document.getElementById('checkinForm');
            const reservaId = document.getElementById('reservaId').value;
            const nombres = Array.from(form.querySelectorAll('input[name="huesped_nombre[]"]')).map(el => el.value.trim());
            const documentos = Array.from(form.querySelectorAll('input[name="huesped_documento[]"]')).map(el => el.value.trim());
            const tipos = Array.from(form.querySelectorAll('select[name="huesped_tipo_documento[]"]')).map(el => el.value);

            if (!reservaId) {
                alert('No se encontro la reserva para check-in.');
                return;
            }

            const hasInvalidGuest = nombres.some((nombre, index) => nombre === '' || (documentos[index] ?? '') === '');
            if (hasInvalidGuest || nombres.length === 0) {
                alert('Completa nombre y documento para todos los huespedes.');
                return;
            }

            const payload = new URLSearchParams();
            payload.append('reserva_id', reservaId);
            payload.append('ajax', '1');

            nombres.forEach((nombre, index) => {
                payload.append(`huespedes[${index}][nombre]`, nombre);
                payload.append(`huespedes[${index}][documento]`, documentos[index] ?? '');
                payload.append(`huespedes[${index}][tipo_documento]`, tipos[index] ?? 'DNI');
            });

            try {
                const response = await fetch('<?= BASE_URL ?>/hotel/huespedes/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: payload.toString()
                });

                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'No se pudo registrar el check-in');
                }

                alert('Check-in registrado correctamente');
                window.location.reload();
            } catch (error) {
                alert('Error en check-in: ' + error.message);
            }
        }

        function abrirCheckoutModal(reservaId) {
            document.getElementById('reservationId').value = reservaId;
            document.getElementById('checkoutModal').style.display = 'flex';
        }

        function cerrarCheckoutModal() {
            document.getElementById('checkoutModal').style.display = 'none';
        }

        function confirmarCheckout() {
            const metodoPago = document.getElementById('metodoPagoCheckout').value;
            document.getElementById('nuevoEstado').value = 'finalizada';
            document.getElementById('metodoPagoHidden').value = metodoPago || 'efectivo';
            document.getElementById('statusForm').submit();
        }

        function cambiarEstado(reservaId, nuevoEstado) {
            document.getElementById('reservationId').value = reservaId;
            document.getElementById('nuevoEstado').value = nuevoEstado;
            if (nuevoEstado !== 'finalizada') {
                document.getElementById('metodoPagoHidden').value = 'efectivo';
            }
            document.getElementById('statusForm').submit();
        }

        // Cerrar modal al hacer click fuera
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('checkinModal');
            if (event.target === modal) {
                cerrarModalCheckin();
            }

            const checkoutModal = document.getElementById('checkoutModal');
            if (event.target === checkoutModal) {
                cerrarCheckoutModal();
            }
        });
    </script>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
