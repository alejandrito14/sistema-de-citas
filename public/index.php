
<?php
// 1. INICIO DE CONFIGURACIÓN
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Mostrar errores (Cambiar a 0 en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Mexico_City');

// 2. DETECCIÓN AUTOMÁTICA DE LA URL BASE
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
$scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$scriptPath = rtrim($scriptPath, '/'); 

// Definir constantes globales
define('BASE_URL', $protocol . $domainName . $scriptPath);
define('APP_ROOT', dirname(__DIR__) . '/app');

// Cargar autoload de Composer si existe (dompdf y otras dependencias)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// 3. AUTOCARGA INTELIGENTE DE CONTROLADORES
$controladores = [
    'AuthController', 
    'HomeController', 
    'CitaController', 
    'CotizacionController', 
    'MedicoController', 
    'PacienteController', 
    'ServicioController', 
    'PagoController', 
    'ConfiguracionController', 
    'PerfilController',
    'ReporteController',
    'AuditoriaController',
    'EspecialidadController',
    'MedicamentoController'
];

foreach ($controladores as $controlador) {
    $archivo = APP_ROOT . '/controllers/' . $controlador . '.php';
    if (file_exists($archivo)) {
        require_once $archivo;
    }
}

// Rutas AJAX para cuotas (debe ir después de cargar los controladores)
if (isset($_GET['ajax']) && $_GET['ajax'] === 'cuotas_cita') {
    (new CitaController())->ajaxCuotasCita();
    exit;
}
if (isset($_GET['ajax']) && $_GET['ajax'] === 'pagar_cuotas') {
    (new CitaController())->ajaxPagarCuotas();
    exit;
}
// AJAX: detalle de pago
if (isset($_GET['ajax']) && $_GET['ajax'] === 'detalle_pago') {
    (new CitaController())->ajaxDetallePago();
    exit;
}
// AJAX: buscar pacientes para select2
if (isset($_GET['ajax']) && $_GET['ajax'] === 'buscar_pacientes') {
    (new PacienteController())->buscarAjax();
    exit;
}

// 4. LÓGICA DE ENRUTAMIENTO (ROUTER)
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME']; 
$base_path = dirname($script_name);

if (strpos($request_uri, $base_path) === 0) {
    $url = substr($request_uri, strlen($base_path));
} else {
    $url = $request_uri;
}

$url = trim($url, '/');
if (($pos = strpos($url, '?')) !== false) {
    $url = substr($url, 0, $pos);
}

$urlArray = explode('/', $url);
// Controlador por defecto
$controllerName = !empty($urlArray[0]) ? $urlArray[0] : 'home';

// 5. MIDDLEWARE DE SEGURIDAD
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Si no está logueado y trata de entrar a algo privado -> Login
if (!$userId && $controllerName !== 'login' && $controllerName !== 'auth') {
    $controllerName = 'login'; 
}

// Si ya está logueado e intenta ir al login -> Home
if ($userId && $controllerName === 'login') {
    header('Location: ' . BASE_URL . '/home');
    exit;
}

// 6. DISPATCHER (INTERRUPTOR DE RUTAS)
switch ($controllerName) {
    
    case 'auth':
        if (class_exists('AuthController')) {
            $controller = new AuthController();
            if (isset($urlArray[1]) && $urlArray[1] == 'authenticate') $controller->authenticate();
            elseif (isset($urlArray[1]) && $urlArray[1] == 'logout') $controller->logout();
        }
        break;

    case 'login':
        if (class_exists('AuthController')) {
            $controller = new AuthController();
            $controller->login();
        }
        break;

    case 'home':
        if (class_exists('HomeController')) {
            $controller = new HomeController();
            $controller->index();
        }
        break;

    case 'citas':
        if (class_exists('CitaController')) {
            $controller = new CitaController();
            if (isset($urlArray[1])) {
                if ($urlArray[1] == 'ajaxDetallePago') $controller->ajaxDetallePago();
                elseif ($urlArray[1] == 'imprimirTicket') $controller->imprimirTicket();
                if ($urlArray[1] == 'guardar') $controller->guardar();
                elseif ($urlArray[1] == 'actualizar') $controller->actualizar();
                elseif ($urlArray[1] == 'finalizar') $controller->finalizar();
                elseif ($urlArray[1] == 'eliminar') $controller->eliminar();
                elseif ($urlArray[1] == 'listarEventos') $controller->listarEventos();
                elseif ($urlArray[1] == 'cobrar') $controller->cobrar(); // Ruta de Pagos
                else $controller->index();
            } else {
                $controller->index();
            }
        }
        break;

    case 'cotizaciones':
        if (class_exists('CotizacionController')) {
            $controller = new CotizacionController();
            if (isset($urlArray[1])) {
                if ($urlArray[1] == 'guardar') $controller->guardar();
                elseif ($urlArray[1] == 'convertir') $controller->convertir();
                elseif ($urlArray[1] == 'imprimir') $controller->imprimir();
                else $controller->index();
            } else {
                $controller->index();
            }
        }
        break;

    case 'medicos':
        if (class_exists('MedicoController')) {
            $controller = new MedicoController();
            if (isset($urlArray[1])) {
                if ($urlArray[1] == 'guardar') $controller->guardar();
                elseif ($urlArray[1] == 'horarios') $controller->horarios();
                elseif ($urlArray[1] == 'guardarHorario') $controller->guardarHorario();
                elseif ($urlArray[1] == 'eliminarHorario') $controller->eliminarHorario();
                else $controller->index();
            } else {
                $controller->index();
            }
        }
        break;

    case 'pacientes':
        if (class_exists('PacienteController')) {
            $controller = new PacienteController();
            if (isset($urlArray[1])) {
                if ($urlArray[1] == 'guardar') $controller->guardar();
                elseif ($urlArray[1] == 'actualizar') $controller->actualizar(); 
                elseif ($urlArray[1] == 'eliminar') $controller->eliminar(); 
                elseif ($urlArray[1] == 'historial') $controller->historial();
                elseif ($urlArray[1] == 'subirArchivo') $controller->subirArchivo();
                else $controller->index();
            } else {
                $controller->index();
            }
        }
        break;

    case 'servicios':
        if (class_exists('ServicioController')) {
            $controller = new ServicioController();
            if (isset($urlArray[1])) {
                if ($urlArray[1] == 'guardar') $controller->guardar();
                elseif ($urlArray[1] == 'actualizar') $controller->actualizar();
                elseif ($urlArray[1] == 'eliminar') $controller->eliminar();
                else $controller->index();
            } else {
                $controller->index();
            }
        }
        break;

    case 'especialidades':
        if (class_exists('EspecialidadController')) {
            $controller = new EspecialidadController();
            if (isset($urlArray[1])) {
                if ($urlArray[1] == 'guardar') $controller->guardar();
                elseif ($urlArray[1] == 'actualizar') $controller->actualizar();
                elseif ($urlArray[1] == 'eliminar') $controller->eliminar();
                else $controller->index();
            } else {
                $controller->index();
            }
        }
        break;

    case 'medicamentos':
        if (class_exists('MedicamentoController')) {
            $controller = new MedicamentoController();
            if (isset($urlArray[1])) {
                if ($urlArray[1] == 'guardar') $controller->guardar();
                elseif ($urlArray[1] == 'actualizar') $controller->actualizar();
                elseif ($urlArray[1] == 'eliminar') $controller->eliminar();
                else $controller->index();
            } else {
                $controller->index();
            }
        }
        break;

    case 'pagos':
        if (class_exists('PagoController')) {
            $controller = new PagoController();
            if (isset($urlArray[1]) && $urlArray[1] == 'eliminar') $controller->eliminar();
            else $controller->index();
        }
        break;

    case 'configuracion':
        if (class_exists('ConfiguracionController')) {
            $controller = new ConfiguracionController();
            if (isset($urlArray[1]) && $urlArray[1] == 'guardar') $controller->guardar();
            else $controller->index();
        }
        break;

    case 'perfil':
        if (class_exists('PerfilController')) {
            $controller = new PerfilController();
            if (isset($urlArray[1]) && $urlArray[1] == 'actualizar') $controller->actualizar();
            else $controller->index();
        }
        break;

    case 'reportes':
        if (class_exists('ReporteController')) {
            $controller = new ReporteController();
            $controller->index();
        }
        break;

    case 'auditoria':
        if (class_exists('AuditoriaController')) {
            $controller = new AuditoriaController();
            $controller->index();
        }
        break;

    default:
        header('Location: ' . BASE_URL . '/login');
        break;
}