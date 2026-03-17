<?php
class HotelUsersController {
    private PDO $db;

    public function __construct() {
        $this->db = MasterDatabase::getConnection();
        $this->checkAuth();
        $this->checkAdmin();
    }

    private function checkAuth(): void {
        if (!isset($_SESSION['hotel_user_id'])) {
            header('Location: ' . BASE_URL . '/hotel/login');
            exit;
        }
    }

    private function checkAdmin(): void {
        if (($_SESSION['hotel_user_role'] ?? 'staff') !== 'admin') {
            header('Location: ' . BASE_URL . '/hotel/dashboard?error=acceso');
            exit;
        }
    }

    private function isValidCsrfToken(?string $token): bool {
        return !empty($token)
            && !empty($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    public function index(): void {
        $stmt = $this->db->query("SELECT id, nombre, email, rol, activo, created_at, updated_at FROM usuarios ORDER BY created_at DESC");
        $users = $stmt->fetchAll();

        require_once BASE_PATH . '/views/hotel/users.php';
    }

    public function create(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/hotel/usuarios');
            exit;
        }

        if (!$this->isValidCsrfToken($_POST['csrf_token'] ?? null)) {
            header('Location: ' . BASE_URL . '/hotel/usuarios?error=csrf');
            exit;
        }

        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $rol = trim((string)($_POST['rol'] ?? 'staff'));

        $rolesPermitidos = ['admin', 'staff', 'recepcion'];

        if ($nombre === '' || $email === '' || $password === '') {
            header('Location: ' . BASE_URL . '/hotel/usuarios?error=datos');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ' . BASE_URL . '/hotel/usuarios?error=email');
            exit;
        }

        if (strlen($password) < 6) {
            header('Location: ' . BASE_URL . '/hotel/usuarios?error=pass');
            exit;
        }

        if (!in_array($rol, $rolesPermitidos, true)) {
            $rol = 'staff';
        }

        try {
            $stmt = $this->db->prepare('INSERT INTO usuarios (nombre, email, password, rol, activo) VALUES (?, ?, ?, ?, 1)');
            $stmt->execute([$nombre, $email, password_hash($password, PASSWORD_BCRYPT), $rol]);

            header('Location: ' . BASE_URL . '/hotel/usuarios?success=created');
            exit;
        } catch (PDOException $e) {
            header('Location: ' . BASE_URL . '/hotel/usuarios?error=duplicate');
            exit;
        }
    }

    public function toggleStatus(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/hotel/usuarios');
            exit;
        }

        if (!$this->isValidCsrfToken($_POST['csrf_token'] ?? null)) {
            header('Location: ' . BASE_URL . '/hotel/usuarios?error=csrf');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $activo = (int)($_POST['activo'] ?? 0);

        if ($id <= 0) {
            header('Location: ' . BASE_URL . '/hotel/usuarios?error=datos');
            exit;
        }

        if ($id === (int)($_SESSION['hotel_user_id'] ?? 0) && $activo === 0) {
            header('Location: ' . BASE_URL . '/hotel/usuarios?error=self');
            exit;
        }

        $stmt = $this->db->prepare('UPDATE usuarios SET activo = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$activo === 1 ? 1 : 0, $id]);

        header('Location: ' . BASE_URL . '/hotel/usuarios?success=updated');
        exit;
    }
}
?>
