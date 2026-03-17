<?php
$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
require_once "../config/config.php";
require_once "../config/MasterDatabase.php";

$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$uri = explode("/", trim($uri, "/"));
$uri = array_values(array_filter($uri));

if (isset($uri[0]) && $uri[0] === "hotel-system") {
    array_shift($uri);
}
if (isset($uri[0]) && $uri[0] === "public") {
    array_shift($uri);
}

$accessType = $uri[0] ?? "";

if ($accessType === "super") {
    require_once "../models/Hotel.php";
    require_once "../models/SuperUser.php";
    require_once "../controllers/SuperAdminController.php";
    
    $controller = new SuperAdminController();
    $action = $uri[1] ?? "login";
    
    if ($action === "login") {
        $controller->login();
    } elseif ($action === "dashboard") {
        $controller->dashboard();
    } elseif ($action === "hotels" && isset($uri[2]) && $uri[2] === "create") {
        $controller->createHotel();
    } elseif ($action === "logout") {
        $controller->logout();
    } else {
        http_response_code(404);
        echo "Página no encontrada";
    }
} elseif ($accessType === "hotel") {
    require_once "../controllers/HotelAuthController.php";
    require_once "../controllers/HotelDashboardController.php";
    
    $action = $uri[1] ?? "login";
    
    if ($action === "login") {
        $controller = new HotelAuthController();
        $controller->login();
    } elseif ($action === "password") {
        $controller = new HotelAuthController();
        $controller->changePassword();
    } elseif ($action === "logout") {
        $controller = new HotelAuthController();
        $controller->logout();
    } elseif ($action === "dashboard") {
        $controller = new HotelDashboardController();
        $controller->dashboard();
    } elseif ($action === "calendario") {
        require_once "../controllers/HotelCalendarController.php";
        $controller = new HotelCalendarController();
        $controller->index();
    } elseif ($action === "habitaciones") {
        require_once "../controllers/HotelRoomsController.php";
        $subAction = $uri[2] ?? "index";
        $controller = new HotelRoomsController();
        
        if ($subAction === "create") {
            $controller->create();
        } elseif ($subAction === "edit") {
            $controller->edit();
        } elseif ($subAction === "delete") {
            $controller->delete();
        } else {
            $controller->index();
        }
    } elseif ($action === "reservas") {
        require_once "../controllers/HotelReservationsController.php";
        $subAction = $uri[2] ?? "index";
        $controller = new HotelReservationsController();
        
        if ($subAction === "create") {
            $controller->create();
        } elseif ($subAction === "walkin") {
            $controller->walkin();
        } elseif ($subAction === "create-walkin") {
            $controller->createWalkin();
        } elseif ($subAction === "update-status") {
            $controller->updateStatus();
        } else {
            $controller->index();
        }
    
    } elseif ($action === "huespedes") {
        require_once "../controllers/HotelGuestsController.php";
        $subAction = $uri[2] ?? "save";
        $controller = new HotelGuestsController();
        
        if ($subAction === "save") {
            $controller->save();
        } elseif ($subAction === "get") {
            $controller->getByReserva();
        }
    } elseif ($action === "clientes") {
        require_once "../controllers/HotelClientsController.php";
        $subAction = $uri[2] ?? "index";
        $controller = new HotelClientsController();
        
        if ($subAction === "create") {
            $controller->create();
        } elseif ($subAction === "find-by-document") {
            $controller->findByDocument();
        } elseif ($subAction === "view") {
            $controller->view();
        } elseif ($subAction === "edit") {
            $controller->edit();
        } elseif ($subAction === "delete") {
            $controller->delete();
        } else {
            $controller->index();
        }
    } else {
        http_response_code(404);
        echo "Página no encontrada";
    }
} else {
    echo "<h1>Bienvenido al Sistema Hotel</h1>";
    echo "<p><a href=\"" . BASE_URL . "/super/login\">Super Admin</a> | <a href=\"" . BASE_URL . "/hotel/login\">Admin Hotel</a></p>";
}
?>
