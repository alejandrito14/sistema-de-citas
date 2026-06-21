<?php
require_once APP_ROOT . '/models/Cotizacion.php';
require_once APP_ROOT . '/models/Pago.php';
require_once APP_ROOT . '/models/PagoDetalle.php';
require_once APP_ROOT . '/models/PagoCuota.php';
require_once APP_ROOT . '/models/Configuracion.php';
require_once APP_ROOT . '/models/Paciente.php';
require_once APP_ROOT . '/config/Database.php';

class CotizacionController {

    public function index() {
        // Mostrar listado de cotizaciones
        $database = new Database();
        $db = $database->connect();
        $model = new Cotizacion($db);
        $configModel = new Configuracion($db);
        $empresa = $configModel->obtener();
        $cotizaciones = $model->listar();
        // Cargar datos necesarios para el modal de cobro (servicios, productos, pacientes)
        require_once APP_ROOT . '/models/Servicio.php';
        require_once APP_ROOT . '/models/Medicamento.php';
        require_once APP_ROOT . '/models/Paciente.php';
        $servicioModel = new Servicio($db);
        $medicamentoModel = new Medicamento($db);
        $pacienteModel = new Paciente($db);
        $listaServicios = $servicioModel->leerActivos();
        $listaMedicamentos = $medicamentoModel->leer();
        $listaPacientes = $pacienteModel->leer();
        require_once APP_ROOT . '/views/admin/cotizaciones.php';
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location:' . BASE_URL . '/citas'); exit; }
        if (session_status() === PHP_SESSION_NONE) session_start();
        $database = new Database();
        $db = $database->connect();
        $configModel = new Configuracion($db);
        $empresaCfg = $configModel->obtener();
        $model = new Cotizacion($db);
        $detalles = json_decode($_POST['detalles_json'] ?? '[]', true);
        $data = [
            'id_paciente' => $_POST['id_paciente'] ?? null,
            'vigencia_dias' => $_POST['vigencia_dias'] ?? 15,
            'subtotal' => $_POST['subtotal'] ?? 0,
            'descuento_tipo' => $_POST['descuento_tipo'] ?? 'fijo',
            'descuento_valor' => $_POST['descuento_valor'] ?? 0,
            'total' => $_POST['total'] ?? 0,
            'estado' => $_POST['estado'] ?? 'borrador',
            // usar la moneda enviada o la configurada en la empresa
            'moneda' => $_POST['moneda'] ?? ($empresaCfg['moneda'] ?? 'S/.'),
            'usuario_id' => $_SESSION['user_id'] ?? null,
            'observaciones' => $_POST['observaciones'] ?? null,
            'detalles' => is_array($detalles) ? $detalles : []
        ];

        // Soporte para actualización cuando se envía id_cotizacion
        if (!empty($_POST['id_cotizacion'])) {
            $idc = (int)$_POST['id_cotizacion'];
            $ok = $model->actualizar($idc, $data);
            $cotActual = $model->obtenerPorId($idc);
            $resp = ['success' => $ok, 'id' => $idc, 'updated' => $ok, 'folio' => $cotActual['folio'] ?? null];
            // si es AJAX devolver JSON, si no redirigir
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json'); echo json_encode($resp); return;
            }
            if ($ok) header('Location: ' . BASE_URL . '/citas?msg=actualizada');
            else header('Location: ' . BASE_URL . '/citas?msg=error');
            return;
        }

        // crear nueva cotización
        $id = $model->crear($data);
        $cot = $id ? $model->obtenerPorId($id) : null;
        $resp = ['success' => (bool)$id, 'id' => $id, 'created' => (bool)$id, 'folio' => $cot['folio'] ?? null];
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json'); echo json_encode($resp); return;
        }
        if ($id) header('Location: ' . BASE_URL . '/citas?msg=creada');
        else header('Location: ' . BASE_URL . '/citas?msg=error');
    }

    // Convertir cotización en pago/venta
    public function convertir() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = isset($_POST['id_cotizacion']) ? (int)$_POST['id_cotizacion'] : 0;
        // soporte preview via POST or GET: devolver datos de la cotización sin crear pago
        if (!$id && isset($_GET['id_cotizacion'])) {
            $id = (int)$_GET['id_cotizacion'];
        }
        $isPreview = (!empty($_POST['preview']) || !empty($_GET['preview'])) ? true : false;
        if (!$id) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'msg'=>'Falta id_cotizacion']); return; }

        $database = new Database();
        $db = $database->connect();
        $cotModel = new Cotizacion($db);
        $pagoModel = new Pago($db);
        $detalleModel = new PagoDetalle($db);

        $cot = $cotModel->obtenerPorId($id);
        if (!$cot) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'msg'=>'Cotización no encontrada']); return; }

        // Si se solicitó preview, devolver la cotización en JSON para precargar modal de cobro
        if ($isPreview) {
            // intentar enriquecer con nombre de paciente si está disponible
            if (!empty($cot['id_paciente'])) {
                try {
                    require_once APP_ROOT . '/models/Paciente.php';
                    $pacModel = new Paciente($db);
                    $p = $pacModel->obtenerPorId($cot['id_paciente']);
                    if ($p) $cot['paciente_nombre'] = $p['nombre'] ?? ($p['nombre_completo'] ?? null);
                } catch (Exception $e) { /* noop */ }
            }
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'cotizacion' => $cot]);
            return;
        }

        // Preparar datos de pago mínimos
        $datosPago = [
            'id_cita' => null,
            'monto' => $cot['total'] ?? 0,
            'descuento' => $cot['descuento_valor'] ?? 0,
            'metodo_pago' => 'Pendiente',
            'observaciones' => 'Aprobada desde cotización #' . $id,
            'id_cotizacion' => $id
        ];

        if ($pagoModel->registrar($datosPago)) {
            $id_pago = $db->lastInsertId();
            // copiar detalles
            foreach ($cot['detalles'] as $d) {
                $detalle = [
                    'tipo' => $d['tipo'] ?? null,
                    'referencia' => $d['id_referencia'] ?? null,
                    'descripcion' => $d['descripcion'] ?? null,
                    'cantidad' => $d['cantidad'] ?? 1,
                    'precio' => $d['precio'] ?? 0,
                    'total' => $d['total'] ?? 0
                ];
                $detalleModel->registrar($id_pago, $detalle);
            }
            // Marcar la cotización como aprobada al convertir a pago
            $cotModel->actualizarEstado($id, 'aprobada');
            header('Content-Type: application/json'); echo json_encode(['success'=>true,'id_pago'=>$id_pago]); return;
        } else {
            header('Content-Type: application/json'); echo json_encode(['success'=>false,'msg'=>'Error al crear pago']); return;
        }
    }

    public function imprimir() {
        // Imprimir cotización como PDF (si dompdf está instalado) o mostrar HTML
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) { header('HTTP/1.1 400 Bad Request'); echo 'ID inválido'; return; }
        $database = new Database();
        $db = $database->connect();
        $model = new Cotizacion($db);
        $cot = $model->obtenerPorId($id);
        if (!$cot) { header('HTTP/1.1 404 Not Found'); echo 'Cotización no encontrada'; return; }

        // Obtener configuración de empresa para usar la moneda configurada
        $configModel = new Configuracion($db);
        $empresa = $configModel->obtener();
        // la tabla `configuracion` usa `nombre_clinica`; soportar ambos campos por compatibilidad
        $empresaNombre = addslashes($empresa['nombre_clinica'] ?? $empresa['nombre'] ?? 'Mi Empresa');
        $empresaDireccion = addslashes($empresa['direccion'] ?? '');
        $empresaTelefono = addslashes($empresa['telefono'] ?? '');
        // Preferir la moneda de la cotización si existe, sino la moneda configurada en la empresa
        $moneda = $cot['moneda'] ?? ($empresa['moneda'] ?? 'S/.');

        $html = '<!doctype html><html><head><meta charset="utf-8"><title>Cotización</title><style>body{font-family:Arial,Helvetica,sans-serif;padding:20px;color:#000}h2{margin:0 0 10px}table{width:100%;border-collapse:collapse}th,td{border-bottom:1px solid #ddd;padding:8px;text-align:left}thead th{background:#f5f5f5}.right{text-align:right}.logo-img{max-height:80px}</style></head><body>';
        // Cabecera con tabla: datos a la izquierda, logo a la derecha
        $logoImg = '';
        if (!empty($empresa['logo'])) {
            $uploadsPath = realpath(APP_ROOT . '/../public/uploads');
            $logoFile = $uploadsPath ? $uploadsPath . '/' . $empresa['logo'] : null;
            if ($logoFile && file_exists($logoFile) && is_readable($logoFile)) {
                $imgData = file_get_contents($logoFile);
                if ($imgData !== false) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $logoFile);
                    finfo_close($finfo);
                    $base64 = base64_encode($imgData);
                    $dataUri = 'data:' . $mime . ';base64,' . $base64;
                    $logoImg = "<img src='" . $dataUri . "' class='logo-img' alt='Logo'>";
                }
            }
            // fallback a la URL pública si no se pudo incrustar
            if (empty($logoImg)) {
                $logoUrl = rtrim(BASE_URL, '/') . '/uploads/' . $empresa['logo'];
                $logoImg = "<img src='" . $logoUrl . "' class='logo-img' alt='Logo'>";
            }
        }

        $html .= '<table style="width:100%;"><tr>';
        $html .= "<td style='vertical-align:top;width:70%'><h2>{$empresaNombre}</h2><div>{$empresaDireccion}</div><div>{$empresaTelefono}</div></td>";
        $html .= "<td style='vertical-align:top;text-align:right;width:30%'>" . $logoImg . "</td>";
        $html .= '</tr></table><hr>';
        $pacienteText = '';
        if (!empty($cot['id_paciente'])) {
            // intentar cargar nombre de paciente si existe modelo
            if (class_exists('Paciente')) {
                try {
                    $pacModel = new Paciente($db);
                    $p = $pacModel->obtenerPorId($cot['id_paciente']);
                    if ($p) $pacienteText = $p['nombre'];
                } catch (Exception $e) { }
            }
        }
        $html .= "<div><strong>Paciente:</strong> " . ($pacienteText ?: '-') . "</div><div style='margin-top:8px'><strong>Observaciones:</strong> " . htmlspecialchars($cot['observaciones'] ?? '') . "</div>";
        $html .= "<table style='margin-top:16px'><thead><tr><th>Descripción</th><th style='width:80px'>Cant.</th><th class='right'>Precio</th><th class='right'>Total</th></tr></thead><tbody>";
        foreach ($cot['detalles'] as $it) {
            $html .= "<tr><td>" . htmlspecialchars($it['descripcion']) . "</td><td class='right'>" . (int)$it['cantidad'] . "</td><td class='right'>{$moneda} " . number_format($it['precio'],2) . "</td><td class='right'>{$moneda} " . number_format($it['total'],2) . "</td></tr>";
        }
        $html .= "</tbody></table>";
        $html .= "<div style='margin-top:12px;text-align:right'><div>Subtotal: {$moneda} " . number_format($cot['subtotal'] ?? 0,2) . "</div><div style='font-weight:bold;font-size:1.2rem'>TOTAL: {$moneda} " . number_format($cot['total'] ?? 0,2) . "</div></div>";
        $html .= "<div style='margin-top:24px;font-size:12px;color:#666'>Documento generado el " . date('d/m/Y H:i') . "</div>";
        $html .= "</body></html>";

        // Intentar usar dompdf si está instalado
        if (class_exists('\Dompdf\Dompdf')) {
            try {
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4','portrait');
                $dompdf->render();
                $dompdf->stream('cotizacion_' . $id . '.pdf', ['Attachment' => false]);
                return;
            } catch (Exception $e) {
                // continuar al fallback
            }
        }

        // Fallback: mostrar HTML para que el navegador lo imprima/guarde como PDF
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }
}
