<?php
class HotelRoomsController {
    private $hotelDb;
    
    public function __construct() {
        $this->checkAuth();
        $this->hotelDb = MasterDatabase::getConnection();
    }
    
    private function checkAuth() {
        if (!isset($_SESSION["hotel_user_id"])) {
            header("Location: " . BASE_URL . "/hotel/login");
            exit;
        }
    }
    
    public function index() {
        $stmt = $this->hotelDb->query("
            SELECT h.*, 
                   (SELECT COUNT(*) FROM reservas r 
                    WHERE r.habitacion_id = h.id 
                    AND r.estado IN ('reservada', 'ocupada')) as reservas_activas
            FROM habitaciones h
            ORDER BY h.numero_habitacion
        ");
        
        $rooms = $stmt->fetchAll();
        require_once BASE_PATH . "/views/hotel/rooms.php";
    }
    
    public function create() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $numero = $_POST["numero_habitacion"];
            $tipo = $_POST["tipo"];
            $precio = $_POST["precio_noche"];
            $capacidad = $_POST["capacidad"];
            $descripcion = $_POST["descripcion"] ?? "";
            
            $stmt = $this->hotelDb->prepare("
                INSERT INTO habitaciones (numero_habitacion, tipo, precio_noche, capacidad, descripcion, estado)
                VALUES (?, ?, ?, ?, ?, 'disponible')
            ");
            
            $stmt->execute([$numero, $tipo, $precio, $capacidad, $descripcion]);
            
            header("Location: " . BASE_URL . "/hotel/habitaciones");
            exit;
        }
        
        require_once BASE_PATH . "/views/hotel/room_form.php";
    }
    
    public function edit() {
        $id = $_GET["id"] ?? 0;
        
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $stmt = $this->hotelDb->prepare("
                UPDATE habitaciones 
                SET numero_habitacion = ?, tipo = ?, precio_noche = ?, 
                    capacidad = ?, descripcion = ?, estado = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $_POST["numero_habitacion"],
                $_POST["tipo"],
                $_POST["precio_noche"],
                $_POST["capacidad"],
                $_POST["descripcion"] ?? "",
                $_POST["estado"],
                $id
            ]);
            
            header("Location: " . BASE_URL . "/hotel/habitaciones");
            exit;
        }
        
        $stmt = $this->hotelDb->prepare("SELECT * FROM habitaciones WHERE id = ?");
        $stmt->execute([$id]);
        $room = $stmt->fetch();
        
        if (!$room) {
            header("Location: " . BASE_URL . "/hotel/habitaciones");
            exit;
        }
        
        require_once BASE_PATH . "/views/hotel/room_form.php";
    }

    public function delete() {
        $id = $_GET["id"] ?? 0;

        if (!$id) {
            header("Location: " . BASE_URL . "/hotel/habitaciones");
            exit;
        }

        $stmt = $this->hotelDb->prepare("SELECT COUNT(*) as total FROM reservas WHERE habitacion_id = ? AND estado IN ('reservada', 'ocupada')");
        $stmt->execute([$id]);
        $active = (int)($stmt->fetch()["total"] ?? 0);

        if ($active > 0) {
            header("Location: " . BASE_URL . "/hotel/habitaciones?error=reservas_activas");
            exit;
        }

        $stmt = $this->hotelDb->prepare("DELETE FROM habitaciones WHERE id = ?");
        $stmt->execute([$id]);

        header("Location: " . BASE_URL . "/hotel/habitaciones?success=deleted");
        exit;
    }
}
