<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
        .navbar-brand { display: flex; align-items: center; gap: 12px; }
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
        .navbar-brand-text { font-weight: 700; font-size: 18px; color: #1f2937; }
        .navbar-hotel { font-size: 12px; color: #6b7280; }
        .navbar-menu { display: flex; gap: 25px; align-items: center; }
        .nav-link {
            color: #6b7280;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s;
            font-size: 14px;
        }
        .nav-link:hover { background: #f3f4f6; color: #667eea; }
        .nav-link.active { background: #667eea; color: white; }
        .navbar-user { display: flex; align-items: center; gap: 15px; }
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
            max-width: 1500px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .dashboard-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { font-size: 32px; color: #1f2937; margin-bottom: 10px; }
        .subtitle { color: #6b7280; }

        .toolbar {
            background: white;
            border-radius: 15px;
            padding: 16px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-nav {
            text-decoration: none;
            color: #334155;
            background: #e2e8f0;
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
        }
        .month-title { font-weight: 700; color: #1f2937; }

        .legend {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .legend-item { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #475569; }
        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }
        .dot-reservada { background: #f59e0b; }
        .dot-ocupada { background: #ef4444; }
        .dot-finalizada { background: #10b981; }

        .calendar-wrap {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: auto;
        }
        .calendar-table {
            width: max-content;
            min-width: 100%;
            border-collapse: collapse;
        }
        .calendar-table th,
        .calendar-table td {
            border-bottom: 1px solid #e5e7eb;
            border-right: 1px solid #f1f5f9;
            text-align: center;
            padding: 10px 8px;
            font-size: 12px;
        }
        .calendar-table th {
            position: sticky;
            top: 0;
            background: #f8fafc;
            z-index: 2;
            color: #334155;
            font-weight: 700;
        }
        .room-col {
            position: sticky;
            left: 0;
            z-index: 1;
            background: white;
            text-align: left !important;
            min-width: 190px;
            max-width: 190px;
            color: #1f2937;
            font-weight: 600;
        }
        .cell {
            min-width: 32px;
            height: 36px;
        }
        .cell-empty { background: #f8fafc; }
        .cell-reservada { background: #fef3c7; }
        .cell-ocupada { background: #fee2e2; }
        .cell-finalizada { background: #dcfce7; }

        .status-pill {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 999px;
            color: #334155;
            background: rgba(255,255,255,0.8);
        }

        @media (max-width: 768px) {
            .navbar-menu { display: none; }
            .container { padding: 0 10px; }
            .dashboard-header { padding: 20px; }
            h1 { font-size: 24px; }
        }
    </style>

<div class="container">
        <div class="dashboard-header">
            <h1>Calendario de Ocupación</h1>
            <p class="subtitle">Vista mensual por habitación con estado de reservas y ocupaciones</p>
        </div>

        <div class="toolbar">
            <div style="display:flex; gap:8px; align-items:center;">
                <a class="btn-nav" href="<?= BASE_URL ?>/hotel/calendario?ym=<?= htmlspecialchars($prevYm) ?>">← Mes anterior</a>
                <div class="month-title"><?= htmlspecialchars($monthStart->format('F Y')) ?></div>
                <a class="btn-nav" href="<?= BASE_URL ?>/hotel/calendario?ym=<?= htmlspecialchars($nextYm) ?>">Mes siguiente →</a>
            </div>

            <div class="legend">
                <div class="legend-item"><span class="legend-dot dot-reservada"></span>Reservada</div>
                <div class="legend-item"><span class="legend-dot dot-ocupada"></span>Ocupada</div>
                <div class="legend-item"><span class="legend-dot dot-finalizada"></span>Finalizada</div>
            </div>
        </div>

        <div class="calendar-wrap">
            <table class="calendar-table">
                <thead>
                    <tr>
                        <th class="room-col">Habitación</th>
                        <?php foreach ($days as $day): ?>
                            <th>
                                <div><?= htmlspecialchars($day['day']) ?></div>
                                <small><?= htmlspecialchars($day['dow']) ?></small>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td class="room-col">
                                #<?= htmlspecialchars($room['numero_habitacion']) ?><br>
                                <small style="color:#6b7280;"><?= htmlspecialchars($room['tipo']) ?></small>
                            </td>
                            <?php foreach ($days as $day): ?>
                                <?php
                                $dayData = $calendarMap[$room['id']][$day['date']] ?? null;
                                $cellClass = 'cell-empty';
                                if ($dayData) {
                                    if ($dayData['status'] === 'ocupada') {
                                        $cellClass = 'cell-ocupada';
                                    } elseif ($dayData['status'] === 'reservada') {
                                        $cellClass = 'cell-reservada';
                                    } else {
                                        $cellClass = 'cell-finalizada';
                                    }
                                }
                                ?>
                                <td class="cell <?= $cellClass ?>" title="<?= $dayData ? ('Reserva #' . $dayData['reservation_id'] . ' - ' . $dayData['client_name'] . ' (' . $dayData['status'] . ')') : 'Sin ocupación' ?>">
                                    <?php if ($dayData): ?>
                                        <span class="status-pill"><?= strtoupper(substr($dayData['status'], 0, 1)) ?></span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
