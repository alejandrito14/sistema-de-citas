<?php require_once APP_ROOT . '/views/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark">Gestión de Horarios</h4>
        <p class="text-muted mb-0">Médico: <strong class="text-primary fs-5"><?php echo $medico['nombre']; ?></strong> - <?php echo $medico['especialidad']; ?></p>
    </div>
    <a href="<?php echo BASE_URL; ?>/medicos" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Volver</a>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white fw-bold">Agregar Turno</div>
            <div class="card-body">
                <form action="<?php echo BASE_URL; ?>/medicos/guardarHorario" method="POST">
                    <input type="hidden" name="id_medico" value="<?php echo $medico['id_medico']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Día de la Semana</label>
                        <select name="dia" class="form-select" required>
                            <option value="Lunes">Lunes</option>
                            <option value="Martes">Martes</option>
                            <option value="Miércoles">Miércoles</option>
                            <option value="Jueves">Jueves</option>
                            <option value="Viernes">Viernes</option>
                            <option value="Sábado">Sábado</option>
                            <option value="Domingo">Domingo</option>
                        </select>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Inicio</label>
                            <input type="time" name="inicio" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Fin</label>
                            <input type="time" name="fin" class="form-control" required>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-2"></i> Agregar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold">Horarios Asignados</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Día</th>
                                <th>Desde</th>
                                <th>Hasta</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($horarios)): ?>
                                <?php foreach($horarios as $h): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-primary"><?php echo $h['dia_semana']; ?></td>
                                    <td><?php echo date('h:i A', strtotime($h['hora_inicio'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($h['hora_fin'])); ?></td>
                                    <td class="text-center">
                                        <a href="<?php echo BASE_URL; ?>/medicos/eliminarHorario?id=<?php echo $h['id_horario']; ?>&id_medico=<?php echo $medico['id_medico']; ?>" class="btn btn-sm btn-outline-danger border-0"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">Este médico no tiene horarios definidos (Trabaja 08:00 - 20:00 por defecto).</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    if(msg === 'guardado') Swal.fire('Éxito', 'Horario agregado correctamente', 'success');
    if(msg === 'eliminado') Swal.fire('Eliminado', 'Horario removido', 'success');
    if(msg) window.history.replaceState({}, document.title, window.location.pathname + '?id=<?php echo $medico['id_medico']; ?>');
</script>

<?php require_once APP_ROOT . '/views/layouts/footer.php'; ?>