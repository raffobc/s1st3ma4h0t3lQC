<?php
/**
 * SISTEMA DE ALERTAS DE CHECKOUT
 * Archivo: controllers/HotelDashboardController.php (ACTUALIZADO)
 */

class HotelDashboardController {
    private PDO $hotelDb;
    
    public function __construct() {
        $this->checkAuth();
        $this->hotelDb = $this->getHotelConnection();
    }
    
    private function checkAuth(): void {
        if (!isset($_SESSION["hotel_user_id"])) {
            header("Location: /hotel/login");
            exit;
        }
    }
    
    private function getHotelConnection(): PDO {
        $dbName = $_SESSION["hotel_db_name"];
        $credentials = $_SESSION["hotel_db_credentials"];
        
        $pdo = new PDO(
            "mysql:host={$credentials["host"]};dbname=$dbName;charset=utf8mb4",
            $credentials["user"],
            $credentials["password"],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        return $pdo;
    }
    
    public function dashboard(): void {
        $stats = $this->getStats();
        $recentReservations = $this->getRecentReservations();
        $roomsByStatus = $this->getRoomsByStatus();
        $checkoutAlerts = $this->getCheckoutAlerts();
        
        require_once BASE_PATH . "/views/hotel/dashboard.php";
    }
    
    private function getCheckoutAlerts(): array {
        // Checkouts de HOY
        $stmt = $this->hotelDb->prepare("
            SELECT r.*, 
                   h.numero_habitacion, h.tipo as tipo_habitacion,
                   c.nombre as cliente_nombre, 
                   c.documento as cliente_documento,
                   c.telefono as cliente_telefono,
                   c.email as cliente_email,
                   CASE 
                       WHEN TIME(r.fecha_salida) = '00:00:00' THEN '12:00:00'
                       ELSE TIME(r.fecha_salida)
                   END as hora_salida
            FROM reservas r
            JOIN habitaciones h ON r.habitacion_id = h.id
            JOIN clientes c ON r.cliente_id = c.id
            WHERE DATE(r.fecha_salida) = CURDATE()
            AND r.estado IN ('reservada', 'ocupada')
            ORDER BY hora_salida ASC
        ");
        $stmt->execute();
        $todayCheckouts = $stmt->fetchAll();
        
        // Checkouts MAÑANA
        $stmt = $this->hotelDb->prepare("
            SELECT r.*, 
                   h.numero_habitacion, h.tipo as tipo_habitacion,
                   c.nombre as cliente_nombre,
                   c.documento as cliente_documento,
                   CASE 
                       WHEN TIME(r.fecha_salida) = '00:00:00' THEN '12:00:00'
                       ELSE TIME(r.fecha_salida)
                   END as hora_salida
            FROM reservas r
            JOIN habitaciones h ON r.habitacion_id = h.id
            JOIN clientes c ON r.cliente_id = c.id
            WHERE DATE(r.fecha_salida) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
            AND r.estado IN ('reservada', 'ocupada')
            ORDER BY hora_salida ASC
        ");
        $stmt->execute();
        $tomorrowCheckouts = $stmt->fetchAll();
        
        // Checkouts ATRASADOS (deberían haber salido pero siguen activos)
        $stmt = $this->hotelDb->prepare("
            SELECT r.*, 
                   h.numero_habitacion, h.tipo as tipo_habitacion,
                   c.nombre as cliente_nombre,
                   c.documento as cliente_documento,
                   DATEDIFF(CURDATE(), DATE(r.fecha_salida)) as dias_retraso
            FROM reservas r
            JOIN habitaciones h ON r.habitacion_id = h.id
            JOIN clientes c ON r.cliente_id = c.id
            WHERE DATE(r.fecha_salida) < CURDATE()
            AND r.estado IN ('reservada', 'ocupada')
            ORDER BY r.fecha_salida ASC
        ");
        $stmt->execute();
        $overdueCheckouts = $stmt->fetchAll();
        
        return [
            'today' => $todayCheckouts,
            'tomorrow' => $tomorrowCheckouts,
            'overdue' => $overdueCheckouts,
            'total_alerts' => count($todayCheckouts) + count($overdueCheckouts)
        ];
    }
    
    private function getStats(): array {
        $stmt = $this->hotelDb->query("SELECT COUNT(*) as total FROM habitaciones");
        $totalRooms = $stmt->fetch()["total"];
        
        $stmt = $this->hotelDb->query("SELECT COUNT(*) as total FROM habitaciones WHERE estado = 'disponible'");
        $availableRooms = $stmt->fetch()["total"];
        
        $stmt = $this->hotelDb->query("SELECT COUNT(*) as total FROM habitaciones WHERE estado = 'ocupada'");
        $occupiedRooms = $stmt->fetch()["total"];
        
        $stmt = $this->hotelDb->query("SELECT COUNT(*) as total FROM reservas WHERE estado IN ('reservada', 'ocupada')");
        $activeReservations = $stmt->fetch()["total"];
        
        $stmt = $this->hotelDb->query("SELECT COUNT(*) as total FROM clientes");
        $totalClients = $stmt->fetch()["total"];
        
        $stmt = $this->hotelDb->query("
            SELECT COALESCE(SUM(monto), 0) as total 
            FROM pagos 
            WHERE MONTH(fecha_pago) = MONTH(CURRENT_DATE())
            AND YEAR(fecha_pago) = YEAR(CURRENT_DATE())
        ");
        $monthlyRevenue = $stmt->fetch()["total"];
        
        return [
            "total_rooms" => $totalRooms,
            "available_rooms" => $availableRooms,
            "occupied_rooms" => $occupiedRooms,
            "active_reservations" => $activeReservations,
            "total_clients" => $totalClients,
            "monthly_revenue" => $monthlyRevenue,
            "occupancy_rate" => $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0
        ];
    }
    
    private function getRecentReservations(): array {
        $stmt = $this->hotelDb->query("
            SELECT r.*, 
                   h.numero_habitacion, h.tipo as tipo_habitacion,
                   c.nombre as cliente_nombre, c.documento as cliente_documento
            FROM reservas r
            JOIN habitaciones h ON r.habitacion_id = h.id
            JOIN clientes c ON r.cliente_id = c.id
            ORDER BY r.created_at DESC
            LIMIT 5
        ");
        
        return $stmt->fetchAll();
    }
    
    private function getRoomsByStatus(): array {
        $stmt = $this->hotelDb->query("
            SELECT estado, COUNT(*) as cantidad
            FROM habitaciones
            GROUP BY estado
        ");
        
        return $stmt->fetchAll();
    }
}
?>
