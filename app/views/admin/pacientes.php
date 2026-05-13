<?php require_once APP_ROOT . '/views/layouts/header.php'; ?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(45deg, #6a11cb, #2575fc);">
            <div class="card-body d-flex align-items-center justify-content-between p-4">
                <div>
                    <h6 class="mb-0 text-white-50">Total de Pacientes Registrados</h6>
                    <h2 class="mb-0 fw-bold"><?php echo $resultado->rowCount(); ?></h2>
                </div>
                <i class="fas fa-users fa-3x opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<div class="card shadow border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-secondary fw-bold"><i class="fas fa-user-injured me-2"></i> Directorio de Pacientes</h5>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalPaciente">
            <i class="fas fa-plus me-2"></i> Nuevo Paciente
        </button>
    </div>
    
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tablaPro">
                <thead class="bg-light text-secondary">
                    <tr>
                        <th class="ps-3">Nombre / DNI</th>
                        <th>Contacto</th>
                        <th>Datos Clínicos</th>
                        <th>Registro</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($resultado->rowCount() > 0): ?>
                        <?php while($row = $resultado->fetch(PDO::FETCH_ASSOC)): ?>
                            <?php 
                                $telefono = $row['telefono'] ?? '';
                                $alergias = $row['alergias'] ?? '';
                                $cronicas = $row['enfermedades_cronicas'] ?? '';
                                $sangre = $row['grupo_sanguineo'] ?? '';
                                $dni = $row['documento_identidad'] ?? '---';
                            ?>
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded-circle p-2 me-3 text-secondary"><i class="fas fa-user"></i></div>
                                    <div>
                                        <div class="fw-bold text-dark"><?php echo $row['nombre']; ?></div>
                                        <small class="text-muted">DNI: <?php echo $dni; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <small><i class="fas fa-envelope me-1 text-muted"></i> <?php echo $row['email']; ?></small>
                                    <small><i class="fas fa-phone me-1 text-success"></i> <?php echo $telefono ? $telefono : 'Sin Nro'; ?></small>
                                </div>
                            </td>
                            <td>
                                <?php if(!empty($alergias)): ?>
                                    <span class="badge bg-danger" title="Alergias: <?php echo $alergias; ?>"><i class="fas fa-exclamation-triangle"></i> Alergia</span>
                                <?php endif; ?>
                                <?php if(!empty($cronicas)): ?>
                                    <span class="badge bg-warning text-dark" title="Crónicas: <?php echo $cronicas; ?>"><i class="fas fa-heartbeat"></i> Crónico</span>
                                <?php endif; ?>
                                <?php if(empty($alergias) && empty($cronicas)): ?>
                                    <span class="badge bg-light text-muted">Sin datos críticos</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?php echo date('d/m/Y', strtotime($row['fecha_creacion'])); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="<?php echo BASE_URL; ?>/pacientes/historial?id=<?php echo $row['id_usuario']; ?>" class="btn btn-sm btn-info text-white border-0 me-1" title="Historia Clínica">
                                    <i class="fas fa-file-medical-alt"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-primary border-0 me-1" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalEditar"
                                        onclick="cargarDatosEditar('<?php echo $row['id_usuario']; ?>', '<?php echo $row['nombre']; ?>', '<?php echo $dni; ?>', '<?php echo $row['email']; ?>', '<?php echo $telefono; ?>', '<?php echo $sangre; ?>', `<?php echo $alergias; ?>`, `<?php echo $cronicas; ?>`)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="#" onclick="confirmarEliminacion(<?php echo $row['id_usuario']; ?>)" class="btn btn-sm btn-outline-danger border-0" title="Eliminar">
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

<div class="modal fade" id="modalPaciente" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background: linear-gradient(45deg, #6a11cb, #2575fc);">
                <h5 class="modal-title fw-bold">Registrar Paciente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo BASE_URL; ?>/pacientes/guardar" method="POST">
                <div class="modal-body p-4">
                    <h6 class="text-primary fw-bold border-bottom pb-2 mb-3">Datos Personales</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombre Completo</label>
                            <input type="text" name="nombre" class="form-control" required placeholder="Ej: Maria Lopez">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">DNI / Cédula</label>
                            <input type="text" name="dni" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Teléfono</label>
                            <input type="text" name="telefono" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Correo Electrónico</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Contraseña</label>
                        <input type="password" name="password" class="form-control" required placeholder="******">
                    </div>

                    <h6 class="text-danger fw-bold border-bottom pb-2 mb-3 mt-4"><i class="fas fa-heartbeat me-2"></i> Datos Clínicos Críticos</h6>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Grupo Sanguíneo</label>
                            <select name="sangre" class="form-select">
                                <option value="">Seleccione...</option>
                                <option value="O+">O+</option><option value="O-">O-</option><option value="A+">A+</option><option value="A-">A-</option>
                                <option value="B+">B+</option><option value="B-">B-</option><option value="AB+">AB+</option><option value="AB-">AB-</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold text-danger">Alergias</label>
                            <input type="text" name="alergias" class="form-control" placeholder="Ej: Penicilina">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-warning-dark">Enfermedades Crónicas</label>
                        <textarea name="cronicas" class="form-control" rows="2" placeholder="Ej: Diabetes, Hipertensión..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold">Editar Paciente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo BASE_URL; ?>/pacientes/actualizar" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="id_usuario" id="edit_id">
                    
                    <h6 class="text-dark fw-bold border-bottom pb-2 mb-3">Datos Personales</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombre Completo</label>
                            <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">DNI / Cédula</label>
                            <input type="text" name="dni" id="edit_dni" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Teléfono</label>
                            <input type="text" name="telefono" id="edit_telefono" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Correo Electrónico</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nueva Contraseña</label>
                        <input type="password" name="password" class="form-control" placeholder="Dejar en blanco para mantener">
                    </div>

                    <h6 class="text-danger fw-bold border-bottom pb-2 mb-3 mt-4"><i class="fas fa-heartbeat me-2"></i> Datos Clínicos Críticos</h6>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Grupo Sanguíneo</label>
                            <select name="sangre" id="edit_sangre" class="form-select">
                                <option value="">Seleccione...</option>
                                <option value="O+">O+</option><option value="O-">O-</option><option value="A+">A+</option><option value="A-">A-</option>
                                <option value="B+">B+</option><option value="B-">B-</option><option value="AB+">AB+</option><option value="AB-">AB-</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold text-danger">Alergias</label>
                            <input type="text" name="alergias" id="edit_alergias" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Enfermedades Crónicas</label>
                        <textarea name="cronicas" id="edit_cronicas" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning fw-bold px-4">Actualizar Datos</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function cargarDatosEditar(id, nombre, dni, email, telefono, sangre, alergias, cronicas) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nombre').value = nombre;
        document.getElementById('edit_dni').value = dni;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_telefono').value = telefono;
        document.getElementById('edit_sangre').value = sangre;
        document.getElementById('edit_alergias').value = alergias;
        document.getElementById('edit_cronicas').value = cronicas;
    }

    function confirmarEliminacion(id) {
        Swal.fire({
            title: '¿Eliminar Paciente?',
            text: "Se borrará todo su historial y citas. No se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "<?php echo BASE_URL; ?>/pacientes/eliminar?id=" + id;
            }
        })
    }
</script>

<?php require_once APP_ROOT . '/views/layouts/footer.php'; ?>