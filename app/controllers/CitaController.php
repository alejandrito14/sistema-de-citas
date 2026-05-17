<?php
require_once APP_ROOT . '/config/Database.php';
require_once APP_ROOT . '/models/Cita.php';
require_once APP_ROOT . '/models/Medico.php';
require_once APP_ROOT . '/models/Paciente.php';
require_once APP_ROOT . '/models/Configuracion.php';
require_once APP_ROOT . '/models/Servicio.php';
require_once APP_ROOT . '/models/Pago.php';

require_once APP_ROOT . '/models/Auditoria.php';
require_once APP_ROOT . '/models/PagoDetalle.php';
require_once APP_ROOT . '/models/PagoCuota.php';

class CitaController {
    
    public function index() {
        
        if (session_status() === PHP_SESSION_NONE) session_start();
        $database = new Database();
        $db = $database->connect();
        
        $citaModel = new Cita($db);
        $medicoModel = new Medico($db);
        $pacienteModel = new Paciente($db);
        $configModel = new Configuracion($db);
        $servicioModel = new Servicio($db);
        $medicamentoModel = new Medicamento($db);
        
        // Filtros Visuales
        $fechaFiltro = isset($_GET['fecha']) && !empty($_GET['fecha']) ? $_GET['fecha'] : null;
        $estadoFiltro = isset($_GET['estado']) && !empty($_GET['estado']) ? $_GET['estado'] : null;

        // SEGURIDAD POR ROLES
        $rol = $_SESSION['user_role_id'] ?? 0;
        $idMedicoFiltro = ($rol == 2) ? ($_SESSION['medico_id'] ?? null) : null;
        $idPacienteFiltro = ($rol == 3) ? $_SESSION['user_id'] : null;

        // Obtener datos filtrados
        $resultado = $citaModel->leer($fechaFiltro, $estadoFiltro, $idMedicoFiltro, $idPacienteFiltro);
        
        // Datos para formularios
        $listaMedicos = $medicoModel->leer();
        $listaPacientes = $pacienteModel->leer();
        $empresa = $configModel->obtener();
        $listaServicios = $servicioModel->leerActivos();
        $listaMedicamentos = $medicamentoModel->leer();
        
        require_once APP_ROOT . '/views/admin/citas.php';
    }

    // API JSON para Calendario
    public function listarEventos() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $database = new Database();
        $db = $database->connect();
        $citaModel = new Cita($db);

        $rol = $_SESSION['user_role_id'] ?? 0;
        $idMedicoFiltro = ($rol == 2) ? ($_SESSION['medico_id'] ?? null) : null;
        $idPacienteFiltro = ($rol == 3) ? $_SESSION['user_id'] : null;

        $citas = $citaModel->leer(null, null, $idMedicoFiltro, $idPacienteFiltro);
        $eventos = [];

        while($row = $citas->fetch(PDO::FETCH_ASSOC)) {
            $color = '#ffc107'; // Pendiente
            if($row['estado'] == 'Confirmada') $color = '#0d6efd';
            if($row['estado'] == 'Finalizada') $color = '#198754';
            if($row['estado'] == 'Cancelada') $color = '#dc3545';

            $servicioTxt = $row['nombre_servicio'] ? ' - ' . $row['nombre_servicio'] : '';
            $titulo = ($rol == 3) ? 'Cita' . $servicioTxt : $row['paciente'] . $servicioTxt;

            $eventos[] = [
                'id' => $row['id_cita'],
                'title' => $titulo,
                'start' => $row['fecha_cita'],
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'medico' => $row['medico'],
                    'estado' => $row['estado'],
                    'motivo' => $row['motivo']
                ]
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($eventos);
        exit;
    }

    // Validación de Negocio
    private function validarReglasNegocio($fechaHora, $id_medico) {
        $fechaIngresada = strtotime($fechaHora);
        $fechaActual = time();
        
        // 1. No permitir fechas pasadas
        if ($fechaIngresada < ($fechaActual - 300)) return 'pasado';

        // 2. Validar Horario Médico
        $database = new Database();
        $db = $database->connect();
        $medicoModel = new Medico($db);

        if (!$medicoModel->verificaHorarioLaboral($id_medico, $fechaHora)) {
            return 'fuera_horario';
        }

        return 'ok';
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $database = new Database();
            $db = $database->connect();
            $citaModel = new Cita($db);
            $auditoria = new Auditoria($db);

            $rol = $_SESSION['user_role_id'] ?? 0;
            
            $datos = [
                'id_paciente' => ($rol == 3) ? $_SESSION['user_id'] : $_POST['paciente_id'],
                'id_medico' => ($rol == 2) ? $_SESSION['medico_id'] : $_POST['medico_id'],
                'id_servicio' => $_POST['id_servicio'],
                'fecha_cita' => $_POST['fecha'],
                'motivo' => $_POST['motivo']
            ];

            $validacion = $this->validarReglasNegocio($datos['fecha_cita'], $datos['id_medico']);
            if($validacion != 'ok') { header('Location: ' . BASE_URL . '/citas?msg=' . $validacion); exit; }

            if ($citaModel->verificarDisponibilidad($datos['id_medico'], $datos['fecha_cita'])) {
                header('Location: ' . BASE_URL . '/citas?msg=ocupado'); exit;
            }

            if($citaModel->crear($datos)) {
                $auditoria->registrar($_SESSION['user_id'], 'CREAR', 'citas', 0, 'Cita Agendada');
                header('Location: ' . BASE_URL . '/citas?msg=creado');
            } else {
                header('Location: ' . BASE_URL . '/citas?msg=error');
            }
        }
    }

    public function actualizar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $database = new Database();
            $db = $database->connect();
            $citaModel = new Cita($db);
            $auditoria = new Auditoria($db);
            $rol = $_SESSION['user_role_id'] ?? 0;

            $datos = [
                'id_cita' => $_POST['id_cita'],
                'id_medico' => ($rol == 2) ? $_SESSION['medico_id'] : $_POST['medico_id'],
                'id_servicio' => $_POST['id_servicio'],
                'fecha_cita' => $_POST['fecha'],
                'motivo' => $_POST['motivo'],
                'estado' => $_POST['estado']
            ];

            if ($datos['estado'] == 'Pendiente' || $datos['estado'] == 'Confirmada') {
                $validacion = $this->validarReglasNegocio($datos['fecha_cita'], $datos['id_medico']);
                if($validacion != 'ok') { header('Location: ' . BASE_URL . '/citas?msg=' . $validacion); exit; }
            }

            if ($citaModel->verificarDisponibilidad($datos['id_medico'], $datos['fecha_cita'], $datos['id_cita'])) {
                header('Location: ' . BASE_URL . '/citas?msg=ocupado'); exit;
            }

            if($citaModel->actualizar($datos)) {
                $auditoria->registrar($_SESSION['user_id'], 'ACTUALIZAR', 'citas', $datos['id_cita'], 'Estado: '.$datos['estado']);
                header('Location: ' . BASE_URL . '/citas?msg=actualizado');
            } else {
                header('Location: ' . BASE_URL . '/citas?msg=error');
            }
        }
    }

    public function finalizar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $database = new Database();
            $db = $database->connect();
            $citaModel = new Cita($db);
            $auditoria = new Auditoria($db);

            // Datos Triaje y Reposo
            $id = $_POST['id_cita'];
            $dias = !empty($_POST['dias_reposo']) ? (int)$_POST['dias_reposo'] : 0;
            $fin_reposo = ($dias > 0) ? date('Y-m-d', strtotime("+$dias days")) : null;

            $params = [
                $_POST['id_cita'],
                $_POST['diagnostico'],
                $_POST['prescripcion'],
                $_POST['peso'] ?? null,
                $_POST['talla'] ?? null,
                $_POST['presion'] ?? null,
                $_POST['temperatura'] ?? null,
                $dias,
                $fin_reposo
            ];

            if($citaModel->finalizarAtencion(...$params)) {
                $auditoria->registrar($_SESSION['user_id'], 'ATENCION', 'citas', $id, 'Consulta Finalizada');
                header('Location: ' . BASE_URL . '/citas?msg=atendido');
            } else {
                header('Location: ' . BASE_URL . '/citas?msg=error');
            }
        }
    }

    public function cobrar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $database = new Database();
            $db = $database->connect();
            $pagoModel = new Pago($db);
            $detalleModel = new PagoDetalle($db);
            $cuotaModel = new PagoCuota($db);
            $auditoria = new Auditoria($db);

            $datos = [
                'id_cita' => $_POST['id_cita'],
                'monto' => $_POST['monto'],
                'descuento' => $_POST['descuento'] ?? 0,
                'metodo_pago' => $_POST['metodo_pago'],
                'observaciones' => $_POST['observaciones']
            ];

            // Registrar pago principal
            if($pagoModel->registrar($datos)) {
                // Obtener el último id_pago insertado
                $id_pago = $db->lastInsertId();

                // Registrar detalles
                $detalles = json_decode($_POST['detalle_json'] ?? '[]', true);
                if (is_array($detalles)) {
                    foreach ($detalles as $detalle) {
                        $detalleModel->registrar($id_pago, $detalle);
                    }
                }

                // Registrar cuotas si existen
                $cuotas = json_decode($_POST['cuotas_json'] ?? '[]', true);
                if (is_array($cuotas) && count($cuotas) > 0) {
                    foreach ($cuotas as $cuota) {
                        $cuotaModel->registrar($id_pago, $cuota);
                    }
                }

                $auditoria->registrar($_SESSION['user_id'], 'PAGO', 'pagos', $id_pago, 'Cobro: ' . $datos['monto']);
                header('Location: ' . BASE_URL . '/citas?msg=pagado');
            } else {
                header('Location: ' . BASE_URL . '/citas?msg=error');
            }
        }
    }

    public function eliminar() {
        if (isset($_GET['id'])) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            
            // SEGURIDAD: Solo Admin puede borrar historial
            if ($_SESSION['user_role_id'] != 1) { 
                header('Location: ' . BASE_URL . '/citas?msg=error_permisos'); 
                exit; 
            }

            $database = new Database();
            $db = $database->connect();
            $citaModel = new Cita($db);
            $auditoria = new Auditoria($db);

            if ($citaModel->eliminar($_GET['id'])) {
                $auditoria->registrar($_SESSION['user_id'], 'ELIMINAR', 'citas', $_GET['id'], 'Cita Borrada');
                header('Location: ' . BASE_URL . '/citas?msg=eliminado');
            } else {
                header('Location: ' . BASE_URL . '/citas?msg=error');
            }
        }
    }
}