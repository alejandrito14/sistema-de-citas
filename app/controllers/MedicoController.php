<?php
require_once APP_ROOT . '/config/Database.php';
require_once APP_ROOT . '/models/Medico.php';

class MedicoController {
    
    public function index() {
        // Seguridad
        if (!isset($_SESSION['user_role_id']) || $_SESSION['user_role_id'] != 1) { header('Location: ' . BASE_URL . '/home'); exit; }

        $database = new Database();
        $db = $database->connect();
        $medicoModel = new Medico($db);
        
        $resultado = $medicoModel->leer();
        $especialidades = $medicoModel->obtenerEspecialidades();
        
        require_once APP_ROOT . '/views/admin/medicos.php';
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $database = new Database();
            $db = $database->connect();
            $medicoModel = new Medico($db);

            $datos = [
                'nombre' => $_POST['nombre'],
                'email' => $_POST['email'],
                'password' => $_POST['password'],
                'id_especialidad' => $_POST['id_especialidad']
            ];

            if($medicoModel->crear($datos)) {
                header('Location: ' . BASE_URL . '/medicos?msg=creado');
            } else {
                header('Location: ' . BASE_URL . '/medicos?msg=error');
            }
        }
    }

    // NUEVO: GESTIONAR HORARIOS (VISTA)
    public function horarios() {
        if (!isset($_GET['id'])) { header('Location: ' . BASE_URL . '/medicos'); exit; }
        
        $database = new Database();
        $db = $database->connect();
        $medicoModel = new Medico($db);

        $id_medico = $_GET['id'];
        $medico = $medicoModel->obtenerPorId($id_medico);
        $horarios = $medicoModel->obtenerHorarios($id_medico);

        require_once APP_ROOT . '/views/admin/horarios_medico.php';
    }

    // NUEVO: GUARDAR HORARIO
    public function guardarHorario() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $database = new Database();
            $db = $database->connect();
            $medicoModel = new Medico($db);

            $id = $_POST['id_medico'];
            if($medicoModel->agregarHorario($id, $_POST['dia'], $_POST['inicio'], $_POST['fin'])) {
                header('Location: ' . BASE_URL . '/medicos/horarios?id=' . $id . '&msg=guardado');
            } else {
                header('Location: ' . BASE_URL . '/medicos/horarios?id=' . $id . '&msg=error');
            }
        }
    }

    // NUEVO: ELIMINAR HORARIO
    public function eliminarHorario() {
        if (isset($_GET['id']) && isset($_GET['id_medico'])) {
            $database = new Database();
            $db = $database->connect();
            $medicoModel = new Medico($db);
            
            if($medicoModel->eliminarHorario($_GET['id'])) {
                header('Location: ' . BASE_URL . '/medicos/horarios?id=' . $_GET['id_medico'] . '&msg=eliminado');
            } else {
                header('Location: ' . BASE_URL . '/medicos/horarios?id=' . $_GET['id_medico'] . '&msg=error');
            }
        }
    }
}