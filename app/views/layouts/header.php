<?php
if(!defined('BASE_URL')) { header("Location: ../public/"); exit; }

// Cargar configuración
require_once APP_ROOT . '/config/Database.php';
require_once APP_ROOT . '/models/Configuracion.php';

$db_header = new Database();
$conn_header = $db_header->connect();
$configModel_header = new Configuracion($conn_header);
$empresa_header = $configModel_header->obtener();

$nombre_app = !empty($empresa_header['nombre_clinica']) ? $empresa_header['nombre_clinica'] : 'MediCitas';
$logo_app = !empty($empresa_header['logo']) ? $empresa_header['logo'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nombre_app; ?> - Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; overflow-x: hidden; }
        .sidebar-col { min-height: 100vh; background: #212529; box-shadow: 2px 0 5px rgba(0,0,0,0.1); padding: 0; transition: all 0.3s; }
        .sidebar-col.toggled { margin-left: -25%; }
        .logo-area { background: #1a1d20; padding: 20px 0; text-align: center; }
        .sidebar-menu a { color: #adb5bd; text-decoration: none; display: block; padding: 15px 25px; border-left: 4px solid transparent; transition: 0.3s; }
        .sidebar-menu a:hover { background: #2c3034; color: white; padding-left: 30px; }
        .sidebar-menu a.active { background: #0d6efd; color: white; border-left-color: white; }
        .content-col { padding: 0; background-color: #f4f6f9; transition: all 0.3s; }
        .top-navbar { background: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter { margin-bottom: 15px; }
        .select2-container--bootstrap-5 .select2-selection { border-color: #dee2e6; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row flex-nowrap">
        
        <div class="col-auto col-md-3 col-xl-2 px-0 sidebar-col bg-dark" id="sidebar">
            <div class="logo-area">
                <?php if($logo_app && file_exists(APP_ROOT . '/../public/uploads/' . $logo_app)): ?>
                    <div class="mb-2"><img src="<?php echo BASE_URL; ?>/uploads/<?php echo $logo_app; ?>" alt="Logo" class="img-fluid" style="max-height: 50px; object-fit: contain;"></div>
                    <h6 class="text-white mb-0 fw-bold small"><?php echo $nombre_app; ?></h6>
                <?php else: ?>
                    <h4 class="text-white mb-0"><i class="fas fa-heartbeat text-primary"></i> <?php echo $nombre_app; ?></h4>
                <?php endif; ?>
            </div>
            
            <div class="sidebar-menu mt-3">
                <?php $uri = $_SERVER['REQUEST_URI']; ?>
                <?php $rol = isset($_SESSION['user_role_id']) ? $_SESSION['user_role_id'] : 0; ?>

                <a href="<?php echo BASE_URL; ?>/home" class="<?php echo (strpos($uri, 'home') !== false) ? 'active' : ''; ?>"><i class="fas fa-chart-pie me-2"></i> Inicio</a>
                <a href="<?php echo BASE_URL; ?>/citas" class="<?php echo (strpos($uri, 'citas') !== false) ? 'active' : ''; ?>"><i class="fas fa-calendar-alt me-2"></i> Citas</a>
                
                <?php if($rol == 1): ?>
                    <a href="<?php echo BASE_URL; ?>/medicos" class="<?php echo (strpos($uri, 'medicos') !== false) ? 'active' : ''; ?>"><i class="fas fa-user-md me-2"></i> Médicos</a>
                    <a href="<?php echo BASE_URL; ?>/especialidades" class="<?php echo (strpos($uri, 'especialidades') !== false) ? 'active' : ''; ?>"><i class="fas fa-stethoscope me-2"></i> Especialidades</a>
                    <a href="<?php echo BASE_URL; ?>/servicios" class="<?php echo (strpos($uri, 'servicios') !== false) ? 'active' : ''; ?>"><i class="fas fa-tags me-2"></i> Servicios / Tarifas</a>
                <?php endif; ?>

                <?php if($rol == 1 || $rol == 4): ?>
                    <a href="<?php echo BASE_URL; ?>/medicamentos" class="<?php echo (strpos($uri, 'medicamentos') !== false) ? 'active' : ''; ?>"><i class="fas fa-pills me-2"></i> Farmacia</a>
                    <a href="<?php echo BASE_URL; ?>/pacientes" class="<?php echo (strpos($uri, 'pacientes') !== false) ? 'active' : ''; ?>"><i class="fas fa-users me-2"></i> Pacientes</a>
                    <a href="<?php echo BASE_URL; ?>/pagos" class="<?php echo (strpos($uri, 'pagos') !== false) ? 'active' : ''; ?>"><i class="fas fa-cash-register me-2"></i> Caja / Pagos</a>
                <?php endif; ?>

                <?php if($rol == 2): ?>
                    <a href="<?php echo BASE_URL; ?>/pacientes" class="<?php echo (strpos($uri, 'pacientes') !== false) ? 'active' : ''; ?>"><i class="fas fa-users me-2"></i> Mis Pacientes</a>
                <?php endif; ?>

                <?php if($rol == 1): ?>
                    <a href="<?php echo BASE_URL; ?>/reportes" class="<?php echo (strpos($uri, 'reportes') !== false) ? 'active' : ''; ?>"><i class="fas fa-chart-line me-2"></i> Reportes</a>
                    <a href="<?php echo BASE_URL; ?>/auditoria" class="<?php echo (strpos($uri, 'auditoria') !== false) ? 'active' : ''; ?>"><i class="fas fa-shield-alt me-2"></i> Seguridad</a>
                    <a href="<?php echo BASE_URL; ?>/configuracion" class="<?php echo (strpos($uri, 'configuracion') !== false) ? 'active' : ''; ?>"><i class="fas fa-cogs me-2"></i> Configuración</a>
                <?php endif; ?>

                <div class="mt-5 pt-5 px-3">
                    <a href="<?php echo BASE_URL; ?>/auth/logout" class="btn btn-outline-danger w-100 text-start"><i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión</a>
                </div>
            </div>
        </div>

        <div class="col content-col">
            <div class="top-navbar mb-4">
                <div class="d-flex align-items-center">
                    <button class="btn btn-light me-3 border" id="menu-toggle"><i class="fas fa-bars"></i></button>
                    <h5 class="m-0 text-secondary fw-bold d-none d-md-block"><?php echo $nombre_app; ?> - Panel</h5>
                </div>
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle fw-bold text-dark d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="me-2 text-end d-none d-md-block">
                                <small class="d-block text-muted" style="font-size: 0.75rem;">Bienvenido,</small>
                                <span><?php echo $_SESSION['user_name'] ?? 'Usuario'; ?></span>
                            </div>
                            <?php if(!empty($_SESSION['user_avatar']) && file_exists(APP_ROOT . '/../public/uploads/' . $_SESSION['user_avatar'])): ?>
                                <img src="<?php echo BASE_URL; ?>/uploads/<?php echo $_SESSION['user_avatar']; ?>" class="rounded-circle border" style="width: 40px; height: 40px; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-user-circle fa-2x text-primary"></i>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/perfil"><i class="fas fa-user me-2 text-muted"></i> Mi Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/auth/logout"><i class="fas fa-sign-out-alt me-2"></i> Salir</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="container-fluid px-4">