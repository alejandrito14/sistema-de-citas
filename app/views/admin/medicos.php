<?php require_once APP_ROOT . '/views/layouts/header.php'; ?>

<div class="card shadow border-0">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold"><i class="fas fa-user-md me-2"></i> Gestión de Médicos</h5>
        <button class="btn btn-light text-success fw-bold btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalMedico">
            <i class="fas fa-plus me-1"></i> Nuevo Médico
        </button>
    </div>
    <div class="card-body p-4">
        
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tablaPro">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Especialidad</th>
                        <th>Email</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($resultado->rowCount() > 0): ?>
                        <?php while($row = $resultado->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td class="fw-bold">
                                <i class="fas fa-user-md text-success me-2"></i>
                                <?php echo $row['nombre']; ?>
                            </td>
                            <td>
                                <span class="badge bg-soft-success text-success border border-success bg-opacity-10">
                                    <?php echo $row['especialidad']; ?>
                                </span>
                            </td>
                            <td><?php echo $row['email']; ?></td>
                            <td class="text-center">
                                <a href="<?php echo BASE_URL; ?>/medicos/horarios?id=<?php echo $row['id_medico']; ?>" class="btn btn-sm btn-outline-info border-0 fw-bold me-1" title="Gestionar Horarios">
                                    <i class="fas fa-clock"></i> Horarios
                                </a>

                                <button class="btn btn-outline-primary btn-sm border-0" 
                                        data-bs-toggle="modal" data-bs-target="#modalEditar"
                                        onclick="cargarDatos('<?php echo $row['id_medico']; ?>')"> <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<div class="modal fade" id="modalMedico" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Registrar Nuevo Médico</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo BASE_URL; ?>/medicos/guardar" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre Completo</label>
                        <input type="text" name="nombre" class="form-control" required placeholder="Dr. Juan Pérez">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Especialidad</label>
                        <select name="id_especialidad" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <?php while($esp = $especialidades->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?php echo $esp['id_especialidad']; ?>">
                                    <?php echo $esp['nombre']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Correo Electrónico (Usuario)</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Contraseña</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success fw-bold">Guardar Médico</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Si tuvieras edición de médicos, aquí iría el script cargarDatos
    // Alerta de mensajes
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    if(msg === 'creado') Swal.fire('Éxito', 'Médico registrado correctamente', 'success');
    if(msg === 'error') Swal.fire('Error', 'No se pudo completar la operación', 'error');
</script>

<?php require_once APP_ROOT . '/views/layouts/footer.php'; ?>