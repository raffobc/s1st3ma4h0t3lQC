<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Nuevo Hotel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f7fa; }
        .navbar { background: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-weight: 700; font-size: 20px; color: #667eea; }
        .btn-back { padding: 8px 16px; background: #6b7280; color: white; border: none; border-radius: 6px; text-decoration: none; font-weight: 600; }
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h1 { font-size: 28px; color: #1f2937; margin-bottom: 10px; }
        h2 { font-size: 20px; color: #374151; margin: 25px 0 15px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #374151; font-size: 14px; }
        .form-control { width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; }
        .form-control:focus { outline: none; border-color: #667eea; }
        select.form-control { 
            appearance: none; 
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E"); 
            background-repeat: no-repeat; 
            background-position: right 10px center; 
            background-size: 20px; 
            padding-right: 40px; 
        }
        .btn-primary { width: 100%; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 10px; font-weight: 600; font-size: 16px; cursor: pointer; margin-top: 20px; }
        .btn-primary:hover { transform: translateY(-2px); }
        .info-box { background: #f0f9ff; border-left: 4px solid #3b82f6; padding: 15px; border-radius: 8px; margin: 20px 0; font-size: 14px; }
        small { color: #6b7280; font-size: 12px; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">👑 Super Admin - Crear Hotel</div>
        <a href="<?= BASE_URL ?>/super/dashboard" class="btn-back">← Volver</a>
    </nav>
    
    <div class="container">
        <div class="card">
            <h1>🏨 Crear Nuevo Hotel</h1>
            <p style="color: #6b7280; margin-bottom: 30px;">Complete el formulario para crear un nuevo hotel en el sistema</p>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <h2>📋 Información del Hotel</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nombre del Hotel *</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Hotel Paradise Lima" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Razón Social *</label>
                        <input type="text" name="razon_social" class="form-control" placeholder="Ej: Paradise Hotels SAC" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">RUC *</label>
                        <input type="text" name="ruc" class="form-control" placeholder="20123456789" required maxlength="11">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" name="telefono" class="form-control" placeholder="+51 999 888 777">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="contacto@hotel.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ciudad *</label>
                        <input type="text" name="ciudad" class="form-control" placeholder="Lima" required>
                    </div>
                </div>
                <div class="form-group full">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="direccion" class="form-control" placeholder="Av. Principal 123, Distrito">
                </div>
                
                <h2>⚙️ Configuración del Sistema</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Plan *</label>
                        <select name="plan" class="form-control" required>
                            <option value="basico">Básico - 50 habitaciones</option>
                            <option value="profesional">Profesional - 100 habitaciones</option>
                            <option value="empresarial">Empresarial - Ilimitado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Máximo de Habitaciones</label>
                        <input type="number" name="max_habitaciones" class="form-control" value="50" min="1">
                    </div>
                </div>
                
                <div class="info-box">
                    <strong>ℹ️ Importante:</strong> Se creará automáticamente una base de datos individual para este hotel.
                </div>
                
                <h2>👤 Administrador del Hotel</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nombre Completo *</label>
                        <input type="text" name="admin_nombre" class="form-control" placeholder="Juan Pérez García" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="admin_email" class="form-control" placeholder="admin@hotel.com" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" name="admin_telefono" class="form-control" placeholder="+51 999 888 777">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contraseña *</label>
                        <input type="password" name="admin_password" class="form-control" value="Admin123!" required>
                        <small>Por defecto: Admin123!</small>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">✓ Crear Hotel y Base de Datos</button>
            </form>
        </div>
    </div>
</body>
</html>
