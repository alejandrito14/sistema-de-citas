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
        $cuotaModel = new PagoCuota($db);

        // Filtros Visuales
        $fechaFiltro = isset($_GET['fecha']) && !empty($_GET['fecha']) ? $_GET['fecha'] : null;
        $estadoFiltro = isset($_GET['estado']) && !empty($_GET['estado']) ? $_GET['estado'] : null;

        // SEGURIDAD POR ROLES
        $rol = $_SESSION['user_role_id'] ?? 0;
        $idMedicoFiltro = ($rol == 2) ? ($_SESSION['medico_id'] ?? null) : null;
        $idPacienteFiltro = ($rol == 3) ? $_SESSION['user_id'] : null;

        // Obtener datos filtrados
        $resultado = $citaModel->leer($fechaFiltro, $estadoFiltro, $idMedicoFiltro, $idPacienteFiltro);

        // Obtener resumen de cuotas por cada cita (por id_pago)
        $cuotasResumen = [];
        if ($resultado && $resultado->rowCount() > 0) {
            // Guardar el cursor actual
            $rows = $resultado->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                if (!empty($row['id_pago'])) {
                    $cuotasResumen[$row['id_cita']] = $cuotaModel->obtenerResumenCuotas($row['id_pago']);
                }
            }
            // Volver a poner el resultado en modo iterador
            $resultado = new ArrayObject($rows);
        }

        // Datos para formularios
        $listaMedicos = $medicoModel->leer();
        $listaPacientes = $pacienteModel->leer();
        $empresa = $configModel->obtener();
        $listaServicios = $servicioModel->leerActivos();
        $listaMedicamentos = $medicamentoModel->leer();

        // Pasar $cuotasResumen a la vista
        require APP_ROOT . '/views/admin/citas.php';
    }

      public function ajaxCuotasCita() {
        if (!isset($_GET['id_pago'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Falta id_pago']);
            exit;
        }
    ;
        $database = new Database();
        $db = $database->connect();
        $cuotaModel = new PagoCuota($db);
        $cuotas = $cuotaModel->obtenerCuotasPorPago($_GET['id_pago']);
        header('Content-Type: application/json');
        echo json_encode($cuotas);
        exit;
    }

    // AJAX: Obtener detalle completo de pago/nota por id_cita
    public function ajaxDetallePago() {
        if (!isset($_GET['id_cita'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'msg' => 'Falta id_cita']);
            exit;
        }

        // Habilitar display de errores temporales para depuración
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        try {
            $id_cita = $_GET['id_cita'];
            $database = new Database();
            $db = $database->connect();
            $pagoModel = new Pago($db);
            $detalleModel = new PagoDetalle($db);
            $cuotaModel = new PagoCuota($db);
            $citaModel = new Cita($db);
            $configModel = new Configuracion($db);

            // Obtener pagos asociados a la cita
            $pagos = $pagoModel->obtenerPorCita($id_cita);
            if (!$pagos) $pagos = [];

            // Tomar el primer pago (si existe) para conceptos y cuotas
            $firstPago = count($pagos) > 0 ? $pagos[0] : null;
            $id_pago = $firstPago['id_pago'] ?? null;

            $conceptos = [];
            if ($id_pago) {
                $conceptos = $detalleModel->obtenerPorPago($id_pago);
            }

            // Historial de pagos: listar cada cuota pagada como fila separada.
            $historial = [];
            $total_pagado = 0;
            foreach ($pagos as $p) {
                $pCuotas = $cuotaModel->obtenerCuotasPorPago($p['id_pago']);
                if ($pCuotas && is_array($pCuotas) && count($pCuotas) > 0) {
                    foreach ($pCuotas as $pc) {
                        if (!empty($pc['pagada']) && $pc['pagada']) {
                            $fechaPago = isset($pc['fecha_pago']) && !empty($pc['fecha_pago']) ? date('d/m/Y', strtotime($pc['fecha_pago'])) : (isset($p['fecha_pago']) ? date('d/m/Y', strtotime($p['fecha_pago'])) : '');
                            $metodoPago = $pc['metodo_pago'] ?? $p['metodo_pago'] ?? $p['metodo'] ?? '';
                            $montoCuota = floatval($pc['monto']);
                            $historial[] = [
                                'fecha' => $fechaPago,
                                'metodo' => $metodoPago,
                                'monto' => $montoCuota,
                                'usuario' => $p['usuario'] ?? ($p['usuario_nombre'] ?? 'Admin')
                            ];
                            $total_pagado += $montoCuota;
                        }
                    }
                } else {
                    // Si no hay cuotas asociadas, mostrar el pago completo
                    $montoPago = floatval($p['monto'] ?? 0);
                    $historial[] = [
                        'fecha' => isset($p['fecha_pago']) ? date('d/m/Y', strtotime($p['fecha_pago'])) : $p['fecha_pago'] ?? '',
                        'metodo' => $p['metodo_pago'] ?? $p['metodo'] ?? '',
                        'monto' => $montoPago,
                        'usuario' => $p['usuario'] ?? ($p['usuario_nombre'] ?? 'Admin')
                    ];
                    $total_pagado += $montoPago;
                }
            }

            // Cuotas
            $cuotasData = [];
            $cuotasResumen = '0 de 0';
            if ($id_pago) {
                $cuotas = $cuotaModel->obtenerCuotasPorPago($id_pago);
                $totalCuotas = count($cuotas);
                $pagadas = 0;
                foreach ($cuotas as $c) if ($c['pagada']) $pagadas++;
                $cuotasResumen = "$pagadas de $totalCuotas";
                $cuotasData = $cuotas;
            }

            // Cita y especialista
            $cita = $citaModel->obtenerPorId($id_cita);

            $empresa = $configModel->obtener();

            // Total nota (sum de conceptos si existen) o monto del primer pago
            $total_nota = 0;
            if (!empty($conceptos)) {
                foreach ($conceptos as $c) $total_nota += floatval($c['total']);
            } elseif ($firstPago) {
                $total_nota = floatval($firstPago['monto'] ?? 0);
            }

            $data = [
                'paciente' => $cita['paciente'] ?? '',
                'nro_nota' => $firstPago ? ('NP-' . str_pad($firstPago['id_pago'], 6, '0', STR_PAD_LEFT)) : '',
                'fecha' => $firstPago && !empty($firstPago['fecha_pago']) ? date('d/m/Y', strtotime($firstPago['fecha_pago'])) : '',
                'estado_txt' => ($firstPago ? 'Pagada' : ($cita['estado'] ?? '')),
                'estado_badge' => ($firstPago ? 'bg-success' : 'bg-secondary'),
                'total_nota' => number_format($total_nota, 2, '.', ''),
                'total_pagado' => number_format($total_pagado, 2, '.', ''),
                'cuotas' => $cuotasResumen,
                'especialista' => $cita['medico'] ?? $cita['especialidad'] ?? '-',
                'conceptos' => $conceptos,
                'pagos' => $historial,
                'observaciones' => $firstPago['observaciones'] ?? '',
                'liquidada' => ($total_nota > 0 && $total_pagado >= $total_nota),
                'moneda' => $empresa['moneda'] ?? 'S/.'
            ];

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        } catch (\Throwable $t) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'msg' => $t->getMessage(), 'trace' => $t->getTraceAsString()]);
            exit;
        }
    }

    // Generar PDF del ticket/nota usando dompdf y abrir en nueva pestaña
    public function imprimirTicket() {
        if (!isset($_GET['id_cita'])) {
            echo 'Falta id_cita';
            exit;
        }
        $id_cita = $_GET['id_cita'];
        $database = new Database();
        $db = $database->connect();
        $pagoModel = new Pago($db);
        $detalleModel = new PagoDetalle($db);
        $cuotaModel = new PagoCuota($db);
        $citaModel = new Cita($db);
        $configModel = new Configuracion($db);

        $pagos = $pagoModel->obtenerPorCita($id_cita);
        $firstPago = count($pagos) > 0 ? $pagos[0] : null;
        $id_pago = $firstPago['id_pago'] ?? null;
        $conceptos = $id_pago ? $detalleModel->obtenerPorPago($id_pago) : [];
        $cuotas = $id_pago ? $cuotaModel->obtenerCuotasPorPago($id_pago) : [];
        $cita = $citaModel->obtenerPorId($id_cita);
        $empresa = $configModel->obtener();

        // Construir HTML simple para el PDF (adaptable)
        $moneda = $empresa['moneda'] ?? 'S/.';
        $html = '<!doctype html><html><head><meta charset="utf-8"><title>Nota de pago</title>';
        $html .= '<style>body{font-family:Arial,Helvetica,sans-serif;font-size:12px} .header{text-align:center} .small{font-size:11px} table{width:100%;border-collapse:collapse} th,td{padding:6px;border:1px solid #ddd}</style>';
        $html .= '</head><body>';
        if (!empty($empresa['logo'])) $html .= '<div class="header"><img src="' . BASE_URL . '/uploads/' . $empresa['logo'] . '" style="max-height:60px"></div>';
        $html .= '<h3 style="text-align:center">' . ($empresa['nombre_clinica'] ?? 'Clínica') . '</h3>';
        $html .= '<p class="small">' . ($empresa['direccion'] ?? '') . ' - Tel: ' . ($empresa['telefono'] ?? '') . '</p>';
        $html .= '<hr>';
        $html .= '<p><strong>Paciente:</strong> ' . ($cita['paciente'] ?? '') . '<br><strong>Nota:</strong> ' . ($firstPago ? ('NP-' . str_pad($firstPago['id_pago'],6,'0',STR_PAD_LEFT)) : '') . '<br><strong>Fecha:</strong> ' . ($firstPago['fecha_pago'] ?? '') . '</p>';

        $html .= '<h4>Conceptos</h4>';
        $html .= '<table><thead><tr><th>Concepto</th><th class="text-center">Cantidad</th><th>Precio</th><th>Subtotal</th></tr></thead><tbody>';
        $total = 0;
        foreach ($conceptos as $c) {
            $html .= '<tr><td>' . htmlspecialchars($c['descripcion']) . '</td><td class="text-center">' . $c['cantidad'] . '</td><td>' . $moneda . ' ' . number_format($c['precio'],2,'.','') . '</td><td>' . $moneda . ' ' . number_format($c['total'],2,'.','') . '</td></tr>';
            $total += floatval($c['total']);
        }
        $html .= '</tbody><tfoot><tr><th colspan="3" style="text-align:right">Total</th><th>' . $moneda . ' ' . number_format($total,2,'.','') . '</th></tr></tfoot></table>';

        $html .= '<h4>Historial de pagos</h4>';
        $html .= '<table><thead><tr><th>Pago</th><th>Fecha</th><th>Método</th><th>Monto</th></tr></thead><tbody>';
        $i=1; $pagado = 0;
        foreach ($pagos as $p) {
            $html .= '<tr><td>Pago #' . $i . '</td><td>' . ($p['fecha_pago'] ?? '') . '</td><td>' . ($p['metodo_pago'] ?? $p['metodo'] ?? '') . '</td><td>' . $moneda . ' ' . number_format($p['monto'],2,'.','') . '</td></tr>';
            $pagado += floatval($p['monto'] ?? 0);
            $i++;
        }
        $html .= '</tbody><tfoot><tr><th colspan="3" style="text-align:right">Total pagado</th><th>' . $moneda . ' ' . number_format($pagado,2,'.','') . '</th></tr></tfoot></table>';

        $html .= '<p class="small">Observaciones: ' . htmlspecialchars($firstPago['observaciones'] ?? '') . '</p>';
        $html .= '<p style="text-align:center;margin-top:30px"><small>*** Gracias por su preferencia ***</small></p>';
        $html .= '</body></html>';

        // Intentar usar dompdf si está disponible
        if (file_exists(APP_ROOT . '/../vendor/autoload.php')) {
            require_once APP_ROOT . '/../vendor/autoload.php';
            try {
              /*  $dompdf = new Dompdf\Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $dompdf->stream('nota_' . ($firstPago['id_pago'] ?? $id_cita) . '.pdf', ['Attachment' => false]);
                exit;*/
            } catch (Exception $e) {
                // Fallback: mostrar HTML
                echo $html; exit;
            }
        } else {
            // Mostrar HTML simple (usuario debe tener dompdf instalado para descargar PDF)
            echo $html; exit;
        }
    }

    // AJAX: Registrar pago de cuotas seleccionadas
    public function ajaxPagarCuotas() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            exit;
        }
            $ids = [];
            if (isset($_POST['cuotas'])) {
                if (is_array($_POST['cuotas'])) {
                    $ids = $_POST['cuotas'];
                } else if (is_string($_POST['cuotas'])) {
                    $ids = json_decode($_POST['cuotas'], true);
                }
            }
            $metodo = $_POST['metodo'] ?? '';
            $descuento = $_POST['descuento'] ?? 0;
            $observaciones = $_POST['observaciones'] ?? null;
            $comprobante = null;
            // Soporte para FormData
            if (empty($ids) && isset($_POST['cuotas'])) {
                $ids = json_decode(stripslashes($_POST['cuotas']), true);
            }
            if (!$ids || !$metodo) {
                echo json_encode(['success' => false, 'msg' => 'Datos incompletos']);
                exit;
            }
            if ($metodo === 'Transferencia' && isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION);
                $nombreArchivo = 'comprobante_' . uniqid() . '.' . $ext;
                $ruta = APP_ROOT . '/../public/uploads/' . $nombreArchivo;
                if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $ruta)) {
                    $comprobante = $nombreArchivo;
                }
            }
            $database = new Database();
            $db = $database->connect();
            $cuotaModel = new PagoCuota($db);
            $ok = $cuotaModel->marcarCuotasPagadas($ids, $metodo, $descuento, $observaciones, $comprobante);
            echo json_encode(['success' => $ok]);
            exit;
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
                'id_cita' => !empty($_POST['id_cita']) ? $_POST['id_cita'] : null,
                'monto' => $_POST['monto'],
                'descuento' => $_POST['descuento'] ?? 0,
                'metodo_pago' => $_POST['metodo_pago'],
                'observaciones' => $_POST['observaciones'],
                'id_cotizacion' => !empty($_POST['id_cotizacion']) ? (int)$_POST['id_cotizacion'] : null
            ];

            // Si se incluye id_cotizacion, adjuntar y luego marcarla como convertida
            $id_cotizacion = !empty($_POST['id_cotizacion']) ? (int)$_POST['id_cotizacion'] : null;

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

                // Si venía de una cotización, marcarla como convertida
                if ($id_cotizacion) {
                    try {
                        require_once APP_ROOT . '/models/Cotizacion.php';
                        $cotModel = new Cotizacion($db);
                        $cotModel->actualizarEstado($id_cotizacion, 'convertida');
                    } catch (Exception $e) {
                        // no bloquear el flujo si ocurre error
                    }
                }
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