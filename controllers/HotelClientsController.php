<?php
class HotelClientsController {
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
        $search = $_GET["search"] ?? "";
        
        $sql = "
            SELECT c.*, 
                   COUNT(DISTINCT r.id) as total_reservas,
                   COALESCE(SUM(p.monto), 0) as total_gastado
            FROM clientes c
            LEFT JOIN reservas r ON c.id = r.cliente_id
            LEFT JOIN pagos p ON r.id = p.reserva_id
        ";
        
        if ($search) {
            $sql .= " WHERE c.nombre LIKE ? OR c.documento LIKE ? OR c.email LIKE ?";
        }
        
        $sql .= " GROUP BY c.id ORDER BY c.created_at DESC";
        
        if ($search) {
            $stmt = $this->hotelDb->prepare($sql);
            $searchParam = "%$search%";
            $stmt->execute([$searchParam, $searchParam, $searchParam]);
            $clients = $stmt->fetchAll();
        } else {
            $clients = $this->hotelDb->query($sql)->fetchAll();
        }
        
        require_once BASE_PATH . "/views/hotel/clients.php";
    }
    
    public function create() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $isAjax = !empty($_POST["ajax"]);

            $nombre = trim($_POST["nombre"] ?? "");
            $documento = trim($_POST["documento"] ?? "");
            $email = trim($_POST["email"] ?? "");
            $telefono = trim($_POST["telefono"] ?? "");
            $ciudad = trim($_POST["ciudad"] ?? "");
            $pais = trim($_POST["pais"] ?? "");

            if ($nombre === "" || $documento === "") {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Nombre y documento son obligatorios.'
                    ]);
                    exit;
                }

                header("Location: " . BASE_URL . "/hotel/clientes/create?error=required");
                exit;
            }

            try {
                $stmt = $this->hotelDb->prepare(" 
                    INSERT INTO clientes (nombre, documento, email, telefono, ciudad, pais)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $nombre,
                    $documento,
                    $email !== "" ? $email : null,
                    $telefono !== "" ? $telefono : null,
                    $ciudad !== "" ? $ciudad : null,
                    $pais !== "" ? $pais : null
                ]);

                $clienteId = $this->hotelDb->lastInsertId();
                
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'cliente' => [
                            'id' => $clienteId,
                            'nombre' => $nombre,
                            'documento' => $documento
                        ]
                    ]);
                    exit;
                }

                header("Location: " . BASE_URL . "/hotel/clientes?success=created");
                exit;
            } catch (PDOException $e) {
                $isDuplicate = ($e->getCode() === '23000');
                $message = $isDuplicate
                    ? 'Ya existe un cliente con ese documento.'
                    : 'No se pudo registrar el cliente.';

                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $message
                    ]);
                    exit;
                }

                header("Location: " . BASE_URL . "/hotel/clientes/create?error=" . ($isDuplicate ? "duplicate" : "save"));
                exit;
            }
        }
        
        require_once BASE_PATH . "/views/hotel/client_form.php";
    }
    
    public function view() {
        $id = $_GET["id"] ?? 0;
        
        $stmt = $this->hotelDb->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmt->execute([$id]);
        $client = $stmt->fetch();
        
        if (!$client) {
            header("Location: " . BASE_URL . "/hotel/clientes");
            exit;
        }
        
        $stmt = $this->hotelDb->prepare("
            SELECT r.*, h.numero_habitacion, h.tipo
            FROM reservas r
            JOIN habitaciones h ON r.habitacion_id = h.id
            WHERE r.cliente_id = ?
            ORDER BY r.fecha_entrada DESC
        ");
        $stmt->execute([$id]);
        $reservations = $stmt->fetchAll();
        
        require_once BASE_PATH . "/views/hotel/client_detail.php";
    }

    public function edit() {
        $id = $_GET["id"] ?? 0;

        $stmt = $this->hotelDb->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmt->execute([$id]);
        $client = $stmt->fetch();

        if (!$client) {
            header("Location: " . BASE_URL . "/hotel/clientes");
            exit;
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $stmt = $this->hotelDb->prepare("UPDATE clientes SET nombre = ?, documento = ?, email = ?, telefono = ?, ciudad = ?, pais = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([
                $_POST["nombre"],
                $_POST["documento"],
                $_POST["email"] ?? null,
                $_POST["telefono"] ?? null,
                $_POST["ciudad"] ?? null,
                $_POST["pais"] ?? null,
                $id
            ]);

            header("Location: " . BASE_URL . "/hotel/clientes?success=1");
            exit;
        }

        require_once BASE_PATH . "/views/hotel/client_form.php";
    }

    public function delete() {
        $id = $_GET["id"] ?? 0;

        if (!$id) {
            header("Location: " . BASE_URL . "/hotel/clientes");
            exit;
        }

        $stmt = $this->hotelDb->prepare("SELECT COUNT(*) as total FROM reservas WHERE cliente_id = ? AND estado IN ('reservada', 'ocupada')");
        $stmt->execute([$id]);
        $active = (int)($stmt->fetch()["total"] ?? 0);

        if ($active > 0) {
            header("Location: " . BASE_URL . "/hotel/clientes?error=reservas_activas");
            exit;
        }

        $stmt = $this->hotelDb->prepare("DELETE FROM clientes WHERE id = ?");
        $stmt->execute([$id]);

        header("Location: " . BASE_URL . "/hotel/clientes?success=deleted");
        exit;
    }
}
