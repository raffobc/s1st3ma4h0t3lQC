<?php
class SuperAdminController {
    private SuperUser $superUserModel;
    private Hotel $hotelModel;
    
    public function __construct() {
        $this->superUserModel = new SuperUser();
        $this->hotelModel = new Hotel();
    }
    
    private function checkAuth(): void {
        if (!isset($_SESSION["super_user_id"])) {
            header("Location: " . BASE_URL . "/super/login");
            exit;
        }
    }
    
    public function login(): void {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $email = $_POST["email"] ?? "";
            $password = $_POST["password"] ?? "";
            
            $user = $this->superUserModel->authenticate($email, $password);
            
            if ($user) {
                session_regenerate_id(true);
                $_SESSION["super_user_id"] = $user["id"];
                $_SESSION["super_user_name"] = $user["nombre"];
                $_SESSION["super_user_email"] = $user["email"];
                
                header("Location: " . BASE_URL . "/super/dashboard");
                exit;
            } else {
                $error = "Credenciales inválidas";
                require_once BASE_PATH . "/views/super/login.php";
                return;
            }
        }
        
        require_once BASE_PATH . "/views/super/login.php";
    }
    
    public function dashboard(): void {
        $this->checkAuth();
        $hotels = $this->hotelModel->getAllHotels();
        
        if (isset($_SESSION["success_message"])) {
            $success = $_SESSION["success_message"];
            unset($_SESSION["success_message"]);
        }
        
        require_once BASE_PATH . "/views/super/dashboard.php";
    }
    
    public function createHotel(): void {
        $this->checkAuth();
        
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $data = [
                "nombre" => $_POST["nombre"],
                "razon_social" => $_POST["razon_social"],
                "ruc" => $_POST["ruc"],
                "direccion" => $_POST["direccion"] ?? null,
                "telefono" => $_POST["telefono"] ?? null,
                "email" => $_POST["email"] ?? null,
                "ciudad" => $_POST["ciudad"] ?? null,
                "plan" => $_POST["plan"] ?? "basico",
                "max_habitaciones" => $_POST["max_habitaciones"] ?? 50,
                "admin_nombre" => $_POST["admin_nombre"],
                "admin_email" => $_POST["admin_email"],
                "admin_password" => $_POST["admin_password"],
                "admin_telefono" => $_POST["admin_telefono"] ?? null
            ];
            
            $hotelId = $this->hotelModel->createHotel($data);
            
            if ($hotelId) {
                $_SESSION["success_message"] = "Hotel creado exitosamente";
                header("Location: " . BASE_URL . "/super/dashboard");
                exit;
            } else {
                $error = "Error al crear el hotel";
            }
        }
        
        require_once BASE_PATH . "/views/super/create_hotel.php";
    }
    
    public function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
        }
        session_destroy();
        header("Location: " . BASE_URL . "/super/login");
        exit;
    }
}
?>
