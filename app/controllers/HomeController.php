<?php
require_once APP_ROOT . '/config/Database.php';
require_once APP_ROOT . '/models/Cita.php';
require_once APP_ROOT . '/models/Medico.php';
require_once APP_ROOT . '/models/Paciente.php';

class HomeController {
    
    public function index() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $database = new Database();
        $db = $database->connect();
        
        $citaModel = new Cita($db);
        $medicoModel = new Medico($db);
        $pacienteModel = new Paciente($db);
        
        $rol = $_SESSION['user_role_id'] ?? 0;
        $userId = $_SESSION['user_id'];
        $medicoId = $_SESSION['medico_id'] ?? null;

        $data = [];
        $hoy = date('Y-m-d');

        // --- LÓGICA SEGÚN ROL ---

        // CASO 1: ADMINISTRADOR (1) Y RECEPCIONISTA (4)
        // Ambos ven el resumen global
        if ($rol == 1 || $rol == 4) { 
            
            $data['total_citas'] = $citaModel->contarTotal();
            $data['total_medicos'] = $medicoModel->leer()->rowCount();
            $data['total_pacientes'] = $pacienteModel->leer()->rowCount();
            
            $data['citas_hoy'] = $citaModel->leer($hoy);
            
            $stats = $citaModel->obtenerEstadisticasEstado();
            $data['chart_labels'] = [];
            $data['chart_data'] = [];
            $data['chart_colors'] = [];
            
            foreach($stats as $stat) {
                $data['chart_labels'][] = $stat['estado'];
                $data['chart_data'][] = $stat['cantidad'];
                if($stat['estado'] == 'Pendiente') $data['chart_colors'][] = '#ffc107';
                elseif($stat['estado'] == 'Confirmada') $data['chart_colors'][] = '#0d6efd';
                elseif($stat['estado'] == 'Finalizada') $data['chart_colors'][] = '#198754';
                elseif($stat['estado'] == 'Cancelada') $data['chart_colors'][] = '#dc3545';
            }

        } elseif ($rol == 2) { 
            // === MÉDICO ===
            $data['citas_hoy'] = $citaModel->leer($hoy, null, $medicoId);
            $data['total_mis_citas'] = $citaModel->contarTotal($medicoId);
            
            $atendidosHoy = 0;
            $pendientesHoy = 0;
            
            $citasDia = $citaModel->leer($hoy, null, $medicoId);
            while($c = $citasDia->fetch(PDO::FETCH_ASSOC)) {
                if($c['estado'] == 'Finalizada') $atendidosHoy++;
                if($c['estado'] == 'Pendiente' || $c['estado'] == 'Confirmada') $pendientesHoy++;
            }
            $data['atendidos_hoy'] = $atendidosHoy;
            $data['pendientes_hoy'] = $pendientesHoy;

        } elseif ($rol == 3) { 
            // === PACIENTE ===
            $misCitas = $citaModel->leer(null, null, null, $userId);
            $proximaCita = null;
            
            while($row = $misCitas->fetch(PDO::FETCH_ASSOC)) {
                if ($row['fecha_cita'] >= date('Y-m-d H:i:s') && $row['estado'] != 'Cancelada' && $row['estado'] != 'Finalizada') {
                    $proximaCita = $row;
                    break;
                }
            }
            $data['proxima_cita'] = $proximaCita;
            $data['total_historial'] = $citaModel->contarTotal(null, $userId);
        }
        
        require_once APP_ROOT . '/views/admin/dashboard.php';
    }
}