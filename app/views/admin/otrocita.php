<?php
// Asegurar que $citas sea un array puro aunque venga como ArrayObject
if (isset($citas) && $citas instanceof ArrayObject) {
    $citas = $citas->getArrayCopy(); // Asegurar que $citas sea un array puro aunque venga como ArrayObject
}
?>

<?php require_once APP_ROOT . '/views/layouts/header.php'; ?>

<?php
// Convertir los PDOStatement a arrays para poder usar count()
if ($listaMedicos instanceof PDOStatement) {
    $medicos = $listaMedicos->fetchAll(PDO::FETCH_ASSOC);
} else {
    $medicos = is_array($listaMedicos) ? $listaMedicos : [];
}
if ($listaPacientes instanceof PDOStatement) {
    $pacientes = $listaPacientes->fetchAll(PDO::FETCH_ASSOC);
} else {
    $pacientes = is_array($listaPacientes) ? $listaPacientes : [];
}
// Convertir resultado a array para evitar errores con rowCount/fetch y soportar ArrayObject
if ($resultado instanceof PDOStatement) {
    $citas = $resultado->fetchAll(PDO::FETCH_ASSOC);
} elseif ($resultado instanceof ArrayObject) {
    $citas = $resultado->getArrayCopy(); // Convertir resultado a array para evitar errores con rowCount/fetch y soportar ArrayObject
} else {
    $citas = is_array($resultado) ? $resultado : [];
}
?>

<script>
    var BASE_URL = '<?php echo BASE_URL; ?>';
    // Asegurar que $citas sea un array puro aunque venga como ArrayObject

</script>

<?php

    if (isset($citas) && $citas instanceof ArrayObject) {
        $citas = $citas->getArrayCopy();
    }

?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

<style>
    .receta-container, .certificado-container { font-family: 'Times New Roman', serif; border: 2px solid #333; padding: 40px; background: #fff; color: #000; }
    .receta-header, .certificado-header { border-bottom: 2px solid #333; margin-bottom: 20px; padding-bottom: 10px; }
    .receta-body, .certificado-body { min-height: 200px; font-size: 16px; line-height: 1.6; }
    .receta-footer, .certificado-footer { margin-top: 50px; border-top: 1px dashed #333; padding-top: 10px; }
    .ticket-container { font-family: 'Courier New', Courier, monospace; border: 1px solid #333; padding: 20px; background: #fff; color: #000; }
    #calendar { max-width: 100%; margin: 0 auto; min-height: 600px; background: white; padding: 20px; border-radius: 10px; }
    .fc-event { cursor: pointer; }

</style>



<div class="row mb-4 no-print">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-white bg-primary h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div><h6 class="mb-0 text-white-50">Citas Totales</h6><h2 class="mb-0 fw-bold"><?php echo count($citas); ?></h2></div>
                <i class="fas fa-calendar-check fa-3x opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-white bg-success h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div><h6 class="mb-0 text-white-50">Staff Médico</h6><h2 class="mb-0 fw-bold"><?php echo count($medicos); ?></h2></div>
                <i class="fas fa-user-md fa-3x opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-white h-100" style="background: linear-gradient(45deg, #6a11cb, #2575fc);">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div><h6 class="mb-0 text-white-50">Pacientes Activos</h6><h2 class="mb-0 fw-bold"><?php echo count($pacientes); ?></h2></div>
                <i class="fas fa-users fa-3x opacity-25"></i>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-4 no-print" id="myTab" role="tablist">
    <li class="nav-item"><button class="nav-link active fw-bold" id="lista-tab" data-bs-toggle="tab" data-bs-target="#lista"><i class="fas fa-list me-2"></i> Lista</button></li>
    <li class="nav-item"><button class="nav-link fw-bold" id="calendario-tab" data-bs-toggle="tab" data-bs-target="#calendario"><i class="fas fa-calendar-alt me-2"></i> Calendario</button></li>
</ul>


<div class="tab-content" id="myTabContent">
    <div class="tab-pane fade show active" id="lista" role="tabpanel">
        <div class="card-body py-3">
            <form action="<?php echo BASE_URL; ?>/citas" method="GET" class="row g-2 align-items-center">
                <div class="col-auto"><span class="fw-bold text-secondary"><i class="fas fa-filter me-1"></i> Filtros:</span></div>
                <div class="col-auto"><input type="date" name="fecha" class="form-control form-control-sm" value="<?php echo isset($_GET['fecha']) ? $_GET['fecha'] : ''; ?>"></div>
                <div class="col-auto"><select name="estado" class="form-select form-select-sm"><option value="">- Estado -</option><option value="Pendiente">Pendiente</option><option value="Confirmada">Confirmada</option><option value="Finalizada">Finalizada</option></select></div>
                <div class="col-auto"><button type="submit" class="btn btn-dark btn-sm">Buscar</button> <a href="<?php echo BASE_URL; ?>/citas" class="btn btn-outline-secondary btn-sm">Limpiar</a></div>
            </form>
        </div>
        <div class="card shadow border-0 no-print">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-secondary fw-bold">Agenda Detallada</h5>
                <div class="d-flex gap-3">
                    <input type="text" id="buscador" class="form-control form-control-sm" placeholder="Buscar rápido...">
                    <button class="btn btn-primary fw-bold px-3 py-3 rounded-pill align-self-center" style="height:auto;min-height:unset;line-height:1.2;min-width:150px;" data-bs-toggle="modal" data-bs-target="#modalCita"> Agendar</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablaCitas">
                        <thead class="bg-light text-secondary">
                            <tr><th class="ps-4">Hora</th><th>Paciente</th><th>Servicio / Médico</th><th>Importe</th><th>Estado</th><th>Pago</th><th>Cuotas</th><th class="text-center">Gestión</th></tr>
                        </thead>
                        <tbody>
                            <?php if(count($citas) > 0): ?>
                                <?php foreach($citas as $row): ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?php echo date('d/m/Y', strtotime($row['fecha_cita'])); ?> <br><span class="text-primary"><?php echo date('H:i A', strtotime($row['fecha_cita'])); ?></span></td>
                                    <td>
                                        <?php echo $row['paciente']; ?><br>
                                        <?php if($row['paciente_telefono']): ?><a href="https://wa.me/51<?php echo $row['paciente_telefono']; ?>" target="_blank" class="badge bg-success text-decoration-none border-0"><i class="fab fa-whatsapp"></i></a><?php endif; ?>
                                    </td>
                                    <td><span class="d-block fw-bold text-dark"><?php echo $row['nombre_servicio'] ? $row['nombre_servicio'] : 'Consulta'; ?></span><small class="text-muted">Dr. <?php echo $row['medico']; ?></small></td>
                                    <td class="fw-bold text-success fs-6"><?php echo (isset($empresa['moneda']) ? $empresa['moneda'] : 'S/.') . ' ' . number_format($row['precio'] ?? 0, 2); ?></td>
                                    <td>
                                        <?php $bg = ($row['estado']=='Confirmada')?'bg-primary':(($row['estado']=='Finalizada')?'bg-success':(($row['estado']=='Cancelada')?'bg-danger':'bg-warning')); ?>
                                        <span class="badge <?php echo $bg; ?>"><?php echo $row['estado']; ?></span>
                                    </td>
                                    <td>
                                        <?php if($row['id_pago']): ?><span class="badge bg-success bg-opacity-75"><i class="fas fa-check-circle me-1"></i> Pagado</span><?php else: ?><span class="badge bg-danger bg-opacity-75"><i class="fas fa-times-circle me-1"></i> Pendiente</span><?php endif; ?>
                                    </td>

                                     <td class="text-center">
                                        <?php
                                        $res = isset($cuotasResumen[$row['id_cita']]) ? $cuotasResumen[$row['id_cita']] : null;
                                        if ($res && $res['total'] > 0) {
                                            printf('<a href="#" class="badge bg-light text-dark me-2" data-bs-toggle="modal" data-bs-target="#modalCuotas" onclick="cargarCuotas(\'%s\', \'%s\')">%s/%s</a>', addslashes($row['id_cita']), addslashes($row['paciente']), $res['pagadas'], $res['total']);
                                        } else {
                                            printf('<a href="#" class="badge bg-secondary me-2" data-bs-toggle="modal" data-bs-target="#modalCuotas" onclick="cargarCuotas(\'%s\', \'%s\')">-</a>', addslashes($row['id_cita']), addslashes($row['paciente']));
                                        }
                                        ?>
                                    </td>
                                    <!-- Columna Gestión muestra los botones de acción -->
                                    <td class="text-center">
                                        <?php if(!$row['id_pago'] && $row['estado'] != 'Cancelada'): ?>
                                            <button class="btn btn-sm btn-outline-info border-0 me-1" title="Pagar" data-bs-toggle="modal" data-bs-target="#modalCobrar" onclick="cargarDatosCobro('<?php echo $row['id_cita']; ?>', '<?php echo $row['paciente']; ?>', '<?php echo $row['precio']; ?>')"><i class="fas fa-money-bill-wave"></i></button>
                                        <?php endif; ?>
                                        <?php if($row['id_pago']): ?>
                                            <button class="btn btn-sm btn-outline-secondary border-0 me-1" title="Ticket" data-bs-toggle="modal" data-bs-target="#modalTicket" onclick="cargarTicket('<?php echo $row['id_cita']; ?>', '<?php echo $row['paciente']; ?>', '<?php echo $row['nombre_servicio']; ?>', '<?php echo $row['precio']; ?>', '<?php echo date('d/m/Y', strtotime($row['fecha_pago'])); ?>')"><i class="fas fa-receipt"></i></button>
                                        <?php endif; ?>
                                        <?php if($row['estado'] != 'Finalizada' && $row['estado'] != 'Cancelada'): ?>
                                            <button class="btn btn-sm btn-outline-success border-0 me-1" title="Atender" data-bs-toggle="modal" data-bs-target="#modalAtender" onclick="cargarDatosAtender('<?php echo $row['id_cita']; ?>', '<?php echo $row['paciente']; ?>')"><i class="fas fa-stethoscope"></i></button>
                                        <?php endif; ?>
                                        <?php if($row['estado'] == 'Finalizada'): ?>
                                            <button class="btn btn-sm btn-outline-dark border-0 me-1" title="Receta" data-bs-toggle="modal" data-bs-target="#modalReceta" onclick="cargarReceta('<?php echo $row['paciente']; ?>', '<?php echo $row['medico']; ?>', '<?php echo $row['especialidad']; ?>', '<?php echo date('d/m/Y', strtotime($row['fecha_cita'])); ?>', `<?php echo $row['diagnostico']; ?>`, `<?php echo $row['prescripcion']; ?>`, '<?php echo $row['peso']; ?>', '<?php echo $row['talla']; ?>', '<?php echo $row['presion_arterial']; ?>', '<?php echo $row['temperatura']; ?>')"><i class="fas fa-file-prescription"></i></button>
                                        <?php endif; ?>
                                        <?php if($row['estado'] == 'Finalizada' && $row['dias_reposo'] > 0): ?>
                                            <button class="btn btn-sm btn-outline-primary border-0 me-1" title="Certificado Médico" data-bs-toggle="modal" data-bs-target="#modalCertificado" onclick="cargarCertificado('<?php echo $row['paciente']; ?>', '<?php echo $row['medico']; ?>', '<?php echo $row['especialidad']; ?>', '<?php echo $row['documento_identidad'] ?? '---'; ?>', '<?php echo date('d/m/Y', strtotime($row['fecha_cita'])); ?>', '<?php echo $row['dias_reposo']; ?>', `<?php echo $row['diagnostico']; ?>`, '<?php echo $row['colegiatura'] ?? 'CMP -----'; ?>')"><i class="fas fa-certificate"></i></button>
                                        <?php endif; ?>
                                        <?php if($row['estado'] != 'Finalizada' && $row['estado'] != 'Cancelada'): ?>
                                            <button class="btn btn-sm btn-outline-primary border-0 me-1" data-bs-toggle="modal" data-bs-target="#modalEditar" onclick="cargarDatosEditar('<?php echo $row['id_cita']; ?>','<?php echo $row['id_medico']; ?>','<?php echo $row['id_servicio']; ?>','<?php echo date('Y-m-d\TH:i', strtotime($row['fecha_cita'])); ?>','<?php echo $row['motivo']; ?>','<?php echo $row['estado']; ?>')"><i class="fas fa-edit"></i></button>
                                        <?php endif; ?>
                                        <a href="#" onclick="confirmarEliminacion(<?php echo $row['id_cita']; ?>)" class="btn btn-sm btn-outline-danger border-0"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                    <!-- Columna Cuotas muestra el resumen de cuotas -->
                                   
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center py-5 text-muted">No se encontraron citas.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane fade" id="calendario" role="tabpanel">
        <div class="card shadow border-0 mb-4 no-print">
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Cuotas (fuera del bucle) -->
<div class="modal fade" id="modalCuotas" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-list-ol me-2"></i>Registrar Pago de Cuotas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="cuotasContenido">
                    <div class="text-center text-secondary py-5">
                        <i class="fas fa-spinner fa-spin fa-2x"></i><br> Cargando cuotas...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCita" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Nueva Cita</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form action="<?php echo BASE_URL; ?>/citas/guardar" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3"><label class="fw-bold">Paciente</label><select name="paciente_id" class="form-select select2-paciente" required><option value="">Buscar Paciente...</option><?php $listaPacientes->execute(); while($pac = $listaPacientes->fetch(PDO::FETCH_ASSOC)): ?><option value="<?php echo $pac['id_usuario']; ?>"><?php echo $pac['nombre']; ?> - <?php echo $pac['telefono']; ?></option><?php endwhile; ?></select></div>
                    <div class="mb-3"><label class="fw-bold">Tipo de Servicio</label><select name="id_servicio" class="form-select" required onchange="actualizarPrecio(this)"><option value="">Seleccione...</option><?php $listaServicios->execute(); while($serv = $listaServicios->fetch(PDO::FETCH_ASSOC)): ?><option value="<?php echo $serv['id_servicio']; ?>" data-precio="<?php echo $serv['precio']; ?>"><?php echo $serv['nombre_servicio']; ?></option><?php endwhile; ?></select><div class="form-text text-end fw-bold text-success" id="precio_preview"></div></div>
                    <div class="mb-3 position-relative">
                        <label class="fw-bold">Médico</label>
                        <select name="medico_id" id="comboMedico" class="form-select select2-medico" required>
                            <option value="">Buscar Médico...</option>
                            <?php $listaMedicos->execute(); while($med = $listaMedicos->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?php echo $med['id_medico']; ?>"><?php echo $med['nombre']; ?></option>
                            <?php endwhile; ?>
                        </select>
                        <div id="msgMedico" class="invalid-feedback" style="display:none;">Debes seleccionar un médico.</div>
                    </div>
                    <div class="mb-3"><label>Fecha</label><input type="datetime-local" name="fecha" id="fecha_input" class="form-control" required></div>
                    <div class="mb-3"><label>Motivo</label><textarea name="motivo" class="form-control" required></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var form = document.querySelector('#modalCita form');
                var medico = document.getElementById('comboMedico');
                var msg = document.getElementById('msgMedico');
                form.addEventListener('submit', function(e) {
                    if (!medico.value) {
                        e.preventDefault();
                        medico.classList.add('is-invalid');
                        msg.style.display = 'block';
                        medico.focus();
                    } else {
                        medico.classList.remove('is-invalid');
                        msg.style.display = 'none';
                    }
                });
                medico.addEventListener('change', function() {
                    if (medico.value) {
                        medico.classList.remove('is-invalid');
                        msg.style.display = 'none';
                    }
                });
            });
            </script>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCobrar" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold">Registrar Cobro</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCobro" action="<?php echo BASE_URL; ?>/citas/cobrar" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="id_cita" id="cobro_id">
                    <!-- Encabezado paciente -->
                    <div class="alert alert-info border-0 d-flex align-items-center mb-3">
                        <i class="fas fa-user me-3 fa-2x"></i>
                        <div>
                            <strong>Paciente:</strong> <span id="cobro_paciente"></span>
                            <br>
                            <small>Confirma los servicios/productos y el monto a cobrar.</small>
                        </div>
                    </div>
                    <div class="row g-4">
                        <!-- Columna izquierda -->
                        <div class="col-md-8">
                            <!-- 1. Agregar servicios y productos -->
                            <div class="mb-4">
                                <label class="fw-bold mb-2">1. Agregar servicios y productos</label>
                                <div class="input-group mb-2">
                                    <select class="form-select" id="selectServicioProducto">
                                        <option value="">Buscar servicio o producto...</option>
                                        <optgroup label="Servicios">
                                            <?php $listaServicios->execute(); while($serv = $listaServicios->fetch(PDO::FETCH_ASSOC)): ?>
                                                <option value="servicio-<?php echo $serv['id_servicio']; ?>" data-tipo="Servicio" data-nombre="<?php echo $serv['nombre_servicio']; ?>" data-precio="<?php echo $serv['precio']; ?>">
                                                    <?php echo $serv['nombre_servicio']; ?> ($<?php echo number_format($serv['precio'],2); ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </optgroup>
                                        <optgroup label="Productos">
                                            <?php $listaMedicamentos->execute(); while($med = $listaMedicamentos->fetch(PDO::FETCH_ASSOC)): ?>
                                                <option value="producto-<?php echo $med['id_medicamento']; ?>" data-tipo="Producto" data-nombre="<?php echo $med['nombre_comercial']; ?>" data-precio="0">
                                                    <?php echo $med['nombre_comercial']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </optgroup>
                                    </select>
                                    <input type="number" class="form-control" id="precioServicioProducto" placeholder="Precio" min="0" step="0.01" style="max-width:120px;">
                                    <input type="number" class="form-control" id="cantidadServicioProducto" placeholder="Cant." min="1" value="1" style="max-width:80px;">
                                    <button type="button" class="btn btn-outline-primary" id="btnAgregarServicioProducto">Agregar</button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle" id="tablaServiciosProductos">
                                        <thead>
                                            <tr>
                                                <th>Descripción</th>
                                                <th class="text-center">Cantidad</th>
                                                <th class="text-end">Precio ($)</th>
                                                <th class="text-end">Total ($)</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Items agregados dinámicamente -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- 2. Plan de pago -->
                            <div class="mb-4">
                                <label class="fw-bold mb-2">2. Plan de pago (cuotas)</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="plan_pago" id="pagoUnico" value="unico" checked>
                                        <label class="form-check-label" for="pagoUnico">Pago único</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="plan_pago" id="pagoCuotas" value="cuotas">
                                        <label class="form-check-label" for="pagoCuotas">En cuotas</label>
                                    </div>
                                </div>
                                <div id="cuotasContainer" class="mt-2" style="display:none;">
                                    <label class="small">Cantidad de cuotas</label>
                                    <input type="number" class="form-control form-control-sm mb-2" id="cantidadCuotas" min="2" max="12" value="2" style="max-width:100px;">
                                    <div id="listaCuotas"></div>
                                </div>
                            </div>
                            <!-- 4. Observaciones -->
                            <div class="mb-3">
                                <label class="fw-bold">4. Observaciones <span class="text-muted">(opcional)</span></label>
                                <textarea name="observaciones" class="form-control" rows="2" placeholder="Agregar observaciones..."></textarea>
                            </div>
                        </div>
                        <!-- Columna derecha -->
                        <div class="col-md-4">
                            <!-- 3. Resumen del cobro -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h6 class="fw-bold">Resumen del cobro</h6>
                                    <div class="mb-2 d-flex justify-content-between"><span>Subtotal</span> <span>$<span id="resumenSubtotal">0.00</span></span></div>
                                    <div class="mb-2 d-flex justify-content-between align-items-center">
                                        <span>Descuento</span>
                                        <span>
                                            <input type="number" class="form-control form-control-sm d-inline-block ms-2" style="width:100px;vertical-align:middle;" id="inputDescuento" name="descuento" value="0" min="0" step="0.01" />
                                        </span>
                                    </div>
                                    <div class="mb-2 d-flex justify-content-between"><span>Impuestos (0%)</span> <span>$<span id="resumenImpuestos">0.00</span></span></div>
                                    <div class="mb-2 d-flex justify-content-between fs-5"><strong>Total a cobrar</strong> <span style="color:#009688;font-weight:bold">$<span id="resumenTotal">0.00</span></span></div>
                                    <input type="hidden" name="monto" id="inputMontoTotal">
                                </div>
                            </div>
                            <!-- 5. Método de pago -->
                            <div class="mb-3">
                                <label class="fw-bold">3. Método de pago</label>
                                <select name="metodo_pago" class="form-select" required>
                                    <option value="Efectivo">Efectivo</option>
                                    <option value="Tarjeta">Tarjeta</option>
                                    <option value="Yape/Plin">Yape/Plin</option>
                                    <option value="Transferencia">Transferencia</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <!-- Campos ocultos para detalles y cuotas -->
                    <input type="hidden" name="detalle_json" id="detalle_json">
                    <input type="hidden" name="cuotas_json" id="cuotas_json">
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-info text-white fw-bold w-100">Confirmar Cobro</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
// --- JS para manejo dinámico del modalCobrar ---
$(function() {
// Evitar submit por Enter en el modal de cobro
    $('#formCobro').on('keydown', function(e) {
        if (e.key === 'Enter' && e.target.type !== 'textarea') {
            e.preventDefault();
            return false;
        }
    });
let serviciosProductos = [];
let cuotas = [];

window.cargarDatosCobro = function(id, paciente, precio) {
    document.getElementById('cobro_id').value = id;
    document.getElementById('cobro_paciente').innerText = paciente;

    // Limpiar servicios/productos y totales
    serviciosProductos = [];
    cuotas = [];
    actualizarTablaServiciosProductos();
    $('#inputDescuento').val(0);
    $('#resumenImpuestos').text('0.00');
    $('#resumenTotal').text('0.00');
    $('#detalle_json').val('');
    $('#cuotas_json').val('');
    $('#formCobro')[0].reset();
    // Ocultar cuotas
    $('#cuotasContainer').hide();
    // Si se pasa precio, puedes usarlo para precargar un servicio
    if (precio && !isNaN(parseFloat(precio))) {
        // Precargar un servicio base con referencia numérica (id de la cita o 0)
        // Buscar el nombre del servicio seleccionado en el listado
        let nombreServicio = '';
        let select = document.getElementById('selectServicioProducto');
        if (select) {
            let opt = select.querySelector('option[value^="servicio-"]');
            if (opt) nombreServicio = opt.getAttribute('data-nombre') || 'Servicio principal';
        }
        serviciosProductos.push({
            tipo: 'Servicio',
            referencia: parseInt(id) || 0,
            descripcion: nombreServicio,
            cantidad: 1,
            precio: parseFloat(precio),
            total: parseFloat(precio)
        });
        actualizarTablaServiciosProductos();
    }
}

function actualizarTablaServiciosProductos() {
    let tbody = $('#tablaServiciosProductos tbody');
    tbody.empty();
    let subtotal = 0;
    serviciosProductos.forEach((item, idx) => {
        let total = item.cantidad * item.precio;
        subtotal += total;
        tbody.append(`<tr>
            <td>${item.descripcion}</td>
            <td class="text-center">${item.cantidad}</td>
            <td class="text-end">$${item.precio.toFixed(2)}</td>
            <td class="text-end">$${(total).toFixed(2)}</td>
            <td><button type="button" class="btn btn-sm btn-danger" onclick="eliminarServicioProducto(${idx})"><i class="fas fa-trash"></i></button></td>
        </tr>`);
    });
    $('#resumenSubtotal').text(subtotal.toFixed(2));
    let descuento = parseFloat($('#inputDescuento').val()) || 0;
    let total = subtotal - descuento;
    $('#resumenTotal').text(total.toFixed(2));
    $('#inputMontoTotal').val(total.toFixed(2));
    $('#detalle_json').val(JSON.stringify(serviciosProductos));
}
window.eliminarServicioProducto = function(idx) {
    serviciosProductos.splice(idx, 1);
    actualizarTablaServiciosProductos();
}
$('#btnAgregarServicioProducto').on('click', function() {
    let selected = $('#selectServicioProducto option:selected');
    let value = selected.val();
    if (!value) return;
    let tipo = selected.data('tipo');
    let descripcion = selected.data('nombre');
    let precio = parseFloat($('#precioServicioProducto').val()) || parseFloat(selected.data('precio')) || 0;
    let cantidad = parseInt($('#cantidadServicioProducto').val()) || 1;
    if (!descripcion || precio <= 0 || cantidad <= 0) return;
    serviciosProductos.push({ tipo, referencia: value, descripcion, cantidad, precio, total: cantidad * precio });
    actualizarTablaServiciosProductos();
    $('#precioServicioProducto').val('');
    $('#cantidadServicioProducto').val('1');
});
$('#selectServicioProducto').on('change', function() {
    let selected = $(this).find('option:selected');
    let precio = selected.data('precio');
    $('#precioServicioProducto').val(precio);
});
$('#inputDescuento').on('input', function() {
    actualizarTablaServiciosProductos();
});

// Plan de pago cuotas
$('input[name="plan_pago"]').on('change', function() {
    if ($('#pagoCuotas').is(':checked')) {
        $('#cuotasContainer').show();
        generarCuotas();
    } else {
        $('#cuotasContainer').hide();
        cuotas = [];
        $('#cuotas_json').val('');
    }
});
$('#cantidadCuotas').on('input', function() {
    generarCuotas();
});
function generarCuotas() {
    let total = parseFloat($('#inputMontoTotal').val()) || 0;
    let n = parseInt($('#cantidadCuotas').val()) || 2;
    if (n < 2) n = 2;
    let montoCuota = (total / n).toFixed(2);
    let html = '';
    cuotas = [];
    for (let i = 1; i <= n; i++) {
        let fecha = '';
        let readonly = '';
        let check = (i === 1) ? '<span class="text-success ms-2 align-middle" title="Pagada"><i class="fas fa-check-circle"></i></span>' : '';
        html += `<div class="mb-2 d-flex align-items-center">
            <strong class="me-2">Cuota ${i}:</strong>
            <input type="number" class="form-control d-inline-block" style="width:120px;" value="${montoCuota}" min="0" step="0.01" onchange="actualizarMontoCuota(${i-1}, this.value)" ${readonly}>
            <input type="date" class="form-control d-inline-block ms-2" style="width:170px;" onchange="actualizarFechaCuota(${i-1}, this.value)" ${readonly}>${check}
        </div>`;
        cuotas.push({ numero: i, monto: parseFloat(montoCuota), fecha: '' });
    }
    $('#listaCuotas').html(html);
    $('#cuotas_json').val(JSON.stringify(cuotas));
}
window.actualizarMontoCuota = function(idx, val) {
    cuotas[idx].monto = parseFloat(val) || 0;
    $('#cuotas_json').val(JSON.stringify(cuotas));
}
window.actualizarFechaCuota = function(idx, val) {
    cuotas[idx].fecha = val;
    $('#cuotas_json').val(JSON.stringify(cuotas));
}
$('#formCobro').on('submit', function() {
    // Actualiza los campos ocultos antes de enviar
    $('#detalle_json').val(JSON.stringify(serviciosProductos));
    $('#cuotas_json').val(JSON.stringify(cuotas));
});
});

// --- CARGAR CUOTAS EN MODAL ---
function cargarCuotas(id_cita, paciente) {
    $('#cuotasContenido').html('<div class="text-center text-secondary py-5"><i class="fas fa-spinner fa-spin fa-2x"></i><br> Cargando cuotas...</div>');
    $.ajax({
        url: BASE_URL + '/citas/cuotas',
        method: 'GET',
        data: { id_cita: id_cita },
        dataType: 'json',
        success: function(res) {
            if (!res || !Array.isArray(res) || res.length === 0) {
                $('#cuotasContenido').html('<div class="alert alert-info">No hay cuotas registradas para esta cita.</div>');
                return;
            }
            let totalPlan = 0, totalPendiente = 0, pagadas = 0;
            let html = '';
            html += `<div class="mb-3"><strong>Paciente:</strong> ${paciente}</div>`;
            html += `<div class="table-responsive"><table class="table align-middle">
                <thead><tr>
                    <th>Cuota</th>
                    <th>Vencimiento</th>
                    <th>Monto</th>
                    <th>Pagado</th>
                    <th>Pendiente</th>
                </tr></thead><tbody>`;
            res.forEach(function(cuota, idx) {
                totalPlan += parseFloat(cuota.monto);
                let badge = cuota.pagada ? '<span class="badge bg-success">Pagada</span>' : '<span class="badge bg-warning text-dark">Pendiente</span>';
                let pendiente = cuota.pagada ? 0 : parseFloat(cuota.monto);
                if (!cuota.pagada) totalPendiente += pendiente; else pagadas++;
                html += `<tr>
                    <td>${cuota.numero_cuota} de ${res.length}</td>
                    <td>${cuota.fecha_vencimiento ? new Date(cuota.fecha_vencimiento).toLocaleDateString() : ''}</td>
                    <td>$${parseFloat(cuota.monto).toFixed(2)}</td>
                    <td>${badge}</td>
                    <td>$${pendiente.toFixed(2)}</td>
                </tr>`;
            });
            html += `</tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="2">Total del plan</td>
                        <td>$${totalPlan.toFixed(2)}</td>
                        <td></td>
                        <td>$${totalPendiente.toFixed(2)}</td>
                    </tr>
                </tfoot>
            </table></div>`;
            html += `<div class="alert alert-info mt-3">Has seleccionado ${pagadas} cuota(s) pagada(s) y quedan ${res.length - pagadas} pendiente(s).</div>`;
            $('#cuotasContenido').html(html);
        },
        error: function() {
            $('#cuotasContenido').html('<div class="alert alert-danger">No se pudieron cargar las cuotas.</div>');
        }
    });
}
</script>



<div class="modal fade" id="modalTPV" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 tpv-bg">

      <div class="modal-body p-4">

        <div class="container-fluid">
          <div class="row g-4">

            <!-- COLUMNA IZQUIERDA -->
            <div class="col-md-6">

              <!-- Buscador -->
              <div class="card shadow-sm rounded-4 mb-3">
                <div class="card-body">
                  <input type="text"
                         class="form-control rounded-3"
                         placeholder="Buscar procedimiento o medicamento...">
                </div>
              </div>

              <!-- Tabs -->
              <div class="card shadow-sm rounded-4 mb-3">
                <div class="card-body d-flex justify-content-between">
                  <button class="btn btn-light rounded-3">Consulta</button>
                  <button class="btn btn-primary rounded-3">Procedimientos</button>
                  <button class="btn btn-light rounded-3">Medicamentos</button>
                </div>
              </div>

              <!-- Producto -->
              <div class="card shadow-sm rounded-4 mb-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                  <div>
                    <h6 class="mb-0">Plasma Rico</h6>
                    <small class="text-muted">$300</small>
                  </div>

                  <div class="fw-bold text-success fs-5">$300</div>

                  <button class="btn btn-info text-white rounded-3">
                    Agregar
                  </button>
                </div>
              </div>

            </div>

            <!-- COLUMNA DERECHA -->
            <div class="col-md-6">

              <!-- Datos cuenta -->
              <div class="card shadow-sm rounded-4 mb-3">
                <div class="card-body">
                  <h5 class="fw-bold">CUENTA ACTUAL</h5>

                  <div class="d-flex justify-content-between">
                    <span>Paciente: Ale</span>
                    <span>Fecha: 16/02/2026</span>
                  </div>

                  <span class="badge bg-success mt-2">ABIERTA</span>
                </div>
              </div>

              <!-- Tabla -->
              <div class="card shadow-sm rounded-4 mb-3">
                <div class="card-body">

                  <table class="table">
                    <thead>
                      <tr>
                        <th>Concepto</th>
                        <th>Cant</th>
                        <th>Precio</th>
                        <th>Subtotal</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>Consulta</td>
                        <td>1</td>
                        <td>$500</td>
                        <td>$500</td>
                      </tr>
                    </tbody>
                  </table>

                  <hr>

                  <div class="d-flex justify-content-between fw-bold text-success fs-5">
                    <span>TOTAL:</span>
                    <span>$1000</span>
                  </div>

                  <div class="d-flex justify-content-between text-danger fs-5">
                    <span>Saldo:</span>
                    <span>$1000</span>
                  </div>

                </div>
              </div>

              <!-- Botón cobrar -->
              <button class="btn btn-primary w-100 py-3 rounded-4 fs-5">
                Cobrar $1000
              </button>

              <div class="d-flex gap-3 mt-3">
                <button class="btn btn-outline-secondary w-50 rounded-3">
                  Pago parcial
                </button>
                <button class="btn btn-warning w-50 rounded-3">
                  Diferir Pago
                </button>
              </div>

            </div>

          </div>
        </div>

      </div>

    </div>
  </div>
</div>



<div class="modal fade" id="modalTicket" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header no-print"><h5 class="modal-title fw-bold">Ticket</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-0">
                <div class="print-container ticket-container">
                    <div class="text-center mb-3">
                        <?php if(!empty($empresa['logo'])): ?><img src="<?php echo BASE_URL; ?>/uploads/<?php echo $empresa['logo']; ?>" style="max-height: 50px; margin-bottom: 10px;"><br><?php endif; ?>
                        <h5 class="fw-bold mb-0"><?php echo $empresa['nombre_clinica']; ?></h5><small><?php echo $empresa['direccion']; ?></small><br><small>Tel: <?php echo $empresa['telefono']; ?></small>
                    </div>
                    <div class="border-top border-bottom py-2 my-2"><div class="d-flex justify-content-between"><small>Fecha:</small> <small id="tick_fecha"></small></div><div class="d-flex justify-content-between"><small>Cita #:</small> <small id="tick_id"></small></div></div>
                    <div class="mb-2"><small class="fw-bold">Paciente:</small><br><span id="tick_paciente"></span></div>
                    <div class="mb-3"><small class="fw-bold">Servicio:</small><br><span id="tick_servicio"></span></div>
                    <div class="text-end border-top pt-2"><h5 class="fw-bold">TOTAL: <?php echo $empresa['moneda']; ?> <span id="tick_monto"></span></h5></div>
                    <div class="text-center mt-4"><small>*** Gracias por su preferencia ***</small></div>
                </div>
            </div>
            <div class="modal-footer no-print"><button type="button" class="btn btn-dark w-100" onclick="imprimirTicket()">Imprimir Ticket</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAtender" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white"><h5 class="modal-title fw-bold">Atención Médica</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form action="<?php echo BASE_URL; ?>/citas/finalizar" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="id_cita" id="atender_id">
                    <div class="alert alert-success"><strong>Paciente:</strong> <span id="atender_paciente_nombre"></span></div>
                    <h6 class="text-success fw-bold border-bottom pb-2 mb-3">Triaje</h6>
                    <div class="row mb-3">
                        <div class="col-md-3"><label class="small fw-bold">Peso (kg)</label><input type="number" step="0.01" name="peso" class="form-control"></div>
                        <div class="col-md-3"><label class="small fw-bold">Talla (m)</label><input type="number" step="0.01" name="talla" class="form-control"></div>
                        <div class="col-md-3"><label class="small fw-bold">Presión</label><input type="text" name="presion" class="form-control"></div>
                        <div class="col-md-3"><label class="small fw-bold">Temp (°C)</label><input type="number" step="0.1" name="temperatura" class="form-control"></div>
                    </div>
                    <div class="mb-3"><label class="fw-bold text-success">Diagnóstico</label><textarea name="diagnostico" class="form-control" rows="3" required></textarea></div>
                    <div class="mb-3"><label class="fw-bold text-success">Receta</label><textarea name="prescripcion" class="form-control" rows="4" required></textarea></div>
                    
                    <div class="bg-light p-3 rounded border">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="checkDescanso" onchange="toggleDescanso()">
                            <label class="form-check-label fw-bold" for="checkDescanso">Emitir Descanso Médico</label>
                        </div>
                        <div class="mt-2 d-none" id="boxDescanso">
                            <label class="small fw-bold">Días de Reposo:</label>
                            <input type="number" name="dias_reposo" class="form-control w-25" value="0">
                        </div>
                    </div>

                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-success">Finalizar</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCertificado" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header no-print"><h5 class="modal-title fw-bold">Certificado Médico</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-0">
                <div class="certificado-container">
                    <div class="certificado-header text-center">
                        <?php if(!empty($empresa['logo'])): ?><img src="<?php echo BASE_URL; ?>/uploads/<?php echo $empresa['logo']; ?>" style="max-height: 70px; margin-bottom: 10px;"><br><?php endif; ?>
                        <h3 class="fw-bold text-uppercase">Certificado Médico</h3>
                        <small><?php echo $empresa['nombre_clinica']; ?> | <?php echo $empresa['direccion']; ?></small>
                    </div>
                    <div class="certificado-body mt-4">
                        <p>El médico que suscribe, <strong>Dr(a). <span id="cert_medico"></span></strong> con C.M.P. <span id="cert_colegiatura"></span>, certifica que:</p>
                        <p class="my-4">El paciente: <strong class="fs-5 text-uppercase"><span id="cert_paciente"></span></strong></p>
                        <p>Identificado con DNI/Doc: <span id="cert_dni"></span></p>
                        <p>Fue atendido el día: <strong><span id="cert_fecha"></span></strong> en la especialidad de <span id="cert_especialidad"></span>.</p>
                        <p><strong>Diagnóstico:</strong> <span id="cert_diagnostico"></span></p>
                        <p class="mt-4">Por lo cual se prescribe <strong><span id="cert_dias"></span> DÍAS DE REPOSO MÉDICO</strong>, a partir de la fecha de atención.</p>
                        <p class="mt-5">Se expide el presente para los fines que el interesado crea conveniente.</p>
                    </div>
                    <div class="certificado-footer text-center">
                        <div class="row">
                            <div class="col-6 offset-3">
                                <p class="mb-0">_______________________________</p>
                                <strong>Dr(a). <span id="cert_firma"></span></strong><br>
                                <small>Firma y Sello</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer no-print"><button type="button" class="btn btn-dark" onclick="imprimirCertificado()">Imprimir</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalReceta" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header no-print"><h5 class="modal-title fw-bold">Receta</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-0">
                <div class="receta-container">
                    <div class="receta-header d-flex justify-content-between align-items-center"><div><h2 class="fw-bold mb-0"><?php echo isset($empresa['nombre_clinica']) ? $empresa['nombre_clinica'] : 'Clínica'; ?></h2><small class="text-muted"><?php echo isset($empresa['direccion']) ? $empresa['direccion'] : ''; ?></small></div><div class="text-end"><h5 class="fw-bold mb-0">RECETA MÉDICA</h5><small>Fecha: <span id="rec_fecha"></span></small></div></div>
                    <div class="row mb-4"><div class="col-6"><strong>Paciente:</strong> <span id="rec_paciente"></span></div><div class="col-6 text-end"><strong>Dr:</strong> <span id="rec_medico"></span></div></div>
                    <div class="row mb-3 p-2 bg-light border rounded small text-center"><div class="col-3"><strong>Peso:</strong> <span id="rec_peso"></span> kg</div><div class="col-3"><strong>Talla:</strong> <span id="rec_talla"></span> m</div><div class="col-3"><strong>P.A.:</strong> <span id="rec_presion"></span></div><div class="col-3"><strong>Temp:</strong> <span id="rec_temp"></span> °C</div></div>
                    <div class="receta-body"><h6 class="fw-bold text-uppercase border-bottom pb-1">Diagnóstico:</h6><p id="rec_diagnostico" class="mb-4"></p><h6 class="fw-bold text-uppercase border-bottom pb-1">Prescripción:</h6><p id="rec_prescripcion" style="white-space: pre-line;"></p></div>
                    <div class="receta-footer text-center"><p class="mb-0">__________________________</p><small>Firma</small><br><strong id="rec_medico_firma"></strong></div>
                </div>
            </div>
            <div class="modal-footer no-print"><button type="button" class="btn btn-dark" onclick="imprimirReceta()">Imprimir</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning text-dark"><h5 class="modal-title fw-bold">Editar Cita</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="<?php echo BASE_URL; ?>/citas/actualizar" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="id_cita" id="edit_id">
                    <div class="mb-3"><label class="fw-bold">Estado</label><select name="estado" id="edit_estado" class="form-select border-warning"><option value="Pendiente">Pendiente</option><option value="Confirmada">Confirmada</option><option value="Finalizada">Finalizada</option><option value="Cancelada">Cancelada</option></select></div>
                    <div class="mb-3"><label class="fw-bold">Servicio</label><select name="id_servicio" id="edit_servicio" class="form-select" required><?php $listaServicios->execute(); while($serv = $listaServicios->fetch(PDO::FETCH_ASSOC)): ?><option value="<?php echo $serv['id_servicio']; ?>"><?php echo $serv['nombre_servicio']; ?></option><?php endwhile; ?></select></div>
                    <div class="mb-3"><label class="fw-bold">Médico</label><select name="medico_id" id="edit_medico" class="form-select" required><?php $listaMedicos->execute(); while($med = $listaMedicos->fetch(PDO::FETCH_ASSOC)): ?><option value="<?php echo $med['id_medico']; ?>"><?php echo $med['nombre']; ?></option><?php endwhile; ?></select></div>
                    <div class="mb-3"><label>Fecha</label><input type="datetime-local" name="fecha" id="edit_fecha" class="form-control" required></div>
                    <div class="mb-3"><label>Motivo</label><textarea name="motivo" id="edit_motivo" class="form-control" required></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-warning fw-bold">Actualizar</button></div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleDescanso() {
        var check = document.getElementById('checkDescanso');
        var box = document.getElementById('boxDescanso');
        if(check.checked) box.classList.remove('d-none');
        else box.classList.add('d-none');
    }

    function actualizarPrecio(select) {
        var option = select.options[select.selectedIndex];
        var precio = option.getAttribute('data-precio');
        var div = document.getElementById('precio_preview');
        if(precio) div.innerHTML = 'Costo: <?php echo isset($empresa["moneda"]) ? $empresa["moneda"] : "S/."; ?> ' + parseFloat(precio).toFixed(2);
        else div.innerHTML = '';
    }


    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        if (calendarEl) {
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
                events: '<?php echo BASE_URL; ?>/citas/listarEventos',
                eventClick: function(info) {
                    Swal.fire({
                        title: info.event.title,
                        html: `Estado: ${info.event.extendedProps.estado}`,
                        icon: 'info'
                    });
                }
            });
            // Forzar renderizado inmediato
            calendar.render();
            // También renderizar si el usuario cambia de pestaña
            var tabEl = document.querySelector('button[data-bs-target="#calendario"]');
            if (tabEl) {
                tabEl.addEventListener('shown.bs.tab', function (event) {
                    calendar.render();
                });
            }
        }
    });

    window.onload = function() {
        var now = new Date();
        
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById('fecha_input').min = now.toISOString().slice(0,16);
    };

    document.getElementById('buscador').addEventListener('keyup', function() {
        let filtro = this.value.toLowerCase();
        let filas = document.querySelectorAll('#tablaCitas tbody tr');
        filas.forEach(fila => {
            let texto = fila.innerText.toLowerCase();
            fila.style.display = texto.includes(filtro) ? '' : 'none';
        });
    });

    function cargarDatosEditar(id, medico, servicio, fecha, motivo, estado) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_medico').value = medico;
        document.getElementById('edit_servicio').value = servicio;
        document.getElementById('edit_fecha').value = fecha;
        document.getElementById('edit_motivo').value = motivo;
        document.getElementById('edit_estado').value = estado;
    }

    function cargarDatosAtender(id, paciente) {
        document.getElementById('atender_id').value = id;
        document.getElementById('atender_paciente_nombre').innerText = paciente;
    }

    function cargarDatosCobro(id, paciente, monto) {
        document.getElementById('cobro_id').value = id;
        document.getElementById('cobro_paciente').innerText = paciente;
        document.getElementById('cobro_monto').value = monto ? monto : '0.00';
    }

    function cargarReceta(paciente, medico, especialidad, fecha, diagnostico, prescripcion, peso, talla, presion, temp) {
        document.getElementById('rec_paciente').innerText = paciente;
        document.getElementById('rec_medico').innerText = medico;
        document.getElementById('rec_medico_firma').innerText = medico;
        document.getElementById('rec_especialidad').innerText = especialidad;
        document.getElementById('rec_fecha').innerText = fecha;
        document.getElementById('rec_diagnostico').innerText = diagnostico;
        document.getElementById('rec_prescripcion').innerText = prescripcion;
        document.getElementById('rec_peso').innerText = peso || '-';
        document.getElementById('rec_talla').innerText = talla || '-';
        document.getElementById('rec_presion').innerText = presion || '-';
        document.getElementById('rec_temp').innerText = temp || '-';
    }

    function cargarCertificado(paciente, medico, especialidad, dni, fecha, dias, diagnostico, colegiatura) {
        document.getElementById('cert_paciente').innerText = paciente;
        document.getElementById('cert_medico').innerText = medico;
        document.getElementById('cert_firma').innerText = medico;
        document.getElementById('cert_especialidad').innerText = especialidad;
        document.getElementById('cert_dni').innerText = dni;
        document.getElementById('cert_fecha').innerText = fecha;
        document.getElementById('cert_dias').innerText = dias;
        document.getElementById('cert_diagnostico').innerText = diagnostico;
        document.getElementById('cert_colegiatura').innerText = colegiatura;
    }

    function cargarTicket(id, paciente, servicio, monto, fecha) {
        document.getElementById('tick_id').innerText = id;
        document.getElementById('tick_paciente').innerText = paciente;
        document.getElementById('tick_servicio').innerText = servicio;
        document.getElementById('tick_monto').innerText = parseFloat(monto).toFixed(2);
        document.getElementById('tick_fecha').innerText = fecha;
    }

    function imprimirReceta() {
        document.body.classList.add('printing-modal');
        // Asegurar que los otros modales no interfieran
        var modalTicket = document.getElementById('modalTicket');
        var modalCert = document.getElementById('modalCertificado');
        if(modalTicket) modalTicket.classList.remove('show');
        if(modalCert) modalCert.classList.remove('show');
        
        window.print();
        document.body.classList.remove('printing-modal');
    }

    function imprimirTicket() {
        document.body.classList.add('printing-modal');
        var modalReceta = document.getElementById('modalReceta');
        if(modalReceta) modalReceta.classList.remove('show');
        window.print();
        document.body.classList.remove('printing-modal');
    }
    
    function imprimirCertificado() {
        document.body.classList.add('printing-modal');
        window.print();
        document.body.classList.remove('printing-modal');
    }

    function confirmarEliminacion(id) {
        Swal.fire({
            title: '¿Eliminar?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Eliminar'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = "<?php echo BASE_URL; ?>/citas/eliminar?id=" + id;
        })
    }
</script>

<?php require_once APP_ROOT . '/views/layouts/footer.php'; ?>