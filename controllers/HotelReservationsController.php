<?php
class HotelReservationsController {
    private $hotelDb;

    private $horaCheckInEstandar = '15:00:00';
    private $horaCheckOutEstandar = '12:00:00';
    private $horaEarlyCheckIn = '11:00:00';
    private $horaLateCheckOut = '18:00:00';
    private $cargoEarlyCheckIn = 50.00;
    private $cargoLateCheckOut = 50.00;

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
        $filter = $_GET["filter"] ?? "all";

        $sql = "
            SELECT r.*,
                   h.numero_habitacion, h.tipo as tipo_habitacion,
                   c.nombre as cliente_nombre, c.documento as cliente_documento,
                   c.email as cliente_email, c.telefono as cliente_telefono
            FROM reservas r
            JOIN habitaciones h ON r.habitacion_id = h.id
            JOIN clientes c ON r.cliente_id = c.id
        ";

        if ($filter === "active") {
            $sql .= " WHERE r.estado IN ('reservada', 'ocupada')";
        } elseif ($filter === "finished") {
            $sql .= " WHERE r.estado = 'finalizada'";
        } elseif ($filter === "cancelled") {
            $sql .= " WHERE r.estado = 'cancelada'";
        }

        $sql .= " ORDER BY r.fecha_entrada DESC";

        $reservations = $this->hotelDb->query($sql)->fetchAll();
        require_once BASE_PATH . "/views/hotel/reservations.php";
    }

    public function create() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $clienteId = (int)($_POST["cliente_id"] ?? 0);
            $habitacionId = (int)($_POST["habitacion_id"] ?? 0);
            $fechaEntrada = $_POST["fecha_entrada"] ?? '';
            $fechaSalida = $_POST["fecha_salida"] ?? '';
            $numHuespedes = (int)($_POST["numero_huespedes"] ?? 1);
            $observaciones = trim($_POST["observaciones"] ?? '');

            if ($clienteId <= 0 || $habitacionId <= 0 || $fechaEntrada === '' || $fechaSalida === '') {
                header("Location: " . BASE_URL . "/hotel/reservas/create?error=datos");
                exit;
            }

            $earlyCheckIn = isset($_POST["early_checkin"]) ? 1 : 0;
            $lateCheckOut = isset($_POST["late_checkout"]) ? 1 : 0;

            $horaEntrada = $earlyCheckIn ? $this->horaEarlyCheckIn : $this->horaCheckInEstandar;
            $horaSalida = $lateCheckOut ? $this->horaLateCheckOut : $this->horaCheckOutEstandar;
            $total = $this->calculateReservationTotal($habitacionId, $fechaEntrada, $fechaSalida, $earlyCheckIn, $lateCheckOut);

            $stmt = $this->hotelDb->prepare(" 
                INSERT INTO reservas (
                    cliente_id, habitacion_id, fecha_entrada, hora_entrada,
                    fecha_salida, hora_salida, numero_huespedes, precio_total, total,
                    observaciones, early_checkin, late_checkout, cargo_extra, estado
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'reservada')
            ");

            $stmt->execute([
                $clienteId,
                $habitacionId,
                $fechaEntrada,
                $horaEntrada,
                $fechaSalida,
                $horaSalida,
                $numHuespedes,
                $total,
                $total,
                $observaciones,
                $earlyCheckIn,
                $lateCheckOut,
                ($earlyCheckIn ? $this->cargoEarlyCheckIn : 0) + ($lateCheckOut ? $this->cargoLateCheckOut : 0)
            ]);

            $stmt = $this->hotelDb->prepare("UPDATE habitaciones SET estado = 'reservada' WHERE id = ?");
            $stmt->execute([$habitacionId]);

            header("Location: " . BASE_URL . "/hotel/reservas?success=created");
            exit;
        }

        $clientes = $this->hotelDb->query("SELECT * FROM clientes ORDER BY nombre")->fetchAll();
        $habitaciones = [];
        $fechaEntrada = $_GET["fecha_entrada"] ?? null;
        $fechaSalida = $_GET["fecha_salida"] ?? null;
        $numHuespedes = $_GET["numero_huespedes"] ?? 1;

        if ($fechaEntrada && $fechaSalida) {
            $habitaciones = $this->findAvailableRooms($fechaEntrada, $fechaSalida, (int)$numHuespedes);
        }

        $config = $this->getScheduleConfig();
        require_once BASE_PATH . "/views/hotel/reservation_form.php";
    }

    public function edit() {
        $id = (int)($_GET["id"] ?? $_POST["id"] ?? 0);
        if ($id <= 0) {
            header("Location: " . BASE_URL . "/hotel/reservas?error=reserva");
            exit;
        }

        $stmt = $this->hotelDb->prepare("SELECT * FROM reservas WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $reservation = $stmt->fetch();

        if (!$reservation) {
            header("Location: " . BASE_URL . "/hotel/reservas?error=reserva");
            exit;
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $clienteId = (int)($_POST["cliente_id"] ?? 0);
            $habitacionId = (int)($_POST["habitacion_id"] ?? 0);
            $fechaEntrada = $_POST["fecha_entrada"] ?? '';
            $fechaSalida = $_POST["fecha_salida"] ?? '';
            $numHuespedes = (int)($_POST["numero_huespedes"] ?? 1);
            $observaciones = trim($_POST["observaciones"] ?? '');

            if ($clienteId <= 0 || $habitacionId <= 0 || $fechaEntrada === '' || $fechaSalida === '') {
                header("Location: " . BASE_URL . "/hotel/reservas/edit?id=" . $id . "&error=datos");
                exit;
            }

            $earlyCheckIn = isset($_POST["early_checkin"]) ? 1 : 0;
            $lateCheckOut = isset($_POST["late_checkout"]) ? 1 : 0;
            $horaEntrada = $earlyCheckIn ? $this->horaEarlyCheckIn : $this->horaCheckInEstandar;
            $horaSalida = $lateCheckOut ? $this->horaLateCheckOut : $this->horaCheckOutEstandar;
            $cargoExtra = ($earlyCheckIn ? $this->cargoEarlyCheckIn : 0) + ($lateCheckOut ? $this->cargoLateCheckOut : 0);
            $total = $this->calculateReservationTotal($habitacionId, $fechaEntrada, $fechaSalida, $earlyCheckIn, $lateCheckOut);

            $this->hotelDb->beginTransaction();
            try {
                $stmt = $this->hotelDb->prepare(" 
                    UPDATE reservas
                    SET cliente_id = ?, habitacion_id = ?, fecha_entrada = ?, hora_entrada = ?,
                        fecha_salida = ?, hora_salida = ?, numero_huespedes = ?,
                        precio_total = ?, total = ?, observaciones = ?,
                        early_checkin = ?, late_checkout = ?, cargo_extra = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $clienteId,
                    $habitacionId,
                    $fechaEntrada,
                    $horaEntrada,
                    $fechaSalida,
                    $horaSalida,
                    $numHuespedes,
                    $total,
                    $total,
                    $observaciones,
                    $earlyCheckIn,
                    $lateCheckOut,
                    $cargoExtra,
                    $id
                ]);

                if ((int)$reservation["habitacion_id"] !== $habitacionId) {
                    $stmt = $this->hotelDb->prepare("UPDATE habitaciones SET estado = 'disponible' WHERE id = ?");
                    $stmt->execute([(int)$reservation["habitacion_id"]]);

                    $newRoomState = $reservation["estado"] === 'ocupada' ? 'ocupada' : 'reservada';
                    $stmt = $this->hotelDb->prepare("UPDATE habitaciones SET estado = ? WHERE id = ?");
                    $stmt->execute([$newRoomState, $habitacionId]);
                }

                $this->hotelDb->commit();
                header("Location: " . BASE_URL . "/hotel/reservas?success=updated");
                exit;
            } catch (Exception $e) {
                $this->hotelDb->rollBack();
                header("Location: " . BASE_URL . "/hotel/reservas/edit?id=" . $id . "&error=save");
                exit;
            }
        }

        $clientes = $this->hotelDb->query("SELECT * FROM clientes ORDER BY nombre")->fetchAll();

        $stmt = $this->hotelDb->prepare(" 
            SELECT h.*
            FROM habitaciones h
            WHERE h.id = ?
               OR (
                    h.estado NOT IN ('mantenimiento')
                    AND h.id NOT IN (
                        SELECT r.habitacion_id
                        FROM reservas r
                        WHERE r.id <> ?
                          AND r.estado IN ('reservada', 'ocupada')
                          AND (
                            (r.fecha_entrada <= ? AND r.fecha_salida >= ?)
                            OR (r.fecha_entrada <= ? AND r.fecha_salida >= ?)
                            OR (r.fecha_entrada >= ? AND r.fecha_salida <= ?)
                          )
                    )
               )
            ORDER BY h.numero_habitacion
        ");

        $stmt->execute([
            (int)$reservation["habitacion_id"],
            $id,
            $reservation["fecha_entrada"],
            $reservation["fecha_entrada"],
            $reservation["fecha_salida"],
            $reservation["fecha_salida"],
            $reservation["fecha_entrada"],
            $reservation["fecha_salida"],
        ]);
        $habitaciones = $stmt->fetchAll();

        require_once BASE_PATH . "/views/hotel/reservation_edit.php";
    }

    public function walkin() {
        $clientes = $this->hotelDb->query(" 
            SELECT * FROM clientes
            ORDER BY created_at DESC
            LIMIT 50
        ")->fetchAll();

        $habitaciones = [];
        $fechaSalida = $_GET['fecha_salida'] ?? null;
        $numHuespedes = $_GET['numero_huespedes'] ?? 1;

        if ($fechaSalida) {
            $fechaEntrada = date('Y-m-d');
            $habitaciones = $this->findAvailableRooms($fechaEntrada, $fechaSalida, (int)$numHuespedes);
        }

        $config = $this->getScheduleConfig();
        require_once BASE_PATH . "/views/hotel/walkin_form.php";
    }

    public function createWalkin() {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: " . BASE_URL . "/hotel/reservas/walkin");
            exit;
        }

        $this->hotelDb->beginTransaction();

        try {
            $clienteId = $_POST["cliente_id"] ?? null;

            if (empty($clienteId) || $clienteId === 'new') {
                $stmt = $this->hotelDb->prepare(" 
                    INSERT INTO clientes (nombre, documento, email, telefono)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST["cliente_nombre"],
                    $_POST["cliente_documento"],
                    $_POST["cliente_email"] ?? '',
                    $_POST["cliente_telefono"] ?? ''
                ]);
                $clienteId = (int)$this->hotelDb->lastInsertId();
            }

            $habitacionId = (int)$_POST["habitacion_id"];
            $fechaEntrada = date('Y-m-d');
            $fechaSalida = $_POST["fecha_salida"];
            $numHuespedes = (int)$_POST["numero_huespedes"];
            $observaciones = $_POST["observaciones"] ?? "";

            $earlyCheckIn = isset($_POST["early_checkin"]) ? 1 : 0;
            $lateCheckOut = isset($_POST["late_checkout"]) ? 1 : 0;
            $horaEntrada = date('H:i:s');
            $horaSalida = $lateCheckOut ? $this->horaLateCheckOut : $this->horaCheckOutEstandar;

            $cargoExtra = $lateCheckOut ? $this->cargoLateCheckOut : 0;
            $total = $this->calculateReservationTotal($habitacionId, $fechaEntrada, $fechaSalida, 0, $lateCheckOut);

            $stmt = $this->hotelDb->prepare(" 
                INSERT INTO reservas (
                    cliente_id, habitacion_id, fecha_entrada, hora_entrada,
                    fecha_salida, hora_salida, numero_huespedes, precio_total, total,
                    observaciones, early_checkin, late_checkout, cargo_extra,
                    fecha_checkin, estado
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'ocupada')
            ");

            $stmt->execute([
                $clienteId,
                $habitacionId,
                $fechaEntrada,
                $horaEntrada,
                $fechaSalida,
                $horaSalida,
                $numHuespedes,
                $total,
                $total,
                $observaciones,
                $earlyCheckIn,
                $lateCheckOut,
                $cargoExtra
            ]);

            $reservaId = (int)$this->hotelDb->lastInsertId();
            $this->saveGuestsForReservation($reservaId, $_POST["huespedes"] ?? []);

            $stmt = $this->hotelDb->prepare("UPDATE habitaciones SET estado = 'ocupada' WHERE id = ?");
            $stmt->execute([$habitacionId]);

            $this->hotelDb->commit();

            header("Location: " . BASE_URL . "/hotel/reservas?success=walkin");
            exit;
        } catch (Exception $e) {
            $this->hotelDb->rollBack();
            header("Location: " . BASE_URL . "/hotel/reservas/walkin?error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    public function updateStatus() {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: " . BASE_URL . "/hotel/reservas");
            exit;
        }

        $id = (int)($_POST["reservation_id"] ?? 0);
        $nuevoEstado = $_POST["nuevo_estado"] ?? '';

        if ($id <= 0 || $nuevoEstado === '') {
            header("Location: " . BASE_URL . "/hotel/reservas?error=status");
            exit;
        }

        $this->hotelDb->beginTransaction();
        try {
            $stmt = $this->hotelDb->prepare(" 
                SELECT r.*, h.numero_habitacion, c.nombre as cliente_nombre
                FROM reservas r
                JOIN habitaciones h ON h.id = r.habitacion_id
                JOIN clientes c ON c.id = r.cliente_id
                WHERE r.id = ?
                LIMIT 1
            ");
            $stmt->execute([$id]);
            $reservation = $stmt->fetch();

            if (!$reservation) {
                throw new Exception('Reserva no encontrada');
            }

            $stmt = $this->hotelDb->prepare("UPDATE reservas SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevoEstado, $id]);

            $estadoHabitacion = "disponible";
            if ($nuevoEstado === "ocupada") {
                $estadoHabitacion = "ocupada";
                $stmt = $this->hotelDb->prepare("UPDATE reservas SET fecha_checkin = COALESCE(fecha_checkin, NOW()) WHERE id = ?");
                $stmt->execute([$id]);
            } elseif ($nuevoEstado === "reservada") {
                $estadoHabitacion = "reservada";
            } elseif ($nuevoEstado === "finalizada") {
                $estadoHabitacion = "limpieza";
                $stmt = $this->hotelDb->prepare("UPDATE reservas SET fecha_checkout = NOW() WHERE id = ?");
                $stmt->execute([$id]);

                $stmt = $this->hotelDb->prepare("SELECT COALESCE(SUM(monto), 0) as pagado FROM pagos WHERE reserva_id = ?");
                $stmt->execute([$id]);
                $pagado = (float)($stmt->fetch()["pagado"] ?? 0);
                $totalReserva = (float)($reservation["total"] > 0 ? $reservation["total"] : $reservation["precio_total"]);
                $pendiente = max(0, $totalReserva - $pagado);

                if ($pendiente > 0) {
                    $metodoPago = $_POST["metodo_pago"] ?? "efectivo";
                    $comprobante = $this->generateVoucherCode($id);

                    $stmt = $this->hotelDb->prepare(" 
                        INSERT INTO pagos (reserva_id, monto, metodo_pago, fecha_pago, comprobante, observaciones, usuario_id)
                        VALUES (?, ?, ?, NOW(), ?, ?, ?)
                    ");
                    $stmt->execute([
                        $id,
                        $pendiente,
                        $metodoPago,
                        $comprobante,
                        'Cobro automatico en check-out',
                        $_SESSION['hotel_user_id'] ?? null,
                    ]);
                }
            }

            $stmt = $this->hotelDb->prepare("UPDATE habitaciones SET estado = ? WHERE id = ?");
            $stmt->execute([$estadoHabitacion, (int)$reservation["habitacion_id"]]);

            $this->hotelDb->commit();

            if ($nuevoEstado === "finalizada") {
                header("Location: " . BASE_URL . "/hotel/reservas/recibo?id=" . $id);
                exit;
            }

            header("Location: " . BASE_URL . "/hotel/reservas?success=status");
            exit;
        } catch (Exception $e) {
            $this->hotelDb->rollBack();
            header("Location: " . BASE_URL . "/hotel/reservas?error=status");
            exit;
        }
    }

    public function receipt() {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header("Location: " . BASE_URL . "/hotel/reservas?error=recibo");
            exit;
        }

        $stmt = $this->hotelDb->prepare(" 
            SELECT r.*, h.numero_habitacion, h.tipo as tipo_habitacion,
                   c.nombre as cliente_nombre, c.documento as cliente_documento,
                   c.email as cliente_email, c.telefono as cliente_telefono
            FROM reservas r
            JOIN habitaciones h ON h.id = r.habitacion_id
            JOIN clientes c ON c.id = r.cliente_id
            WHERE r.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $reservation = $stmt->fetch();

        if (!$reservation) {
            header("Location: " . BASE_URL . "/hotel/reservas?error=recibo");
            exit;
        }

        $stmt = $this->hotelDb->prepare(" 
            SELECT monto, metodo_pago, fecha_pago, comprobante, observaciones
            FROM pagos
            WHERE reserva_id = ?
            ORDER BY fecha_pago DESC
        ");
        $stmt->execute([$id]);
        $payments = $stmt->fetchAll();

        $totalReserva = (float)($reservation['total'] > 0 ? $reservation['total'] : $reservation['precio_total']);
        $totalPagado = 0.0;
        foreach ($payments as $payment) {
            $totalPagado += (float)$payment['monto'];
        }

        $balance = max(0, $totalReserva - $totalPagado);
        require_once BASE_PATH . "/views/hotel/reservation_receipt.php";
    }

    private function calculateReservationTotal(int $habitacionId, string $fechaEntrada, string $fechaSalida, int $earlyCheckIn, int $lateCheckOut): float {
        $entrada = new DateTime($fechaEntrada);
        $salida = new DateTime($fechaSalida);
        $dias = max(1, (int)$entrada->diff($salida)->days);

        $stmt = $this->hotelDb->prepare("SELECT precio_noche FROM habitaciones WHERE id = ?");
        $stmt->execute([$habitacionId]);
        $room = $stmt->fetch();
        $precioNoche = (float)($room['precio_noche'] ?? 0);

        $cargoExtra = 0;
        if ($earlyCheckIn) {
            $cargoExtra += $this->cargoEarlyCheckIn;
        }
        if ($lateCheckOut) {
            $cargoExtra += $this->cargoLateCheckOut;
        }

        return ($dias * $precioNoche) + $cargoExtra;
    }

    private function findAvailableRooms(string $fechaEntrada, string $fechaSalida, int $numHuespedes): array {
        $sql = "
            SELECT h.*
            FROM habitaciones h
            WHERE h.capacidad >= ?
              AND h.id NOT IN (
                SELECT r.habitacion_id
                FROM reservas r
                WHERE r.estado IN ('reservada', 'ocupada')
                  AND (
                    (r.fecha_entrada <= ? AND r.fecha_salida >= ?)
                    OR (r.fecha_entrada <= ? AND r.fecha_salida >= ?)
                    OR (r.fecha_entrada >= ? AND r.fecha_salida <= ?)
                  )
              )
              AND h.estado NOT IN ('mantenimiento')
            ORDER BY h.numero_habitacion
        ";

        $stmt = $this->hotelDb->prepare($sql);
        $stmt->execute([
            $numHuespedes,
            $fechaEntrada,
            $fechaEntrada,
            $fechaSalida,
            $fechaSalida,
            $fechaEntrada,
            $fechaSalida,
        ]);

        return $stmt->fetchAll();
    }

    private function getScheduleConfig(): array {
        return [
            'hora_checkin_estandar' => $this->horaCheckInEstandar,
            'hora_checkout_estandar' => $this->horaCheckOutEstandar,
            'hora_early_checkin' => $this->horaEarlyCheckIn,
            'hora_late_checkout' => $this->horaLateCheckOut,
            'cargo_early_checkin' => $this->cargoEarlyCheckIn,
            'cargo_late_checkout' => $this->cargoLateCheckOut,
        ];
    }

    private function saveGuestsForReservation(int $reservaId, array $huespedes): void {
        if (empty($huespedes)) {
            return;
        }

        $stmt = $this->hotelDb->prepare(" 
            INSERT INTO huespedes
            (reserva_id, nombre, documento, tipo_documento, fecha_nacimiento, nacionalidad, telefono, email, es_titular)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($huespedes as $index => $huesped) {
            if (!empty($huesped['nombre']) && !empty($huesped['documento'])) {
                $stmt->execute([
                    $reservaId,
                    $huesped['nombre'],
                    $huesped['documento'],
                    $huesped['tipo_documento'] ?? 'DNI',
                    !empty($huesped['fecha_nacimiento']) ? $huesped['fecha_nacimiento'] : null,
                    $huesped['nacionalidad'] ?? 'Peruana',
                    $huesped['telefono'] ?? null,
                    $huesped['email'] ?? null,
                    $index === 0 ? 1 : 0,
                ]);
            }
        }
    }

    private function generateVoucherCode(int $reservationId): string {
        return 'TKT-' . date('Ymd-His') . '-' . str_pad((string)$reservationId, 4, '0', STR_PAD_LEFT);
    }
}
?>
