<?php
class HotelDashboardController {
    private PDO $hotelDb;

    public function __construct() {
        $this->checkAuth();
        $this->hotelDb = MasterDatabase::getConnection();
    }
    
    private function checkAuth(): void {
        if (!isset($_SESSION["hotel_user_id"])) {
            header("Location: " . BASE_URL . "/hotel/login");
            exit;
        }
    }
    
    public function dashboard(): void {
        $stats = $this->getStats();
        $recentReservations = $this->getRecentReservations();
        $roomsByStatus = $this->getRoomsByStatus();
        
        require_once BASE_PATH . "/views/hotel/dashboard.php";
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
            WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
            AND YEAR(created_at) = YEAR(CURRENT_DATE())
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
