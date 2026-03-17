<?php include BASE_PATH . "/views/hotel/_header.php"; ?>

<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        background: #f5f7fa;
    }

    .container {
        max-width: 1400px;
        margin: 30px auto;
        padding: 0 20px;
    }

    .header-card {
        background: white;
        padding: 24px;
        border-radius: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    h1 {
        font-size: 30px;
        color: #1f2937;
        margin-bottom: 8px;
    }
    .subtitle {
        color: #6b7280;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    .stat-card {
        background: white;
        padding: 22px;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 4px;
    }
    .stat-label {
        color: #6b7280;
        font-size: 13px;
        font-weight: 600;
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
    .btn-primary {
        padding: 10px 14px;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        text-decoration: none;
        font-size: 14px;
    }
    .filter-help {
        margin-top: 8px;
        color: #6b7280;
        font-size: 12px;
    }

    .management-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }
    .card {
        background: white;
        padding: 24px;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .card-title {
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 18px;
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

    .empty-state {
        text-align: center;
        padding: 20px;
        color: #6b7280;
        font-size: 14px;
    }

    @media (max-width: 768px) {
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
    <div class="header-card">
        <h1>Estadisticas</h1>
        <p class="subtitle">Analisis de gestion por periodo</p>
    </div>

    <div class="filter-card">
        <form method="GET" action="<?= BASE_URL ?>/hotel/estadisticas" class="filter-form">
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
            <div class="stat-value">S/ <?= number_format($stats["monthly_revenue"], 2) ?></div>
            <div class="stat-label">Ingresos del periodo</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $managementStats["reservas_mes"] ?></div>
            <div class="stat-label">Reservas del periodo</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $managementStats["tasa_cancelacion_mes"] ?>%</div>
            <div class="stat-label">Tasa de cancelacion</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">S/ <?= number_format($managementStats["saldo_pendiente_total"], 2) ?></div>
            <div class="stat-label">Saldo pendiente activo</div>
        </div>
    </div>

    <div class="management-grid">
        <div class="card">
            <h2 class="card-title">Indicadores de Gestion</h2>
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
                    <div class="kpi-label">Reservas finalizadas</div>
                    <div class="kpi-value"><?= $managementStats["finalizadas_mes"] ?></div>
                </div>
                <div class="kpi-item">
                    <div class="kpi-label">Reservas canceladas</div>
                    <div class="kpi-value"><?= $managementStats["canceladas_mes"] ?></div>
                </div>
                <div class="kpi-item">
                    <div class="kpi-label">Ticket promedio</div>
                    <div class="kpi-value">S/ <?= number_format($managementStats["ticket_promedio_mes"], 2) ?></div>
                </div>
                <div class="kpi-item">
                    <div class="kpi-label">Estancia promedio</div>
                    <div class="kpi-value"><?= $managementStats["estancia_promedio"] ?> noches</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="card-title">Cobros por Metodo</h2>
            <?php if (empty($paymentsByMethod)): ?>
                <div class="empty-state">No hay pagos registrados en este periodo</div>
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
</div>

<?php include BASE_PATH . "/views/hotel/_footer.php"; ?>
