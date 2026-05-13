<?php require_once APP_ROOT . '/views/layouts/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .timeline { border-left: 3px solid #e9ecef; margin-left: 20px; padding-left: 30px; position: relative; }
    .timeline-item { position: relative; margin-bottom: 40px; }
    .timeline-dot { width: 16px; height: 16px; background: #0d6efd; border-radius: 50%; position: absolute; left: -39px; top: 5px; border: 3px solid white; box-shadow: 0 0 0 2px #0d6efd; }
    .timeline-date { color: #6c757d; font-size: 0.9rem; font-weight: bold; margin-bottom: 5px; }
    .card-historia { border-left: 5px solid #0d6efd; transition: 0.3s; }
    .card-historia:hover { transform: translateX(5px); }
    .file-card { transition: all 0.3s; border: 1px solid #dee2e6; cursor: pointer; }
    .file-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-color: #0d6efd; }
    .file-card a { display: block; width: 100%; height: 100%; text-decoration: none; color: inherit; }
</style>

<style media="print">
    .sidebar-col, .top-navbar, .no-print, .nav-tabs { display: none !important; }
    .content-col { background: white !important; margin: 0 !important; padding: 0 !important; }
    .timeline { border-left: 1px solid #000; }
    .card { border: 1px solid #ddd !important; box-shadow: none !important; break-inside: avoid; }
</style>

<div class="card shadow-sm border-0 mb-4 bg-white">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-1 text-dark">Expediente Clínico</h4>
                <p class="text-muted mb-0">Paciente: <strong class="text-primary fs-5"><?php echo $paciente['nombre']; ?></strong></p>
                <small class="text-muted"><i class="fas fa-envelope me-1"></i> <?php echo $paciente['email']; ?> | <i class="fas fa-phone me-1"></i> <?php echo $paciente['telefono'] ? $paciente['telefono'] : 'Sin Teléfono'; ?></small>
            </div>
            <div class="no-print text-end">
                <button onclick="window.print()" class="btn btn-dark mb-1"><i class="fas fa-print me-2"></i> Imprimir</button>
                <a href="<?php echo BASE_URL; ?>/pacientes" class="btn btn-secondary mb-1"><i class="fas fa-arrow-left me-2"></i> Volver</a>
                
                <div class="mt-2">
                    <?php if($paciente['alergias']): ?>
                        <span class="badge bg-danger p-2"><i class="fas fa-exclamation-triangle me-1"></i> Alergias: <?php echo $paciente['alergias']; ?></span>
                    <?php endif; ?>
                    <?php if($paciente['grupo_sanguineo']): ?>
                        <span class="badge bg-primary p-2 ms-1"><i class="fas fa-tint me-1"></i> <?php echo $paciente['grupo_sanguineo']; ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-4 no-print" id="tabHistorial" role="tablist">
    <li class="nav-item"><button class="nav-link active fw-bold" id="timeline-tab" data-bs-toggle="tab" data-bs-target="#timelinePanel"><i class="fas fa-history me-2"></i> Línea de Tiempo</button></li>
    <li class="nav-item"><button class="nav-link fw-bold" id="graficos-tab" data-bs-toggle="tab" data-bs-target="#graficosPanel"><i class="fas fa-chart-line me-2"></i> Evolución (Gráficos)</button></li>
    <li class="nav-item"><button class="nav-link fw-bold" id="docs-tab" data-bs-toggle="tab" data-bs-target="#docsPanel"><i class="fas fa-folder-open me-2"></i> Documentos</button></li>
</ul>

<div class="tab-content" id="tabContent">
    
    <div class="tab-pane fade show active" id="timelinePanel">
        <div class="row">
            <div class="col-md-12">
                <?php if($historial->rowCount() > 0): ?>
                    <div class="timeline">
                        <?php while($cita = $historial->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot bg-<?php echo ($cita['estado'] == 'Finalizada') ? 'success' : 'secondary'; ?>"></div>
                                <div class="timeline-date"><?php echo date('d/m/Y', strtotime($cita['fecha_cita'])); ?> <span class="ms-2 text-muted"><?php echo date('H:i A', strtotime($cita['fecha_cita'])); ?></span></div>
                                <div class="card shadow-sm card-historia border-0">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h6 class="fw-bold text-primary mb-1">Dr. <?php echo $cita['medico']; ?></h6>
                                            <span class="badge bg-<?php echo ($cita['estado'] == 'Finalizada') ? 'success' : 'warning'; ?>"><?php echo $cita['estado']; ?></span>
                                        </div>
                                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;"><?php echo $cita['especialidad']; ?></small>
                                        
                                        <div class="mt-3 p-2 bg-light rounded small"><strong>Motivo:</strong> <?php echo $cita['motivo']; ?></div>

                                        <?php if($cita['peso'] || $cita['temperatura']): ?>
                                            <div class="row mt-3 small text-center text-muted border-top border-bottom py-2 mx-1">
                                                <div class="col-3 border-end"><strong>Peso</strong><br><?php echo $cita['peso'] ? $cita['peso'].' kg' : '-'; ?></div>
                                                <div class="col-3 border-end"><strong>Talla</strong><br><?php echo $cita['talla'] ? $cita['talla'].' m' : '-'; ?></div>
                                                <div class="col-3 border-end"><strong>Temp</strong><br><?php echo $cita['temperatura'] ? $cita['temperatura'].' °C' : '-'; ?></div>
                                                <div class="col-3"><strong>P.A.</strong><br><?php echo $cita['presion_arterial'] ? $cita['presion_arterial'] : '-'; ?></div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if($cita['diagnostico']): ?>
                                            <div class="mt-2"><h6 class="fw-bold text-dark mt-3 small"><i class="fas fa-notes-medical me-1 text-success"></i> Diagnóstico:</h6><p class="mb-0 text-secondary small"><?php echo nl2br($cita['diagnostico']); ?></p></div>
                                        <?php endif; ?>
                                        <?php if($cita['prescripcion']): ?>
                                            <div class="mt-2 border-top pt-2"><h6 class="fw-bold text-dark mt-1 small"><i class="fas fa-pills me-1 text-primary"></i> Receta:</h6><p class="mb-0 text-secondary small fst-italic"><?php echo nl2br($cita['prescripcion']); ?></p></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light text-center">No hay consultas registradas.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="graficosPanel">
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card shadow border-0">
                    <div class="card-header bg-white fw-bold">Evolución de Peso (kg)</div>
                    <div class="card-body"><canvas id="chartPeso"></canvas></div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card shadow border-0">
                    <div class="card-header bg-white fw-bold">Evolución de Temperatura (°C)</div>
                    <div class="card-body"><canvas id="chartTemp"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="docsPanel">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3"><h5 class="fw-bold text-secondary mb-0"><i class="fas fa-folder-open me-2"></i> Archivos Adjuntos</h5></div>
            <div class="card-body">
                <form action="<?php echo BASE_URL; ?>/pacientes/subirArchivo" method="POST" enctype="multipart/form-data" class="mb-4 p-3 bg-light rounded border no-print">
                    <input type="hidden" name="id_paciente" value="<?php echo $paciente['id_usuario']; ?>">
                    <div class="input-group">
                        <input type="file" name="documento" class="form-control form-control-sm" required accept=".pdf,.jpg,.png,.jpeg">
                        <button class="btn btn-primary btn-sm" type="submit"><i class="fas fa-upload me-1"></i> Subir</button>
                    </div>
                </form>
                <div class="row g-2">
                    <?php if($archivos->rowCount() > 0): ?>
                        <?php while($archivo = $archivos->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="col-md-3 col-6">
                                <div class="card file-card h-100 text-center p-3">
                                    <a href="<?php echo BASE_URL; ?>/uploads/<?php echo $archivo['ruta_archivo']; ?>" target="_blank">
                                        <?php if($archivo['tipo_archivo'] == 'pdf'): ?>
                                            <i class="fas fa-file-pdf fa-3x text-danger mb-2"></i>
                                        <?php else: ?>
                                            <i class="fas fa-file-image fa-3x text-primary mb-2"></i>
                                        <?php endif; ?>
                                        <p class="mb-0 small text-truncate fw-bold text-dark"><?php echo $archivo['nombre_archivo']; ?></p>
                                        <small class="text-muted" style="font-size: 0.6rem;"><?php echo date('d/m/Y', strtotime($archivo['fecha_subida'])); ?></small>
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-3">Sin archivos adjuntos.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    if(msg === 'subido') Swal.fire('Éxito', 'Archivo adjuntado correctamente', 'success');
    if(msg === 'error_tamano') Swal.fire('Error', 'Archivo muy pesado', 'error');
    if(msg) window.history.replaceState({}, document.title, window.location.pathname + '?id=<?php echo $id_paciente; ?>');

    // CONFIGURACIÓN DE GRÁFICOS
    const fechas = <?php echo json_encode($fechas); ?>;
    const pesos = <?php echo json_encode($pesos); ?>;
    const temps = <?php echo json_encode($temperaturas); ?>;

    if(fechas.length > 0) {
        new Chart(document.getElementById('chartPeso'), {
            type: 'line',
            data: {
                labels: fechas,
                datasets: [{ label: 'Peso (kg)', data: pesos, borderColor: '#0d6efd', tension: 0.3, fill: true, backgroundColor: 'rgba(13, 110, 253, 0.1)' }]
            }
        });

        new Chart(document.getElementById('chartTemp'), {
            type: 'line',
            data: {
                labels: fechas,
                datasets: [{ label: 'Temp (°C)', data: temps, borderColor: '#dc3545', tension: 0.3, fill: false }]
            }
        });
    } else {
        // Mensaje si no hay datos
        document.getElementById('chartPeso').parentNode.innerHTML = '<p class="text-center text-muted py-5">No hay datos de peso registrados.</p>';
        document.getElementById('chartTemp').parentNode.innerHTML = '<p class="text-center text-muted py-5">No hay datos de temperatura registrados.</p>';
    }
</script>

<?php require_once APP_ROOT . '/views/layouts/footer.php'; ?>