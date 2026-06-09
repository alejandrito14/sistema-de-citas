<?php
require_once APP_ROOT . '/config/Database.php';
require_once APP_ROOT . '/models/Paciente.php';

class PacienteController {
    
    public function index() {
        $database = new Database();
        $db = $database->connect();
        $pacienteModel = new Paciente($db);
        $resultado = $pacienteModel->leer();
        require_once APP_ROOT . '/views/admin/pacientes.php';
    }

    public function historial() {
        if (isset($_GET['id'])) {
            $database = new Database();
            $db = $database->connect();
            $pacienteModel = new Paciente($db);
            
            $id_paciente = $_GET['id'];
            $paciente = $pacienteModel->obtenerPorId($id_paciente);
            
            if (!$paciente) { header('Location: ' . BASE_URL . '/pacientes'); exit; }

            $historial = $pacienteModel->obtenerHistorial($id_paciente);
            $archivos = $pacienteModel->obtenerArchivos($id_paciente);
            $evolucion = $pacienteModel->obtenerEvolucion($id_paciente);
            
            $fechas = []; $pesos = []; $temperaturas = [];
            foreach($evolucion as $evo) {
                $fechas[] = date('d/m/y', strtotime($evo['fecha_cita']));
                $pesos[] = $evo['peso'];
                $temperaturas[] = $evo['temperatura'];
            }

            require_once APP_ROOT . '/views/admin/historial_clinico.php';
        } else {
            header('Location: ' . BASE_URL . '/pacientes');
        }
    }

    public function subirArchivo() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!isset($_FILES['documento']) || $_FILES['documento']['error'] == UPLOAD_ERR_INI_SIZE) {
                $id = $_POST['id_paciente'] ?? ''; 
                header('Location: ' . BASE_URL . '/pacientes/historial?id=' . $id . '&msg=error_tamano');
                exit;
            }

            $database = new Database();
            $db = $database->connect();
            $pacienteModel = new Paciente($db);

            $id_paciente = $_POST['id_paciente'];
            $archivo = $_FILES['documento'];
            $nombre_original = $archivo['name'];
            $tmp_name = $archivo['tmp_name'];
            $error = $archivo['error'];

            if ($error === 0) {
                $ext_permitidas = ['jpg', 'jpeg', 'png', 'pdf'];
                $ext_archivo = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

                if (in_array($ext_archivo, $ext_permitidas)) {
                    $carpeta_destino = APP_ROOT . '/../public/uploads/';
                    if (!file_exists($carpeta_destino)) { mkdir($carpeta_destino, 0777, true); }

                    $nombre_nuevo = uniqid('DOC_', true) . '.' . $ext_archivo;
                    $destino_final = $carpeta_destino . $nombre_nuevo;

                    if (move_uploaded_file($tmp_name, $destino_final)) {
                        $pacienteModel->registrarArchivo($id_paciente, $nombre_original, $nombre_nuevo, $ext_archivo);
                        header('Location: ' . BASE_URL . '/pacientes/historial?id=' . $id_paciente . '&msg=subido');
                    } else {
                        header('Location: ' . BASE_URL . '/pacientes/historial?id=' . $id_paciente . '&msg=error_upload');
                    }
                } else {
                    header('Location: ' . BASE_URL . '/pacientes/historial?id=' . $id_paciente . '&msg=error_formato');
                }
            } else {
                header('Location: ' . BASE_URL . '/pacientes/historial?id=' . $id_paciente . '&msg=error_general');
            }
        }
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $database = new Database();
            $db = $database->connect();
            $pacienteModel = new Paciente($db);

            $datos = [
                'nombre' => $_POST['nombre'],
                'dni' => $_POST['dni'], // Nuevo
                'email' => $_POST['email'],
                'telefono' => $_POST['telefono'],
                'sangre' => $_POST['sangre'],
                'alergias' => $_POST['alergias'],
                'cronicas' => $_POST['cronicas'],
                'password' => $_POST['password']
            ];

            if($pacienteModel->crear($datos)) {
                header('Location: ' . BASE_URL . '/pacientes?msg=creado');
            } else {
                header('Location: ' . BASE_URL . '/pacientes?msg=error');
            }
        }
    }

    public function actualizar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $database = new Database();
            $db = $database->connect();
            $pacienteModel = new Paciente($db);

            $datos = [
                'id' => $_POST['id_usuario'],
                'nombre' => $_POST['nombre'],
                'dni' => $_POST['dni'], // Nuevo
                'email' => $_POST['email'],
                'telefono' => $_POST['telefono'],
                'sangre' => $_POST['sangre'],
                'alergias' => $_POST['alergias'],
                'cronicas' => $_POST['cronicas'],
                'password' => $_POST['password']
            ];

            if($pacienteModel->actualizar($datos)) {
                header('Location: ' . BASE_URL . '/pacientes?msg=actualizado');
            } else {
                header('Location: ' . BASE_URL . '/pacientes?msg=error');
            }
        }
    }

    public function eliminar() {
        if (isset($_GET['id'])) {
            $database = new Database();
            $db = $database->connect();
            $pacienteModel = new Paciente($db);
            
            if ($pacienteModel->eliminar($_GET['id'])) {
                header('Location: ' . BASE_URL . '/pacientes?msg=eliminado');
            } else {
                header('Location: ' . BASE_URL . '/pacientes?msg=error');
            }
        }
    }

    // Endpoint AJAX para búsqueda de pacientes (select2)
    public function buscarAjax() {
        $q = $_GET['q'] ?? '';
        $database = new Database();
        $db = $database->connect();
        $pacienteModel = new Paciente($db);
        $results = [];
        if ($q !== '') {
            $rows = $pacienteModel->buscar($q, 50);
            foreach ($rows as $r) {
                $results[] = ['id' => $r['id_usuario'], 'text' => $r['nombre'] . ' - ' . ($r['telefono'] ?? $r['documento_identidad'] ?? '')];
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
        exit;
    }
}