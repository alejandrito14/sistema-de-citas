<?php
require_once APP_ROOT . '/config/Database.php';
require_once APP_ROOT . '/models/Especialidad.php';

class EspecialidadController {
    
    public function index() {
        // Seguridad: Solo Admin
        if (!isset($_SESSION['user_role_id']) || $_SESSION['user_role_id'] != 1) {
            header('Location: ' . BASE_URL . '/home');
            exit;
        }

        $database = new Database();
        $db = $database->connect();
        $especialidadModel = new Especialidad($db);
        
        $resultado = $especialidadModel->leer();
        
        require_once APP_ROOT . '/views/admin/especialidades.php';
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $database = new Database();
            $db = $database->connect();
            $especialidadModel = new Especialidad($db);

            if($especialidadModel->crear($_POST['nombre'])) {
                header('Location: ' . BASE_URL . '/especialidades?msg=creado');
            } else {
                header('Location: ' . BASE_URL . '/especialidades?msg=error');
            }
        }
    }

    public function actualizar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $database = new Database();
            $db = $database->connect();
            $especialidadModel = new Especialidad($db);

            if($especialidadModel->actualizar($_POST['id'], $_POST['nombre'])) {
                header('Location: ' . BASE_URL . '/especialidades?msg=actualizado');
            } else {
                header('Location: ' . BASE_URL . '/especialidades?msg=error');
            }
        }
    }

    public function eliminar() {
        if (isset($_GET['id'])) {
            $database = new Database();
            $db = $database->connect();
            $especialidadModel = new Especialidad($db);
            
            if ($especialidadModel->eliminar($_GET['id'])) {
                header('Location: ' . BASE_URL . '/especialidades?msg=eliminado');
            } else {
                // Si falla, asumimos que es porque está en uso
                header('Location: ' . BASE_URL . '/especialidades?msg=en_uso');
            }
        }
    }
}