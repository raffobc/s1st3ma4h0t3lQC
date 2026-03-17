<?php
class HotelGuestsController {
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
    
    public function save() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $reservaId = (int)($_POST["reserva_id"] ?? 0);
            $huespedes = $_POST["huespedes"] ?? [];

            if (empty($huespedes) && !empty($_POST["huesped_nombre"]) && is_array($_POST["huesped_nombre"])) {
                $nombres = $_POST["huesped_nombre"];
                $documentos = $_POST["huesped_documento"] ?? [];
                $tiposDocumento = $_POST["huesped_tipo_documento"] ?? [];

                foreach ($nombres as $i => $nombre) {
                    $huespedes[] = [
                        'nombre' => trim((string)$nombre),
                        'documento' => trim((string)($documentos[$i] ?? '')),
                        'tipo_documento' => (string)($tiposDocumento[$i] ?? 'DNI'),
                    ];
                }
            }

            if ($reservaId <= 0) {
                if (!empty($_POST["ajax"])) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Reserva invalida']);
                    exit;
                }

                header("Location: " . BASE_URL . "/hotel/reservas?error=checkin");
                exit;
            }
            
            // Iniciar transacción
            $this->hotelDb->beginTransaction();
            
            try {
                // Eliminar huéspedes existentes de esta reserva
                $stmt = $this->hotelDb->prepare("DELETE FROM huespedes WHERE reserva_id = ?");
                $stmt->execute([$reservaId]);
                
                // Insertar nuevos huéspedes
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
                            $index === 0 ? 1 : 0 // Primer huésped es el titular
                        ]);
                    }
                }
                
                // Actualizar estado de la reserva a 'ocupada' y fecha real de check-in
                $stmt = $this->hotelDb->prepare("UPDATE reservas SET estado = 'ocupada', fecha_checkin = COALESCE(fecha_checkin, NOW()) WHERE id = ?");
                $stmt->execute([$reservaId]);
                
                // Actualizar estado de la habitación
                $stmt = $this->hotelDb->prepare("
                    UPDATE habitaciones h
                    JOIN reservas r ON h.id = r.habitacion_id
                    SET h.estado = 'ocupada'
                    WHERE r.id = ?
                ");
                $stmt->execute([$reservaId]);
                
                $this->hotelDb->commit();
                
                // Si es AJAX
                if (!empty($_POST["ajax"])) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true]);
                    exit;
                }
                
                header("Location: " . BASE_URL . "/hotel/reservas?success=checkin");
                exit;
                
            } catch (Exception $e) {
                $this->hotelDb->rollBack();
                
                if (!empty($_POST["ajax"])) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    exit;
                }
                
                header("Location: " . BASE_URL . "/hotel/reservas?error=checkin");
                exit;
            }
        }
    }
    
    public function getByReserva() {
        $reservaId = $_GET["reserva_id"] ?? 0;
        
        $stmt = $this->hotelDb->prepare("
            SELECT * FROM huespedes 
            WHERE reserva_id = ? 
            ORDER BY es_titular DESC, id ASC
        ");
        $stmt->execute([$reservaId]);
        $huespedes = $stmt->fetchAll();
        
        header('Content-Type: application/json');
        echo json_encode($huespedes);
        exit;
    }
}
