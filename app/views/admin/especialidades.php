<?php require_once APP_ROOT . '/views/layouts/header.php'; ?>

<div class="card shadow border-0 mb-4">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold"><i class="fas fa-stethoscope me-2"></i> Catálogo de Especialidades</h5>
        <button class="btn btn-light text-dark fw-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalCrear">
            <i class="fas fa-plus me-2"></i> Nueva Especialidad
        </button>
    </div>
    
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tablaPro">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3">Nombre de Especialidad</th>
                        <th class="text-center">Médicos Asignados</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($resultado->rowCount() > 0): ?>
                        <?php while($row = $resultado->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td class="ps-3 fw-bold text-dark fs-5">
                                <?php echo $row['nombre']; ?>
                            </td>
                            <td class="text-center">
                                <?php if($row['total_medicos'] > 0): ?>
                                    <span class="badge bg-primary rounded-pill px-3">
                                        <?php echo $row['total_medicos']; ?> Doctores
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-25 text-secondary rounded-pill px-3">
                                        Sin personal
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary border-0 me-1" 
                                        data-bs-toggle="modal" data-bs-target="#modalEditar"
                                        onclick="cargarDatos('<?php echo $row['id_especialidad']; ?>', '<?php echo $row['nombre']; ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="#" onclick="confirmarEliminacion(<?php echo $row['id_especialidad']; ?>)" class="btn btn-sm btn-outline-danger border-0">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCrear" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Nueva Especialidad</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo BASE_URL; ?>/especialidades/guardar" method="POST">
                <div class="modal-body p-4">
                    <label class="fw-bold mb-2">Nombre</label>
                    <input type="text" name="nombre" class="form-control form-control-lg" required placeholder="Ej: Cardiología">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold">Editar Especialidad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo BASE_URL; ?>/especialidades/actualizar" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="edit_id">
                    <label class="fw-bold mb-2">Nombre</label>
                    <input type="text" name="nombre" id="edit_nombre" class="form-control form-control-lg" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning fw-bold px-4">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function cargarDatos(id, nombre) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nombre').value = nombre;
    }

    function confirmarEliminacion(id) {
        Swal.fire({
            title: '¿Eliminar?',
            text: "Esta acción es irreversible.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "<?php echo BASE_URL; ?>/especialidades/eliminar?id=" + id;
            }
        })
    }

    // Alerta específica para cuando intentan borrar una especialidad en uso
    const urlParams2 = new URLSearchParams(window.location.search);
    if(urlParams2.get('msg') === 'en_uso') {
        Swal.fire('No se puede eliminar', 'Esta especialidad tiene médicos asignados. Primero reasigna o elimina a los médicos.', 'error');
    }
</script>

<?php require_once APP_ROOT . '/views/layouts/footer.php'; ?>