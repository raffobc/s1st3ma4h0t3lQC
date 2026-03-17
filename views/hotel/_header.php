<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_SESSION["hotel_name"] ?? "Hotel Admin" ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/hotel-admin.css">
</head>
<body>
    <?php $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ''; ?>
    <nav class="navbar">
        <div class="navbar-brand">
            <div class="navbar-brand-icon">🏨</div>
            <div>
                <div class="navbar-brand-text"><?= htmlspecialchars($_SESSION["hotel_name"]) ?></div>
                <div class="navbar-hotel">Panel de Administración</div>
            </div>
        </div>
        
        <div class="navbar-menu">
            <a href="<?= BASE_URL ?>/hotel/dashboard" class="nav-link<?= strpos($currentPath, '/hotel/dashboard') !== false ? ' active' : '' ?>">Dashboard</a>
            <a href="<?= BASE_URL ?>/hotel/habitaciones" class="nav-link<?= strpos($currentPath, '/hotel/habitaciones') !== false ? ' active' : '' ?>">Habitaciones</a>
            <a href="<?= BASE_URL ?>/hotel/reservas" class="nav-link<?= strpos($currentPath, '/hotel/reservas') !== false ? ' active' : '' ?>">Reservas</a>
            <a href="<?= BASE_URL ?>/hotel/clientes" class="nav-link<?= strpos($currentPath, '/hotel/clientes') !== false ? ' active' : '' ?>">Clientes</a>
            <a href="<?= BASE_URL ?>/hotel/calendario" class="nav-link<?= strpos($currentPath, '/hotel/calendario') !== false ? ' active' : '' ?>">Calendario</a>
        </div>
        
        <div class="navbar-user">
            <span style="font-weight: 600;"><?= htmlspecialchars($_SESSION["hotel_user_name"]) ?></span>
            <a href="<?= BASE_URL ?>/hotel/password" class="btn-logout" style="background:#2563eb;">Cambiar Clave</a>
            <a href="<?= BASE_URL ?>/hotel/logout" class="btn-logout">Salir</a>
        </div>
    </nav>
    
    <div class="container">
