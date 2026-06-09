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
                    <button class="btn btn-outline-primary fw-bold px-3 py-3 rounded-pill align-self-center" style="height:auto;min-height:unset;line-height:1.2;min-width:150px;margin-left:8px;" data-bs-toggle="modal" data-bs-target="#modalCotizar"> Cotizar</button>
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
                                           
                                            printf('<a href="#" class="badge bg-light text-dark me-2" data-bs-toggle="modal" data-bs-target="#modalCuotas" onclick="cargarCuotas(\'%s\', \'%s\')">%s/%s</a>', addslashes($row['id_pago']), addslashes($row['paciente']), $res['pagadas'], $res['total']);
                                        } else {
                                            if($row['id_pago']!='')
                                            printf('<a href="#" class="badge bg-secondary me-2" data-bs-toggle="modal" data-bs-target="#modalCuotas" onclick="cargarCuotas(\'%s\', \'%s\')">-</a>', addslashes($row['id_pago']), addslashes($row['paciente']));
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

<!-- Modal Cotización (esqueleto) -->
<div class="modal fade" id="modalCotizar" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold">Nueva Cotización</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCotizacion" action="<?php echo BASE_URL; ?>/cotizaciones/guardar" method="POST">
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="fw-bold">Paciente</label>
                                <select name="id_paciente" class="form-select select2-paciente"><option value="">-- Seleccione un paciente --</option>
                                    <?php if (!empty($pacientes) && is_array($pacientes)): ?>
                                        <?php foreach($pacientes as $pac): ?>
                                            <option value="<?php echo $pac['id_usuario']; ?>"><?php echo $pac['nombre']; ?> - <?php echo $pac['telefono']; ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="fw-bold">1. Agregar servicios y productos</label>
                                <div class="input-group mb-2">
                                    <select class="form-select" id="selectCotizarProducto">
                                        <option value="">Buscar servicio o producto...</option>
                                        <optgroup label="Servicios">
                                            <?php $listaServicios->execute(); while($serv = $listaServicios->fetch(PDO::FETCH_ASSOC)): ?>
                                                <option value="servicio-<?php echo $serv['id_servicio']; ?>" data-tipo="Servicio" data-nombre="<?php echo $serv['nombre_servicio']; ?>" data-precio="<?php echo $serv['precio']; ?>"><?php echo $serv['nombre_servicio']; ?></option>
                                            <?php endwhile; ?>
                                        </optgroup>
                                        <optgroup label="Productos">
                                            <?php $listaMedicamentos->execute(); while($med = $listaMedicamentos->fetch(PDO::FETCH_ASSOC)): ?>
                                                <option value="producto-<?php echo $med['id_medicamento']; ?>" data-tipo="Producto" data-nombre="<?php echo $med['nombre_comercial']; ?>" data-precio="<?php echo $med['precio_venta'] ?? 0; ?>"><?php echo $med['nombre_comercial']; ?></option>
                                            <?php endwhile; ?>
                                        </optgroup>
                                    </select>
                                    <input type="number" class="form-control" id="precioCotizar" placeholder="Precio unitario" min="0" step="0.01">
                                    <input type="number" class="form-control" id="cantidadCotizar" placeholder="Cantidad" value="1" min="1" style="max-width:120px;">
                                    <button type="button" class="btn btn-outline-primary" id="btnAgregarCotizacion">Agregar</button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm" id="tablaCotizacionItems"><thead><tr><th>Descripción</th><th class="text-center">Cantidad</th><th class="text-end">Precio</th><th class="text-end">Total</th><th></th></tr></thead><tbody></tbody></table>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="fw-bold">3. Observaciones (opcional)</label>
                                <textarea name="observaciones" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card p-3">
                                <h6 class="fw-bold">Resumen de cotización</h6>
                                <div class="d-flex justify-content-between"><span>Subtotal</span><span id="cot_subtotal"><?php echo $empresa['moneda'] ?? 'S/.'; ?> 0.00</span></div>
                                <div class="mt-2"><label class="small">Descuento</label><div class="input-group"><select id="cot_desc_tipo" class="form-select" style="max-width:120px;"><option value="fijo">Monto</option><option value="porcentaje" selected>%</option></select><input type="number" id="cot_desc_valor" class="form-control" value="0" min="0" step="0.01"></div></div>
                                <hr>
                                <div class="d-flex justify-content-between fw-bold fs-4"><span>TOTAL</span><span id="cot_total"><?php echo $empresa['moneda'] ?? 'S/.'; ?> 0.00</span></div>
                                <input type="hidden" name="subtotal" id="input_cot_subtotal">
                                <input type="hidden" name="descuento_tipo" id="input_cot_desc_tipo">
                                <input type="hidden" name="descuento_valor" id="input_cot_desc_valor">
                                <input type="hidden" name="total" id="input_cot_total">
                                <input type="hidden" name="id_cotizacion" id="input_id_cotizacion">
                                <div class="mt-3 d-grid">
                                    <div class="d-flex gap-2">
                                        <button type="submit" id="btnGuardarCotizacion" class="btn btn-primary flex-grow-1">Guardar cotización</button>
                                        <button type="button" id="btnImprimirCotizacion" class="btn btn-outline-secondary">Imprimir</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Lógica básica para el modal de cotización (añadir items y calcular totales)
let cotItems = [];
const COT_MONEDA = '<?php echo $empresa['moneda'] ?? 'S/.'; ?>';

$('#btnAgregarCotizacion').on('click', function() {
    let opt = $('#selectCotizarProducto option:selected');
    let val = opt.val(); if (!val) return;
    let tipo = opt.data('tipo');
    let descripcion = opt.data('nombre');
    let precio = parseFloat($('#precioCotizar').val()) || parseFloat(opt.data('precio')) || 0;
    let cantidad = parseInt($('#cantidadCotizar').val()) || 1;
    let total = precio * cantidad;
    cotItems.push({ tipo: tipo, referencia: val, descripcion: descripcion, cantidad: cantidad, precio: precio, total: total });
    renderCotItems();
});
// Al cambiar selección, rellenar precio unitario automáticamente (acepta 0)
$('#selectCotizarProducto').on('change', function() {
    var opt = $(this).find('option:selected');
    var raw = opt.data('precio');
    if (typeof raw !== 'undefined' && raw !== null) {
        var precio = parseFloat(raw);
        if (isNaN(precio)) precio = 0;
        $('#precioCotizar').val(precio);
    } else {
        $('#precioCotizar').val('');
    }
});

function renderCotItems() {
    let tbody = $('#tablaCotizacionItems tbody'); tbody.empty(); let subtotal = 0;
    cotItems.forEach((it, i) => {
        subtotal += it.total;
        tbody.append(`<tr>
            <td>${it.descripcion}</td>
            <td class="text-center">${it.cantidad}</td>
            <td class="text-end">${COT_MONEDA} ${it.precio.toFixed(2)}</td>
            <td class="text-end">${COT_MONEDA} ${it.total.toFixed(2)}</td>
            <td><button type="button" class="btn btn-sm btn-danger" onclick="cotEliminar(${i})">Eliminar</button></td>
        </tr>`);
    });

    $('#cot_subtotal').text(COT_MONEDA + ' ' + subtotal.toFixed(2));
    let descTipo = $('#cot_desc_tipo').val();
    let descVal = parseFloat($('#cot_desc_valor').val()) || 0;
    let descuento = descTipo === 'porcentaje' ? subtotal * (descVal/100) : descVal;
    let total = subtotal - descuento; if (total < 0) total = 0;
    $('#cot_total').text(COT_MONEDA + ' ' + total.toFixed(2));
    $('#input_cot_subtotal').val(subtotal.toFixed(2));
    $('#input_cot_desc_tipo').val(descTipo);
    $('#input_cot_desc_valor').val(descVal);
    $('#input_cot_total').val(total.toFixed(2));

    // serializar detalles antes de enviar
    let detalles = cotItems.map(it => ({ tipo: it.tipo, referencia: it.referencia, descripcion: it.descripcion, cantidad: it.cantidad, precio: it.precio, total: it.total }));
    if ($('#formCotizacion input[name="detalles_json"]').length === 0) {
        $('#formCotizacion').append('<input type="hidden" name="detalles_json">');
    }
    $('#formCotizacion input[name="detalles_json"]').val(JSON.stringify(detalles));
}

function cotEliminar(i) { cotItems.splice(i,1); renderCotItems(); }
$('#cot_desc_tipo, #cot_desc_valor').on('input change', renderCotItems);

// Guardado vía AJAX (crea o actualiza según input_id_cotizacion)
function ajaxSaveCotizacion(callback) {
    renderCotItems(); // asegurar detalles actualizados
    var form = $('#formCotizacion');
    var url = form.attr('action');
    var data = form.serialize();
    $.post(url, data, function(resp) {
        try { var j = typeof resp === 'object' ? resp : JSON.parse(resp); } catch(e){ var j = { success: false }; }
        if (j && j.success) {
            if (j.id) $('#input_id_cotizacion').val(j.id);
            if (callback) callback(j);
        } else {
            alert('Error al guardar la cotización');
        }
    }).fail(function(){ alert('Error de red al guardar'); });
}

// Generar y abrir una vista de impresión en nueva ventana usando los datos actuales
function generarPreviewImpresion() {
    var empresa = {
        nombre: '<?php echo addslashes($empresa['nombre'] ?? 'Mi Empresa'); ?>',
        direccion: '<?php echo addslashes($empresa['direccion'] ?? ''); ?>',
        telefono: '<?php echo addslashes($empresa['telefono'] ?? ''); ?>',
        moneda: COT_MONEDA
    };
    var pacienteText = $('#formCotizacion select[name="id_paciente"] option:selected').text() || '';
    var observaciones = $('#formCotizacion textarea[name="observaciones"]').val() || '';
    var subtotal = $('#input_cot_subtotal').val() || '0.00';
    var total = $('#input_cot_total').val() || '0.00';

    let html = `<!doctype html><html><head><meta charset="utf-8"><title>Cotización</title><style>body{font-family:Arial,Helvetica,sans-serif;padding:20px;color:#000}h2{margin:0 0 10px}table{width:100%;border-collapse:collapse}th,td{border-bottom:1px solid #ddd;padding:8px;text-align:left}thead th{background:#f5f5f5} .right{text-align:right}</style></head><body>`;
    html += `<div><h2>${empresa.nombre}</h2><div>${empresa.direccion}</div><div>${empresa.telefono}</div><hr></div>`;
    html += `<div><strong>Paciente:</strong> ${pacienteText}</div><div style="margin-top:8px"><strong>Observaciones:</strong> ${observaciones}</div>`;
    html += `<table style="margin-top:16px"><thead><tr><th>Descripción</th><th style="width:80px">Cant.</th><th class="right">Precio</th><th class="right">Total</th></tr></thead><tbody>`;
    cotItems.forEach(it => {
        html += `<tr><td>${it.descripcion}</td><td class="right">${it.cantidad}</td><td class="right">${empresa.moneda} ${parseFloat(it.precio).toFixed(2)}</td><td class="right">${empresa.moneda} ${parseFloat(it.total).toFixed(2)}</td></tr>`;
    });
    html += `</tbody></table>`;
    html += `<div style="margin-top:12px;text-align:right"><div>Subtotal: ${empresa.moneda} ${parseFloat(subtotal).toFixed(2)}</div><div style="font-weight:bold;font-size:1.2rem">TOTAL: ${empresa.moneda} ${parseFloat(total).toFixed(2)}</div></div>`;
    html += `<div style="margin-top:24px;font-size:12px;color:#666">Documento generado el ${new Date().toLocaleString()}</div>`;
    html += `</body></html>`;

    var w = window.open('', '_blank');
    w.document.open();
    w.document.write(html);
    w.document.close();
    // esperar a que cargue y lanzar imprimir
    setTimeout(function(){ w.print(); }, 500);
}

// Manejo del botón Imprimir: guarda (si es necesario), mantiene el modal abierto y abre PDF en nueva pestaña sin alertas
$('#btnImprimirCotizacion').on('click', function() {
    ajaxSaveCotizacion(function(resp){
        if (resp && resp.success && resp.id) {
            $('#input_id_cotizacion').val(resp.id);
            window.open(BASE_URL + '/cotizaciones/imprimir?id=' + resp.id, '_blank');
        } else {
            // opcional: mostrar mensaje ligero dentro del modal en lugar de alert
            console.error('No se pudo guardar la cotización para imprimir', resp);
        }
    });
});

// Interceptar submit del formulario para usar AJAX y evitar recarga
$('#formCotizacion').on('submit', function(e){
    e.preventDefault();
    ajaxSaveCotizacion(function(resp){
        if (resp && resp.success) {
            alert('Cotización guardada.');
            $('#modalCotizar').modal('hide');
        } else {
            alert('Error al guardar la cotización');
        }
    });
});

</script>
</script>



<!-- Modal de Cuotas (idéntico al diseño) -->
<div class="modal fade" id="modalCuotas" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0" style="box-shadow:0 0 32px #00bfff55;">
            <div class="modal-header" style="background: linear-gradient(90deg,#1ec6ea,#00bfff); color:#fff;">
                <h5 class="modal-title fw-bold"><i class="fas fa-list-ol me-2"></i>Registrar Pago de Cuotas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" style="background:#fafdff;">
                <!-- Encabezado paciente -->
                <div class="alert border-0 d-flex align-items-center mb-4" style="background:#e6f7fb;">
                    <i class="fas fa-user fa-2x me-3" style="color:#0096c7;"></i>
                    <div>
                        <span class="fw-bold" style="font-size:1.1rem; color:#0096c7;">Paciente: <span id="cuotasPaciente"></span></span><br>
                        <span id="msgEncabezadoCuotas" style="color:#0096c7;">Selecciona las cuotas que deseas pagar y el método de pago.</span>
                    </div>
                </div>
                <div class="row g-4">
                    <!-- Columna izquierda: cuotas -->
                    <div class="col-md-7">
                        <div class="fw-bold mb-2" style="color:#0096c7;">1. Seleccionar cuotas a pagar</div>
                        <div id="cuotasContenido">
                            <div class="text-center text-secondary py-5">
                                <i class="fas fa-spinner fa-spin fa-2x"></i><br> Cargando cuotas...
                            </div>
                        </div>
                    </div>
                    <!-- Columna derecha: método, detalle, observaciones -->
                    <div class="col-md-5" id="colCuotasDerecha">
                        <div class="mb-3">
                            <label class="fw-bold mb-2" style="color:#0096c7;">2. Método de pago</label>
                            <select id="metodoPagoCuota" class="form-select">
                                <option value="">Selecciona...</option>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Tarjeta">Tarjeta</option>
                                <option value="Yape">Yape</option>
                                <option value="Plin">Plin</option>
                                <option value="Transferencia">Transferencia</option>
                            </select>
                            <div class="mt-2" id="comprobanteTransferenciaBox" style="display:none;">
                                <label class="fw-bold mb-2">Comprobante de Transferencia</label>
                                <input type="file" id="comprobanteTransferencia" class="form-control" accept="image/*,application/pdf">
                            </div>
                        </div>
                        <div class="mb-3 p-3 rounded" style="background:#f3fafd;border:1px solid #e0f0fa;">
                            <label class="fw-bold mb-2" style="color:#0096c7;">3. Detalle del pago</label>
                            <div class="mb-2 d-flex justify-content-between"><span>Total seleccionado</span> <span id="detalleTotalSeleccionado">S/. 0.00</span></div>
                            <div class="mb-2 d-flex justify-content-between align-items-center">
                                <span>Descuento</span>
                                <input type="number" class="form-control form-control-sm d-inline-block ms-2" style="width:100px;vertical-align:middle;" id="inputDescuentoCuota" value="0" min="0" step="0.01" />
                            </div>
                            <div class="mb-2 d-flex justify-content-between fs-5"><strong>Total a pagar</strong> <span id="detalleTotalPagar" style="color:#00b894;font-weight:bold">S/. 0.00</span></div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold mb-2" style="color:#0096c7;">4. Observaciones <span class="text-muted">(opcional)</span></label>
                            <textarea id="observacionesCuota" class="form-control" rows="2" placeholder="Agregar observaciones..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
                <div class="alert alert-info mt-4 mb-0" id="msgSeleccionCuotas" style="background:#e6f7fb;color:#0096c7;border:0;font-weight:500;"></div>
            </div>
            <div class="modal-footer p-0" id="footerCuotas" style="background:linear-gradient(90deg,#1ec6ea,#00bfff);border:0;">
                <div class="w-100 d-flex justify-content-center">
                    <button type="button" class="btn fw-bold text-white" id="btnPagarCuotas" style="background:none;font-size:1.1rem;max-width:340px;width:100%;margin:16px 24px;box-shadow:0 2px 8px #00bfff33;background:linear-gradient(90deg,#1ec6ea,#00bfff);border-radius:12px;">
                        <i class="fas fa-credit-card me-2"></i>Registrar Pago
                    </button>
                </div>
            </div>
        <script>
        // Mostrar/ocultar campo comprobante según método de pago
        $(document).on('change', '#metodoPagoCuota', function() {
            if ($(this).val() === 'Transferencia') {
                $('#comprobanteTransferenciaBox').show();
            } else {
                $('#comprobanteTransferenciaBox').hide();
                $('#comprobanteTransferencia').val('');
            }
        });
        </script>
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
                <h5 class="modal-title fw-bold">Registrar Cobro <small id="cobro_folio" class="text-white-50 ms-2"></small></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCobro" action="<?php echo BASE_URL; ?>/citas/cobrar" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="id_cita" id="cobro_id">
                    <input type="hidden" name="id_cotizacion" id="cobro_id_cotizacion">
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
                                                    <option value="producto-<?php echo $med['id_medicamento']; ?>" data-tipo="Producto" data-nombre="<?php echo $med['nombre_comercial']; ?>" data-precio="<?php echo isset($med['precio_venta']) ? $med['precio_venta'] : 0; ?>">
                                                        <?php echo $med['nombre_comercial']; ?> (<?php echo ($empresa['moneda'] ?? 'S/.') . ' ' . number_format($med['precio_venta'] ?? 0,2); ?>)
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

    // Si se abrió la página con ?cotizacion_convertir=ID, solicitar la cotización y precargar el modalCobrar
    try {
        const params = new URLSearchParams(window.location.search);
        const cotId = params.get('cotizacion_convertir');
        if (cotId) {
            // pedir preview de la cotización
            $.post(BASE_URL + '/cotizaciones/convertir', { id_cotizacion: cotId, preview: 1 }, function(resp) {
                if (resp && resp.success && resp.cotizacion) {
                    const cot = resp.cotizacion;
                    // cargar paciente si se conoce el id
                    let pacienteText = '-';
                    if (cot.id_paciente) {
                        // intentar obtener nombre desde el select2 o dejar id
                        let opt = $('.select2-paciente option[value="' + cot.id_paciente + '"]');
                        if (opt && opt.length) pacienteText = opt.text(); else pacienteText = 'Paciente #' + cot.id_paciente;
                    }
                    // vaciar y precargar serviciosProductos con los detalles de la cotización
                    serviciosProductos = [];
                    if (Array.isArray(cot.detalles)) {
                        cot.detalles.forEach(function(d) {
                            serviciosProductos.push({
                                tipo: d.tipo || 'Item',
                                referencia: d.id_referencia || d.referencia || null,
                                descripcion: d.descripcion || '',
                                cantidad: parseInt(d.cantidad) || 1,
                                precio: parseFloat(d.precio) || 0,
                                total: parseFloat(d.total) || (parseInt(d.cantidad)||1) * (parseFloat(d.precio)||0)
                            });
                        });
                    }
                    // setear campos y abrir modal
                    $('#cobro_id').val('');
                    var cotIdVal = cot.id_cotizacion || cot.id || cotId;
                    $('#cobro_id_cotizacion').val(cotIdVal);
                    $('#cobro_paciente').text(pacienteText);
                    $('#cobro_folio').text(cot.folio ? ('Folio: ' + cot.folio) : ('Cot.' + cotIdVal));
                    actualizarTablaServiciosProductos();
                    // set monto/summary
                    let total = parseFloat(cot.total) || 0;
                    $('#resumenSubtotal').text((parseFloat(cot.subtotal)||0).toFixed(2));
                    $('#inputDescuento').val(parseFloat(cot.descuento_valor) || 0);
                    $('#resumenTotal').text(total.toFixed(2));
                    $('#inputMontoTotal').val(total.toFixed(2));
                    $('#detalle_json').val(JSON.stringify(serviciosProductos));
                    // abrir modalCobrar
                    var myModal = new bootstrap.Modal(document.getElementById('modalCobrar'));
                    myModal.show();
                } else {
                    console.error('No se pudo obtener cotización preview', resp);
                    alert('No se pudo cargar la cotización para convertir.');
                }
            }, 'json').fail(function(){ alert('Error al solicitar la cotización'); });
        }
    } catch(e) {
        console.error(e);
    }
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
// Recibe id_pago en vez de id_cita

// Restaurar función antigua y mejorar para checkboxes
function cargarCuotas(id_pago, paciente) {
    let monedaCuota = '<?php echo isset($empresa['moneda']) ? $empresa['moneda'] : 'S/.'; ?>';
    let cuotasSeleccionadas = [];
    $('#cuotasPaciente').text(paciente);
    $('#totalCuotasSeleccionadas').text(monedaCuota + ' 0.00');
    $('#metodoPagoCuota').val('');
    $('#cuotasContenido').html('<div class="text-center text-secondary py-5"><i class="fas fa-spinner fa-spin fa-2x"></i><br> Cargando cuotas...</div>');
    $.ajax({
        url: BASE_URL + '/index.php?ajax=cuotas_cita',
        method: 'GET',
        data: { id_pago: id_pago },
        dataType: 'json',
        success: function(res) {
            if (!res || !Array.isArray(res) || res.length === 0) {
                $('#cuotasContenido').html('<div class="alert alert-info">No hay cuotas registradas para esta cita.</div>');
                return;
            }
            let html = '';
            html += `<div class="table-responsive"><table class="table align-middle table-bordered mb-0">
                <thead><tr>
                    <th></th>
                    <th>Cuota</th>
                    <th>Vencimiento</th>
                    <th>Monto</th>
                    <th>Estado</th>
                </tr></thead><tbody>`;
            res.forEach(function(cuota, idx) {
                let checked = cuota.pagada ? 'disabled checked' : '';
                let estado = cuota.pagada ? '<span class="badge bg-success">Pagada</span>' : '<span class="badge bg-warning text-dark">Pendiente</span>';
                html += `<tr>
                    <td><input type="checkbox" class="form-check-input cuota-checkbox" data-idx="${idx}" value="${cuota.id_cuota}" ${checked}></td>
                    <td>${cuota.numero_cuota || cuota.numero || (idx+1)} de ${res.length}</td>
                    <td>${cuota.fecha_vencimiento ? (cuota.fecha_vencimiento.length === 10 ? cuota.fecha_vencimiento : (new Date(cuota.fecha_vencimiento)).toLocaleDateString()) : ''}</td>
                    <td>${monedaCuota} ${parseFloat(cuota.monto).toFixed(2)}</td>
                    <td>${estado}</td>
                </tr>`;
            });
            html += `</tbody></table></div>`;
            $('#cuotasContenido').html(html);
            // Mostrar/ocultar columna derecha y footer si todas las cuotas están pagadas
            let allPaid = res.every(function(c){ return c.pagada === true || c.pagada === '1' || c.pagada === 1; });
            if (allPaid) {
                $('#colCuotasDerecha').hide();
                $('#footerCuotas').hide();
                $('#msgSeleccionCuotas').html('Todas las cuotas ya fueron pagadas.');
                $('#msgEncabezadoCuotas').hide();
            } else {
                $('#colCuotasDerecha').show();
                $('#footerCuotas').show();
                $('#msgEncabezadoCuotas').show();
            }
            // Evento para checkboxes

            $('.cuota-checkbox').on('change', function() {
                actualizarTotalesCuotas();
            });

            // Inicializar totales
            actualizarTotalesCuotas();

            function actualizarTotalesCuotas() {
                let total = 0;
                cuotasSeleccionadas = [];
                $('.cuota-checkbox:checked:not(:disabled)').each(function() {
                    let idx = $(this).data('idx');
                    let cuota = res[idx];
                    if (cuota && !cuota.pagada) {
                        total += parseFloat(cuota.monto);
                        cuotasSeleccionadas.push(cuota.id_cuota);
                    }
                });
                $('#totalCuotasSeleccionadas').text(monedaCuota + ' ' + total.toFixed(2));
                $('#detalleTotalSeleccionado').text(monedaCuota + ' ' + total.toFixed(2));
                let descuento = parseFloat($('#inputDescuentoCuota').val()) || 0;
                let descuentoPorCuota = cuotasSeleccionadas.length > 0 ? (descuento / cuotasSeleccionadas.length) : 0;
                let totalPagar = total - descuento;
                if (totalPagar < 0) totalPagar = 0;
                $('#detalleTotalPagar').text(monedaCuota + ' ' + totalPagar.toFixed(2));
                if (allPaid) {
                    $('#msgSeleccionCuotas').html('');
                } else {
                    $('#msgSeleccionCuotas').html(cuotasSeleccionadas.length > 0 ? `Has seleccionado <b>${cuotasSeleccionadas.length}</b> cuota(s) por un total de <b>${monedaCuota} ${total.toFixed(2)}</b>. Descuento por cuota: <b>${monedaCuota} ${descuentoPorCuota.toFixed(2)}</b>` : 'Selecciona cuotas para pagar.');
                }
            }

            // Guardar selección para el botón de pago
            $('#btnPagarCuotas').off('click').on('click', function() {
                if (cuotasSeleccionadas.length === 0) {
                    alert('Selecciona al menos una cuota a pagar.');
                    return;
                }
                let metodo = $('#metodoPagoCuota').val();
                if (!metodo) {
                    alert('Selecciona el método de pago.');
                    return;
                }
                // AJAX para registrar pago
                $.ajax({
                    url: BASE_URL + '/index.php?ajax=pagar_cuotas',
                    method: 'POST',
                    data: { cuotas: cuotasSeleccionadas, metodo: metodo },
                    dataType: 'json',
                    success: function(resp) {
                        if (resp && resp.success) {
                            alert('Pago registrado correctamente.');
                            $('#modalCuotas').modal('hide');
                            location.reload();
                        } else {
                            alert('No se pudo registrar el pago.');
                        }
                    },
                    error: function() {
                        alert('Error al registrar el pago.');
                    }
                });
            });
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

            // --- JS para manejo dinámico del modal de cuotas ---
            let cuotasSeleccionadas = [];
            let cuotasData = [];
            let monedaCuota = '<?php echo isset($empresa['moneda']) ? $empresa['moneda'] : 'S/.'; ?>';

            window.cargarCuotas = function(id_pago, paciente) {
                // Mostrar nombre del paciente
                $('#cuotasPaciente').text(paciente);
                // Limpiar selección y contenido
                cuotasSeleccionadas = [];
                cuotasData = [];
                $('#totalCuotasSeleccionadas').text(monedaCuota + ' 0.00');
                $('#metodoPagoCuota').val('');
                // Mostrar spinner
                $('#cuotasContenido').html('<div class="text-center text-secondary py-5"><i class="fas fa-spinner fa-spin fa-2x"></i><br> Cargando cuotas...</div>');
                // AJAX para obtener cuotas
                $.ajax({
                    url: BASE_URL + '/index.php?ajax=cuotas_cita',
                    method: 'POST',
                    data: { id_pago: id_pago },
                    dataType: 'json',
                    success: function(resp) {
                        if (resp && resp.length > 0) {
                            cuotasData = resp;
                            let html = '<div class="table-responsive"><table class="table table-bordered align-middle mb-0"><thead class="table-light"><tr><th></th><th>#</th><th>Fecha</th><th>Monto</th><th>Estado</th></tr></thead><tbody>';
                            resp.forEach(function(cuota, idx) {
                                let checked = cuota.pagada == 1 ? 'disabled checked' : '';
                                let estado = cuota.pagada == 1 ? '<span class="badge bg-success">Pagada</span>' : '<span class="badge bg-warning text-dark">Pendiente</span>';
                                html += '<tr>' +
                                    '<td><input type="checkbox" class="form-check-input cuota-checkbox" data-idx="' + idx + '" value="' + cuota.id_cuota + '" ' + checked + '></td>' +
                                    '<td>' + cuota.numero + '</td>' +
                                    '<td>' + cuota.fecha_vencimiento + '</td>' +
                                    '<td>' + monedaCuota + ' ' + parseFloat(cuota.monto).toFixed(2) + '</td>' +
                                    '<td>' + estado + '</td>' +
                                '</tr>';
                            });
                            html += '</tbody></table></div>';
                            $('#cuotasContenido').html(html);
                            // Evento para checkboxes
                            $('.cuota-checkbox').on('change', function() {
                                actualizarTotalCuotas();
                            });
                        } else {
                            $('#cuotasContenido').html('<div class=\"alert alert-warning\">No hay cuotas registradas para este pago.</div>');
                        }
                    },
                    error: function() {
                        $('#cuotasContenido').html('<div class=\"alert alert-danger\">Error al cargar cuotas.</div>');
                    }
                });
            }

            function actualizarTotalCuotas() {
                let total = 0;
                cuotasSeleccionadas = [];
                $('.cuota-checkbox:checked:not(:disabled)').each(function() {
                    let idx = $(this).data('idx');
                    let cuota = cuotasData[idx];
                    if (cuota && cuota.pagada != 1) {
                        total += parseFloat(cuota.monto);
                        cuotasSeleccionadas.push(cuota.id_cuota);
                    }
                });
                $('#totalCuotasSeleccionadas').text(monedaCuota + ' ' + total.toFixed(2));
            }

            $('#btnPagarCuotas').on('click', function() {
                if (cuotasSeleccionadas.length === 0) {
                    alert('Selecciona al menos una cuota a pagar.');
                    return;
                }
                let metodo = $('#metodoPagoCuota').val();
                if (!metodo) {
                    alert('Selecciona el método de pago.');
                    return;
                }
                // AJAX para registrar pago
                $.ajax({
                    url: BASE_URL + '/index.php?ajax=pagar_cuotas',
                    method: 'POST',
                    data: { cuotas: cuotasSeleccionadas, metodo: metodo },
                    dataType: 'json',
                    success: function(resp) {
                        if (resp && resp.success) {
                            alert('Pago registrado correctamente.');
                            $('#modalCuotas').modal('hide');
                            // Opcional: recargar la página o actualizar la fila
                            location.reload();
                        } else {
                            alert('No se pudo registrar el pago.');
                        }
                    },
                    error: function() {
                        alert('Error al registrar el pago.');
                    }
                });
            });
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
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-invoice-dollar me-2"></i>Detalle de nota de pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" style="background:#f7fbff;">
                <div class="row g-3">
                    <div class="col-12 col-lg-8">
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3">
                                <i class="fas fa-user-circle fa-3x text-primary"></i>
                            </div>
                            <div>
                                <div class="fw-bold fs-5" id="detalle_paciente">Paciente: ...</div>
                                <div class="small text-muted">Nota de pago: <span id="detalle_nota"></span> | Fecha: <span id="detalle_fecha"></span></div>
                            </div>
                            <div class="ms-auto">
                                <span class="badge bg-success" id="detalle_estado">Pagada</span>
                            </div>
                        </div>
                        <div class="row text-center mb-4">
                            <div class="col">
                                <div class="fw-bold text-primary" style="font-size:1.3rem">Total de la nota</div>
                                <div class="fs-4 fw-bold" id="detalle_total_nota">S/. 0.00</div>
                            </div>
                            <div class="col">
                                <div class="fw-bold text-primary" style="font-size:1.3rem">Total pagado</div>
                                <div class="fs-4 fw-bold" id="detalle_total_pagado">S/. 0.00</div>
                            </div>
                            <div class="col">
                                <div class="fw-bold text-primary" style="font-size:1.3rem">Cuotas</div>
                                <div class="fs-4 fw-bold" id="detalle_cuotas">0 de 0</div>
                            </div>
                            <div class="col">
                                <div class="fw-bold text-primary" style="font-size:1.3rem">Especialista</div>
                                <div class="fs-5" id="detalle_especialista">-</div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="fw-bold mb-2 text-primary">2. Conceptos incluidos en la nota</div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle mb-0" id="tabla_conceptos">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Concepto</th>
                                            <th class="text-center">Cantidad</th>
                                            <th class="text-end">Precio unitario</th>
                                            <th class="text-end">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- JS: conceptos -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3" class="text-end">Total</th>
                                            <th class="text-end" id="conceptos_total">S/. 0.00</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="fw-bold mb-2 text-primary">3. Historial de pagos realizados</div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle mb-0" id="tabla_pagos">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Pago</th>
                                            <th>Fecha</th>
                                            <th>Método de pago</th>
                                            <th class="text-end">Monto</th>
                                            <th>Usuario</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- JS: pagos -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3" class="text-end">Total pagado</th>
                                            <th class="text-end" id="pagos_total">S/. 0.00</th>
                                            <th colspan="2"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="fw-bold mb-2 text-primary">4. Observaciones</div>
                            <div class="bg-light p-3 rounded" id="detalle_observaciones">-</div>
                        </div>
                        <div class="alert alert-info" id="detalle_liquidada">
                            <i class="fas fa-info-circle me-2"></i>Nota completamente liquidada
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="card mb-3">
                            <div class="card-body text-center">
                                <?php if(!empty($empresa['logo'])): ?>
                                    <img src="<?php echo BASE_URL; ?>/uploads/<?php echo $empresa['logo']; ?>" style="max-height: 70px; margin-bottom: 10px;">
                                <?php endif; ?>
                                <h5 class="fw-bold mb-0"><?php echo $empresa['nombre_clinica']; ?></h5>
                                <div class="small text-muted"><?php echo $empresa['direccion']; ?></div>
                                <div class="small text-muted">Tel: <?php echo $empresa['telefono']; ?></div>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-primary" onclick="imprimirTicket()"><i class="fas fa-print me-2"></i>Imprimir</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
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


    // Cargar detalle de ticket en el nuevo modal
    function cargarTicket(id_cita) {
        // Guardar id en el modal para impresión
        $('#modalTicket').data('id_cita', id_cita);
        // Limpiar placeholders
        $('#detalle_paciente').text('Paciente: ...');
        $('#detalle_nota').text('');
        $('#detalle_fecha').text('');
        $('#detalle_estado').text('');
        $('#detalle_total_nota').text('...');
        $('#detalle_total_pagado').text('...');
        $('#detalle_cuotas').text('...');
        $('#detalle_especialista').text('-');
        $('#tabla_conceptos tbody').html('<tr><td colspan="4" class="text-center">Cargando...</td></tr>');
        $('#conceptos_total').text('...');
        $('#tabla_pagos tbody').html('<tr><td colspan="6" class="text-center">Cargando...</td></tr>');
        $('#pagos_total').text('...');
        $('#detalle_observaciones').text('-');
        $('#detalle_liquidada').show();

        $.get(BASE_URL + '/index.php?ajax=detalle_pago', { id_cita: id_cita }, function(resp) {
            if (!resp || !resp.success) {
                $('#tabla_conceptos tbody').html('<tr><td colspan="4" class="text-center">No hay datos</td></tr>');
                $('#tabla_pagos tbody').html('<tr><td colspan="6" class="text-center">No hay datos</td></tr>');
                return;
            }
            var d = resp.data;
            var moneda = d.moneda || 'S/.';
            // Cabecera
            $('#detalle_paciente').text('Paciente: ' + d.paciente);
            $('#detalle_nota').text(d.nro_nota);
            $('#detalle_fecha').text(d.fecha);
           // $('#detalle_estado').text(d.estado_txt).removeClass().addClass('badge').addClass(d.estado_badge);
            $('#detalle_total_nota').text(moneda + ' ' + d.total_nota);
            $('#detalle_total_pagado').text(moneda + ' ' + d.total_pagado);
            $('#detalle_cuotas').text(d.cuotas);
            $('#detalle_especialista').text(d.especialista);
            // Conceptos
            var conceptos = d.conceptos || [];
            var htmlConceptos = '';
            var totalConceptos = 0;
            conceptos.forEach(function(c) {
                htmlConceptos += `<tr><td>${c.descripcion}</td><td class="text-center">${c.cantidad}</td><td class="text-end">${moneda} ${parseFloat(c.precio).toFixed(2)}</td><td class="text-end">${moneda} ${parseFloat(c.total).toFixed(2)}</td></tr>`;
                totalConceptos += parseFloat(c.total);
            });
            $('#tabla_conceptos tbody').html(htmlConceptos || '<tr><td colspan="4" class="text-center">Sin conceptos</td></tr>');
            $('#conceptos_total').text(moneda + ' ' + totalConceptos.toFixed(2));
            // Pagos
            var pagos = d.pagos || [];
            var htmlPagos = '';
            var totalPagos = 0;
            pagos.forEach(function(p, idx) {
                htmlPagos += `<tr><td>Pago #${idx+1}</td><td>${p.fecha}</td><td>${p.metodo}</td><td class="text-end">${moneda} ${parseFloat(p.monto).toFixed(2)}</td><td>${p.usuario}</td><td><span class="badge bg-success">Pagado</span></td></tr>`;
                totalPagos += parseFloat(p.monto);
            });
            $('#tabla_pagos tbody').html(htmlPagos || '<tr><td colspan="6" class="text-center">Sin pagos</td></tr>');
            $('#pagos_total').text(moneda + ' ' + totalPagos.toFixed(2));
            // Observaciones
            $('#detalle_observaciones').text(d.observaciones || '-');
            // Estado liquidada
            if (d.liquidada) {
                $('#detalle_liquidada').show();
            } else {
                $('#detalle_liquidada').hide();
            }
        }).fail(function() {
            $('#tabla_conceptos tbody').html('<tr><td colspan="4" class="text-center">Error al cargar</td></tr>');
            $('#tabla_pagos tbody').html('<tr><td colspan="6" class="text-center">Error al cargar</td></tr>');
        });
    }

    // Imprimir ticket con dompdf en nueva pestaña
    function imprimirTicket() {
        var id_cita = $('#tick_id').text() || $('.modal.show #tick_id').text();
        if (!id_cita) {
            // Intentar obtener id guardado en data del modal
            id_cita = $('#modalTicket').data('id_cita');
        }
        if (!id_cita) {
            alert('No hay ticket cargado');
            return;
        }
        window.open(BASE_URL + '/citas/imprimirTicket?id_cita=' + encodeURIComponent(id_cita), '_blank');
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