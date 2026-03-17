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

    public function statistics(): void {
        $dateFilter = $this->getDateFilter();
        $stats = $this->getStats($dateFilter);
        $managementStats = $this->getManagementStats($dateFilter);
        $paymentsByMethod = $this->getPaymentsByMethod($dateFilter);

        require_once BASE_PATH . "/views/hotel/statistics.php";
    }

    private function getDateFilter(): array {
        $period = $_GET["period"] ?? "month";
        $allowedPeriods = ["today", "week", "month", "custom"];

        if (!in_array($period, $allowedPeriods, true)) {
            $period = "month";
        }

        $today = new DateTimeImmutable("today");
        $startDate = $today;
        $endDate = $today;

        if ($period === "today") {
            $startDate = $today;
            $endDate = $today;
        } elseif ($period === "week") {
            $startDate = $today->modify("monday this week");
            $endDate = $today->modify("sunday this week");
        } elseif ($period === "custom") {
            $customFrom = $_GET["from"] ?? "";
            $customTo = $_GET["to"] ?? "";
            $fromDate = DateTimeImmutable::createFromFormat("Y-m-d", $customFrom) ?: null;
            $toDate = DateTimeImmutable::createFromFormat("Y-m-d", $customTo) ?: null;

            if ($fromDate && $toDate) {
                if ($fromDate > $toDate) {
                    $temp = $fromDate;
                    $fromDate = $toDate;
                    $toDate = $temp;
                }
                $startDate = $fromDate;
                $endDate = $toDate;
            } else {
                $period = "month";
                $startDate = $today->modify("first day of this month");
                $endDate = $today->modify("last day of this month");
            }
        } else {
            $startDate = $today->modify("first day of this month");
            $endDate = $today->modify("last day of this month");
            $period = "month";
        }

        $start = $startDate->format("Y-m-d");
        $end = $endDate->format("Y-m-d");

        $label = "Mes actual";
        if ($period === "today") {
            $label = "Hoy";
        } elseif ($period === "week") {
            $label = "Semana actual";
        } elseif ($period === "custom") {
            $label = "Rango personalizado";
        }

        return [
            "period" => $period,
            "start" => $start,
            "end" => $end,
            "label" => $label,
        ];
    }
    
    private function getStats(?array $dateFilter = null): array {
        if ($dateFilter === null) {
            $today = new DateTimeImmutable("today");
            $dateFilter = [
                "start" => $today->modify("first day of this month")->format("Y-m-d"),
                "end" => $today->modify("last day of this month")->format("Y-m-d"),
            ];
        }

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
        
        $stmt = $this->hotelDb->prepare("
            SELECT COALESCE(SUM(monto), 0) as total
            FROM pagos
            WHERE DATE(fecha_pago) BETWEEN ? AND ?
        ");
        $stmt->execute([$dateFilter["start"], $dateFilter["end"]]);
        $periodRevenue = $stmt->fetch()["total"];
        
        return [
            "total_rooms" => $totalRooms,
            "available_rooms" => $availableRooms,
            "occupied_rooms" => $occupiedRooms,
            "active_reservations" => $activeReservations,
            "total_clients" => $totalClients,
            "monthly_revenue" => $periodRevenue,
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

    private function getManagementStats(array $dateFilter): array {
        $stmt = $this->hotelDb->prepare("
            SELECT
                SUM(CASE WHEN DATE(fecha_checkin) BETWEEN ? AND ? THEN 1 ELSE 0 END) AS checkins_periodo,
                SUM(CASE WHEN DATE(fecha_checkout) BETWEEN ? AND ? THEN 1 ELSE 0 END) AS checkouts_periodo,
                SUM(CASE WHEN DATE(created_at) BETWEEN ? AND ? THEN 1 ELSE 0 END) AS reservas_periodo,
                SUM(CASE WHEN estado = 'finalizada' AND DATE(updated_at) BETWEEN ? AND ? THEN 1 ELSE 0 END) AS finalizadas_periodo,
                SUM(CASE WHEN estado = 'cancelada' AND DATE(updated_at) BETWEEN ? AND ? THEN 1 ELSE 0 END) AS canceladas_periodo,
                COALESCE(AVG(CASE WHEN DATE(created_at) BETWEEN ? AND ? AND estado IN ('ocupada', 'finalizada') THEN DATEDIFF(fecha_salida, fecha_entrada) END), 0) AS estancia_promedio
            FROM reservas
        ");
        $stmt->execute([
            $dateFilter["start"], $dateFilter["end"],
            $dateFilter["start"], $dateFilter["end"],
            $dateFilter["start"], $dateFilter["end"],
            $dateFilter["start"], $dateFilter["end"],
            $dateFilter["start"], $dateFilter["end"],
            $dateFilter["start"], $dateFilter["end"],
        ]);
        $reservationMetrics = $stmt->fetch() ?: [];

        $stmt = $this->hotelDb->prepare("
            SELECT COALESCE(SUM(monto), 0) AS ingresos_periodo
            FROM pagos
            WHERE DATE(fecha_pago) BETWEEN ? AND ?
        ");
        $stmt->execute([$dateFilter["start"], $dateFilter["end"]]);
        $periodRevenue = (float)($stmt->fetch()["ingresos_periodo"] ?? 0);

        $stmt = $this->hotelDb->prepare("
            SELECT COALESCE(AVG(monto), 0) AS ticket_promedio_periodo
            FROM pagos
            WHERE DATE(fecha_pago) BETWEEN ? AND ?
        ");
        $stmt->execute([$dateFilter["start"], $dateFilter["end"]]);
        $averageTicket = (float)($stmt->fetch()["ticket_promedio_periodo"] ?? 0);

        $stmt = $this->hotelDb->query("
            SELECT COALESCE(SUM(GREATEST(r.total - COALESCE(p.pagado, 0), 0)), 0) AS saldo_pendiente_total
            FROM reservas r
            LEFT JOIN (
                SELECT reserva_id, SUM(monto) AS pagado
                FROM pagos
                GROUP BY reserva_id
            ) p ON p.reserva_id = r.id
            WHERE r.estado IN ('reservada', 'ocupada')
        ");
        $outstandingBalance = (float)($stmt->fetch()["saldo_pendiente_total"] ?? 0);

        $reservasPeriodo = (int)($reservationMetrics["reservas_periodo"] ?? 0);
        $canceladasPeriodo = (int)($reservationMetrics["canceladas_periodo"] ?? 0);

        return [
            "checkins_hoy" => (int)($reservationMetrics["checkins_periodo"] ?? 0),
            "checkouts_hoy" => (int)($reservationMetrics["checkouts_periodo"] ?? 0),
            "reservas_mes" => $reservasPeriodo,
            "finalizadas_mes" => (int)($reservationMetrics["finalizadas_periodo"] ?? 0),
            "canceladas_mes" => $canceladasPeriodo,
            "estancia_promedio" => round((float)($reservationMetrics["estancia_promedio"] ?? 0), 1),
            "ingresos_hoy" => $periodRevenue,
            "ticket_promedio_mes" => $averageTicket,
            "saldo_pendiente_total" => $outstandingBalance,
            "tasa_cancelacion_mes" => $reservasPeriodo > 0 ? round(($canceladasPeriodo / $reservasPeriodo) * 100, 1) : 0,
        ];
    }

    private function getPaymentsByMethod(array $dateFilter): array {
        $stmt = $this->hotelDb->prepare("
            SELECT metodo_pago, COUNT(*) AS cantidad, COALESCE(SUM(monto), 0) AS total
            FROM pagos
            WHERE DATE(fecha_pago) BETWEEN ? AND ?
            GROUP BY metodo_pago
            ORDER BY total DESC
        ");
        $stmt->execute([$dateFilter["start"], $dateFilter["end"]]);

        return $stmt->fetchAll();
    }
}
?>
