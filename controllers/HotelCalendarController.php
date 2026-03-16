<?php
class HotelCalendarController {
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

    public function index(): void {
        $ym = $_GET['ym'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $ym)) {
            $ym = date('Y-m');
        }

        $monthStart = new DateTimeImmutable($ym . '-01');
        $monthEnd = $monthStart->modify('last day of this month');

        $prevYm = $monthStart->modify('-1 month')->format('Y-m');
        $nextYm = $monthStart->modify('+1 month')->format('Y-m');

        $days = [];
        for ($d = $monthStart; $d <= $monthEnd; $d = $d->modify('+1 day')) {
            $days[] = [
                'date' => $d->format('Y-m-d'),
                'day' => $d->format('d'),
                'dow' => $d->format('D'),
            ];
        }

        $rooms = $this->hotelDb->query("SELECT id, numero_habitacion, tipo, estado FROM habitaciones ORDER BY numero_habitacion ASC")->fetchAll();

        $stmt = $this->hotelDb->prepare(
            "SELECT r.id, r.habitacion_id, r.cliente_id, r.fecha_entrada, r.fecha_salida, r.estado,
                    c.nombre AS cliente_nombre
             FROM reservas r
             JOIN clientes c ON c.id = r.cliente_id
             WHERE r.estado IN ('reservada', 'ocupada', 'finalizada')
               AND r.fecha_entrada <= :month_end
               AND r.fecha_salida >= :month_start
             ORDER BY r.fecha_entrada ASC"
        );
        $stmt->execute([
            'month_start' => $monthStart->format('Y-m-d'),
            'month_end' => $monthEnd->format('Y-m-d'),
        ]);
        $reservations = $stmt->fetchAll();

        $calendarMap = [];
        foreach ($reservations as $res) {
            $entry = new DateTimeImmutable($res['fecha_entrada']);
            $checkout = new DateTimeImmutable($res['fecha_salida']);
            $lastOccupied = $checkout->modify('-1 day');

            if ($lastOccupied < $entry) {
                $lastOccupied = $entry;
            }

            $from = $entry > $monthStart ? $entry : $monthStart;
            $to = $lastOccupied < $monthEnd ? $lastOccupied : $monthEnd;

            for ($day = $from; $day <= $to; $day = $day->modify('+1 day')) {
                $key = $day->format('Y-m-d');
                if (!isset($calendarMap[$res['habitacion_id']][$key])) {
                    $calendarMap[$res['habitacion_id']][$key] = [
                        'reservation_id' => (int)$res['id'],
                        'status' => $res['estado'],
                        'client_name' => $res['cliente_nombre'],
                    ];
                }
            }
        }

        require_once BASE_PATH . "/views/hotel/calendar.php";
    }
}
