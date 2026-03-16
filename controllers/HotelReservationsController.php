<?php
class HotelReservationsController {
    private $hotelDb;
    
    // Configuración de horarios
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
    
            public function walkin() {
        // Vista de check-in directo
        
        // Obtener clientes para selector rápido
        $clientes = $this->hotelDb->query("
            SELECT * FROM clientes 
            ORDER BY created_at DESC 
            LIMIT 50
        ")->fetchAll();
        
        // Obtener habitaciones disponibles solo si hay fecha de salida
        $habitaciones = [];
        $fechaSalida = $_GET['fecha_salida'] ?? null;
        $numHuespedes = $_GET['numero_huespedes'] ?? 1;
        
        if ($fechaSalida) {
            $fechaEntrada = date('Y-m-d'); // HOY
            
            // Buscar habitaciones que NO tienen reservas activas en ese rango
            // Y que tengan capacidad suficiente para los huéspedes
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
                $fechaEntrada, $fechaEntrada,
                $fechaSalida, $fechaSalida,
                $fechaEntrada, $fechaSalida
            ]);
            $habitaciones = $stmt->fetchAll();
        }
        
        $config = [
            'hora_checkin_estandar' => $this->horaCheckInEstandar,
            'hora_checkout_estandar' => $this->horaCheckOutEstandar,
            'hora_early_checkin' => $this->horaEarlyCheckIn,
            'hora_late_checkout' => $this->horaLateCheckOut,
            'cargo_early_checkin' => $this->cargoEarlyCheckIn,
            'cargo_late_checkout' => $this->cargoLateCheckOut
        ];
        
        require_once BASE_PATH . "/views/hotel/walkin_form.php";
    }
    
    public function createWalkin() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->hotelDb->beginTransaction();
            
            try {
                // 1. Crear o usar cliente existente
                $clienteId = $_POST["cliente_id"] ?? null;
                
                if (empty($clienteId) || $clienteId === 'new') {
                    // Crear nuevo cliente
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
                    $clienteId = $this->hotelDb->lastInsertId();
                }
                
                // 2. Crear la reserva
                $habitacionId = $_POST["habitacion_id"];
                $fechaEntrada = date('Y-m-d');
                $fechaSalida = $_POST["fecha_salida"];
                $numHuespedes = $_POST["numero_huespedes"];
                $observaciones = $_POST["observaciones"] ?? "";
                
                // Horarios
                $earlyCheckIn = isset($_POST["early_checkin"]) ? 1 : 0;
                $lateCheckOut = isset($_POST["late_checkout"]) ? 1 : 0;
                
                $horaEntrada = date('H:i:s'); // Hora actual
                $horaSalida = $lateCheckOut ? $this->horaLateCheckOut : $this->horaCheckOutEstandar;
                
                // Calcular días y total
                $entrada = new DateTime($fechaEntrada);
                $salida = new DateTime($fechaSalida);
                $dias = $entrada->diff($salida)->days;
                
                if ($dias < 1) $dias = 1; // Mínimo 1 día
                
                // Obtener precio de la habitación
                $stmt = $this->hotelDb->prepare("SELECT precio_noche FROM habitaciones WHERE id = ?");
                $stmt->execute([$habitacionId]);
                $precioNoche = $stmt->fetch()["precio_noche"];
                
                // Calcular cargos extras
                $cargoExtra = 0;
                if ($lateCheckOut) $cargoExtra += $this->cargoLateCheckOut;
                
                $total = ($dias * $precioNoche) + $cargoExtra;
                
                // Crear reserva con estado 'ocupada' directamente
                $stmt = $this->hotelDb->prepare("
                    INSERT INTO reservas (
                        cliente_id, habitacion_id, fecha_entrada, hora_entrada, 
                        fecha_salida, hora_salida, numero_huespedes, precio_total, 
                        observaciones, early_checkin, late_checkout, cargo_extra, estado
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ocupada')
                ");
                
                $stmt->execute([
                    $clienteId, $habitacionId, $fechaEntrada, $horaEntrada,
                    $fechaSalida, $horaSalida, $numHuespedes, $total,
                    $observaciones, $earlyCheckIn, $lateCheckOut, $cargoExtra
                ]);
                
                $reservaId = $this->hotelDb->lastInsertId();
                
                // 3. Registrar huéspedes
                $huespedes = $_POST["huespedes"] ?? [];
                $stmt = $this->hotelDb->prepare("
                    INSERT INTO huespedes 
                    (reserva_id, nombre, documento, tipo_documento, fecha_nacimiento, 
                     nacionalidad, telefono, email, es_titular)
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
                            $index === 0 ? 1 : 0
                        ]);
                    }
                }
                
                // 4. Actualizar estado de habitación
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
    }
    
        public function create() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $clienteId = $_POST["cliente_id"];
            $habitacionId = $_POST["habitacion_id"];
            $fechaEntrada = $_POST["fecha_entrada"];
            $fechaSalida = $_POST["fecha_salida"];
            $numHuespedes = $_POST["numero_huespedes"];
            $observaciones = $_POST["observaciones"] ?? "";
            
            $earlyCheckIn = isset($_POST["early_checkin"]) ? 1 : 0;
            $lateCheckOut = isset($_POST["late_checkout"]) ? 1 : 0;
            
            $horaEntrada = $earlyCheckIn ? $this->horaEarlyCheckIn : $this->horaCheckInEstandar;
            $horaSalida = $lateCheckOut ? $this->horaLateCheckOut : $this->horaCheckOutEstandar;
            
            $entrada = new DateTime($fechaEntrada);
            $salida = new DateTime($fechaSalida);
            $dias = $entrada->diff($salida)->days;
            
            $stmt = $this->hotelDb->prepare("SELECT precio_noche FROM habitaciones WHERE id = ?");
            $stmt->execute([$habitacionId]);
            $precioNoche = $stmt->fetch()["precio_noche"];
            
            $cargoExtra = 0;
            if ($earlyCheckIn) $cargoExtra += $this->cargoEarlyCheckIn;
            if ($lateCheckOut) $cargoExtra += $this->cargoLateCheckOut;
            
            $total = ($dias * $precioNoche) + $cargoExtra;
            
            $stmt = $this->hotelDb->prepare("
                INSERT INTO reservas (
                    cliente_id, habitacion_id, fecha_entrada, hora_entrada, 
                    fecha_salida, hora_salida, numero_huespedes, precio_total, 
                    observaciones, early_checkin, late_checkout, cargo_extra, estado
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'reservada')
            ");
            
            $stmt->execute([
                $clienteId, $habitacionId, $fechaEntrada, $horaEntrada,
                $fechaSalida, $horaSalida, $numHuespedes, $total,
                $observaciones, $earlyCheckIn, $lateCheckOut, $cargoExtra
            ]);
            
            $stmt = $this->hotelDb->prepare("UPDATE habitaciones SET estado = 'reservada' WHERE id = ?");
            $stmt->execute([$habitacionId]);
            
            header("Location: " . BASE_URL . "/hotel/reservas");
            exit;
        }
        
        $clientes = $this->hotelDb->query("SELECT * FROM clientes ORDER BY nombre")->fetchAll();
        $habitaciones = [];
        $fechaEntrada = $_GET["fecha_entrada"] ?? null;
        $fechaSalida = $_GET["fecha_salida"] ?? null;
        $numHuespedes = $_GET["numero_huespedes"] ?? 1;
        
        if ($fechaEntrada && $fechaSalida) {
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
                $fechaEntrada, $fechaEntrada,
                $fechaSalida, $fechaSalida,
                $fechaEntrada, $fechaSalida
            ]);
            $habitaciones = $stmt->fetchAll();
        }
        
        $config = [
            'hora_checkin_estandar' => $this->horaCheckInEstandar,
            'hora_checkout_estandar' => $this->horaCheckOutEstandar,
            'hora_early_checkin' => $this->horaEarlyCheckIn,
            'hora_late_checkout' => $this->horaLateCheckOut,
            'cargo_early_checkin' => $this->cargoEarlyCheckIn,
            'cargo_late_checkout' => $this->cargoLateCheckOut
        ];
        
        require_once BASE_PATH . "/views/hotel/reservation_form.php";
    }
    
    public function updateStatus() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $id = $_POST["reservation_id"];
            $nuevoEstado = $_POST["nuevo_estado"];
            
            $stmt = $this->hotelDb->prepare("UPDATE reservas SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevoEstado, $id]);
            
            $stmt = $this->hotelDb->prepare("SELECT habitacion_id FROM reservas WHERE id = ?");
            $stmt->execute([$id]);
            $habitacionId = $stmt->fetch()["habitacion_id"];
            
            $estadoHabitacion = "disponible";
            if ($nuevoEstado === "ocupada") {
                $estadoHabitacion = "ocupada";
            } elseif ($nuevoEstado === "reservada") {
                $estadoHabitacion = "reservada";
            } elseif ($nuevoEstado === "finalizada") {
                $estadoHabitacion = "limpieza";
            }
            
            $stmt = $this->hotelDb->prepare("UPDATE habitaciones SET estado = ? WHERE id = ?");
            $stmt->execute([$estadoHabitacion, $habitacionId]);
            
            header("Location: " . BASE_URL . "/hotel/reservas");
            exit;
        }
    }
}
