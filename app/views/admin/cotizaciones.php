<?php require_once APP_ROOT . '/views/layouts/header.php'; ?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #6a11cb, #2575fc);">
            <div class="card-body d-flex align-items-center justify-content-between p-4">
                <div>
                    <h6 class="mb-0 text-white-50">Cotizaciones realizadas</h6>
                    <h2 class="mb-0 fw-bold" id="totalCotizaciones">0</h2>
                </div>
                <i class="fas fa-file-signature fa-3x opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<div class="card shadow border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <h5 class="mb-0 fw-bold text-secondary"><i class="fas fa-list me-2"></i> Listado de Cotizaciones</h5>
        <form action="<?php echo BASE_URL; ?>/cotizaciones" method="GET" class="d-flex gap-2">
            <input type="date" name="inicio" class="form-control form-control-sm" value="<?php echo $_GET['inicio'] ?? ''; ?>">
            <input type="date" name="fin" class="form-control form-control-sm" value="<?php echo $_GET['fin'] ?? ''; ?>">
            <button type="submit" class="btn btn-dark btn-sm px-3"><i class="fas fa-filter"></i> Filtrar</button>
        </form>
    </div>

    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tablaCotizaciones">
                <thead class="bg-light">
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Paciente</th>
                        <th>Estado</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total = 0;
                    if (!empty($cotizaciones) && is_array($cotizaciones)):
                        foreach ($cotizaciones as $row):
                            $total += $row['total'];
                    ?>
                        <tr>
                            <td class="fw-bold"><?php echo htmlspecialchars($row['folio'] ?? ''); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['created_at'] ?? $row['fecha'] ?? $row['id_cotizacion'])); ?> <small class="text-muted"><?php echo date('H:i', strtotime($row['created_at'] ?? $row['fecha'] ?? '00:00:00')); ?></small></td>
                            <td class="fw-bold text-dark"><?php
                                // intentar mostrar nombre de paciente si existe
                                if (!empty($row['id_paciente']) && class_exists('Paciente')) {
                                    try {
                                        $pm = new Paciente($db);
                                        $p = $pm->obtenerPorId($row['id_paciente']);
                                        echo $p ? $p['nombre'] : '-';
                                    } catch (Exception $e) { echo '-'; }
                                } else {
                                    echo '-';
                                }
                            ?></td>
                            <td>
                                <?php
                                    $cls = 'secondary';
                                    if ($row['estado'] === 'convertida') $cls = 'success';
                                    elseif ($row['estado'] === 'borrador') $cls = 'warning text-dark';
                                ?>
                                <span class="badge bg-<?php echo $cls; ?>"><?php echo ucfirst($row['estado']); ?></span>
                            </td>
                            <td class="text-end fw-bold text-success"><?php echo ($empresa['moneda'] ?? $row['moneda'] ?? 'S/.') . ' ' . number_format($row['total'], 2); ?></td>
                            <td class="text-center">
                                <a href="<?php echo BASE_URL; ?>/cotizaciones/imprimir?id=<?php echo $row['id_cotizacion']; ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-print"></i></a>
                                <a href="#" onclick="editarCotizacion(<?php echo $row['id_cotizacion']; ?>)" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></a>
                                <a href="#" onclick="convertirCotizacion(<?php echo $row['id_cotizacion']; ?>)" class="btn btn-sm btn-outline-success"><i class="fas fa-exchange-alt"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Base URL para llamadas AJAX desde esta vista
    var BASE_URL = '<?php echo BASE_URL; ?>';
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('totalCotizaciones').innerText = '<?php echo count($cotizaciones); ?>';
    });

    function editarCotizacion(id) {
        // Abrir modal de cotización en la vista de citas cargando la cotización via AJAX
        window.location.href = BASE_URL + '/citas?id_cotizacion=' + id;
    }

    function convertirCotizacion(id) {
        // mostrar modal de confirmación en lugar de confirm()
        var modalConfirm = document.getElementById('modalConfirmConvert');
        if (modalConfirm) modalConfirm.dataset.cotid = id;
        showBootstrapModalById('modalConfirmConvert');
    }
</script>

<script>
// Helper: muestra modal cuando bootstrap esté cargado
function showBootstrapModalById(id) {
    var el = document.getElementById(id);
    if (!el) {
        console.error('showBootstrapModalById: elemento no encontrado', id);
        return;
    }
    (function waitAndShow(retries){
        if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal === 'function') {
            try {
                var m = new bootstrap.Modal(el);
                m.show();
                console.log('showBootstrapModalById: modal mostrado', id);
            } catch(e) {
                console.error('showBootstrapModalById: error mostrando modal', e);
            }
        } else {
            if (!retries) retries = 0;
            if (retries > 50) { console.error('showBootstrapModalById: bootstrap no disponible tras varios reintentos'); return; }
            setTimeout(function(){ waitAndShow(retries + 1); }, 100);
        }
    })();
}
</script>


<!-- ModalCobrar (idéntico al de citas.php) -->
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
// variables globales para que handlers fuera del IIFE puedan acceder
var serviciosProductos = [];
var cuotas = [];
// --- JS para manejo dinámico del modalCobrar ---
// Espera a que jQuery esté disponible y luego ejecuta la inicialización
function waitForjQuery(cb, retries) {
    retries = retries || 0;
    if (typeof window.$ !== 'undefined' || typeof window.jQuery !== 'undefined') { try { cb(); } catch(e){ console.error('initCotizaciones error', e); } return; }
    if (retries > 50) { console.error('jQuery no disponible para inicializar cotizaciones'); return; }
    setTimeout(function(){ waitForjQuery(cb, retries + 1); }, 100);
}

function initCotizaciones() {
    // Evitar submit por Enter en el modal de cobro
    $('#formCobro').on('keydown', function(e) {
        if (e.key === 'Enter' && e.target.type !== 'textarea') {
            e.preventDefault();
            return false;
        }
    });
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
    var $tbody = $('#tablaServiciosProductos tbody');
    if (!$tbody || $tbody.length === 0) { console.error('actualizarTablaServiciosProductos: tbody no encontrado'); return; }
    var subtotal = 0;
    var html = '';
    serviciosProductos.forEach(function(item, idx) {
        var cant = parseInt(item.cantidad) || 0;
        var precio = parseFloat(item.precio) || 0;
        var total = cant * precio;
        subtotal += total;
        var descripcion = (item.descripcion !== undefined && item.descripcion !== null) ? String(item.descripcion) : '';
        // Log cada fila para depuración
        console.log('fila serviciosProductos:', idx, descripcion, cant, precio, total);
        html += '<tr>' +
                '<td>' + descripcion + '</td>' +
                '<td class="text-center">' + cant + '</td>' +
                '<td class="text-end">$' + precio.toFixed(2) + '</td>' +
                '<td class="text-end">$' + total.toFixed(2) + '</td>' +
                '<td><button type="button" class="btn btn-sm btn-danger" onclick="eliminarServicioProducto(' + idx + ')"><i class="fas fa-trash"></i></button></td>' +
            '</tr>';
    });
    // Reemplazar el contenido de tbody de una sola vez
    $tbody.html(html);
    console.log('actualizarTablaServiciosProductos: filas renderizadas:', serviciosProductos.length);
    $('#resumenSubtotal').text(subtotal.toFixed(2));
    var descuento = parseFloat($('#inputDescuento').val()) || 0;
    var totalFinal = subtotal - descuento;
    $('#resumenTotal').text(totalFinal.toFixed(2));
    $('#inputMontoTotal').val(totalFinal.toFixed(2));
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
    if (!descripcion || precio < 0 || cantidad <= 0) return;
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
                        // Si faltan descripciones, intentar resolverlas desde los selects disponibles
                        serviciosProductos.forEach(function(item) {
                            if ((!item.descripcion || item.descripcion.trim() === '') && item.referencia) {
                                var refId = item.referencia;
                                // Normalizar a número si viene como string con prefijo
                                if (typeof refId === 'string') {
                                    var m = refId.match(/(\d+)$/);
                                    if (m) refId = m[1];
                                }
                                var selectorVal = '';
                                if (String(item.tipo).toLowerCase().indexOf('serv') !== -1) selectorVal = 'servicio-' + refId;
                                else if (String(item.tipo).toLowerCase().indexOf('prod') !== -1) selectorVal = 'producto-' + refId;
                                if (selectorVal) {
                                    var opt = document.querySelector('#selectServicioProducto option[value="' + selectorVal + '"]') || document.querySelector('#selectCotizarProducto option[value="' + selectorVal + '"]');
                                    if (opt) {
                                        var nombre = opt.getAttribute('data-nombre') || opt.getAttribute('data-name') || opt.textContent || '';
                                        if (nombre) item.descripcion = nombre;
                                    }
                                }
                            }
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
                    showBootstrapModalById('modalCobrar');
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

}

// Iniciar cuando jQuery esté listo
waitForjQuery(initCotizaciones);

// Exponer funciones clave al ámbito global por si otros handlers las llaman antes
try {
    if (typeof window.actualizarTablaServiciosProductos === 'undefined' && typeof actualizarTablaServiciosProductos === 'function') {
        window.actualizarTablaServiciosProductos = actualizarTablaServiciosProductos;
    }
} catch(e) { /* noop */ }

try {
    if (typeof window.eliminarServicioProducto === 'undefined' && typeof eliminarServicioProducto === 'function') {
        window.eliminarServicioProducto = eliminarServicioProducto;
    }
} catch(e) { /* noop */ }

// --- CARGAR CUOTAS EN MODAL ---
// Recibe id_pago en vez de id_cita

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
                if (res.every(function(c){ return c.pagada === true || c.pagada === '1' || c.pagada === 1; })) {
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


<!-- Modal de confirmación para Convertir Cotización -->
<div class="modal fade" id="modalConfirmConvert" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title">Confirmar conversión</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">¿Deseas abrir esta cotización en el registro de cobro y precargar sus datos?</p>
        <small class="text-muted d-block mt-2">Podrás revisar los ítems y confirmar el primer pago.</small>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" id="confirmConvertAccept" class="btn btn-primary">Aceptar</button>
      </div>
    </div>
  </div>
</div>

<script>
// Handler del botón Aceptar en modal de confirmación (mejorado con logs y fallback)
// Función que maneja la solicitud de preview y muestra el modal (usable sin jQuery)
window._cot_confirm_in_progress = false;
// Renderiza las filas directamente desde la cotización recibida y sincroniza `serviciosProductos`
window.renderDetallesFromCot = function(cot) {
    serviciosProductos = [];
    var html = '';
    if (Array.isArray(cot.detalles)) {
        cot.detalles.forEach(function(d, idx) {
            var tipo = d.tipo || 'Item';
            var referencia = d.id_referencia || d.referencia || null;
            var descripcion = d.descripcion || '';
            var cantidad = parseInt(d.cantidad) || 1;
            var precio = parseFloat(d.precio) || 0;
            var total = parseFloat(d.total) || (cantidad * precio);
            serviciosProductos.push({ tipo: tipo, referencia: referencia, descripcion: descripcion, cantidad: cantidad, precio: precio, total: total });
            html += '<tr>' +
                    '<td>' + (descripcion || '') + '</td>' +
                    '<td class="text-center">' + cantidad + '</td>' +
                    '<td class="text-end">$' + precio.toFixed(2) + '</td>' +
                    '<td class="text-end">$' + total.toFixed(2) + '</td>' +
                    '<td><button type="button" class="btn btn-sm btn-danger" onclick="eliminarServicioProducto(' + idx + ')"><i class="fas fa-trash"></i></button></td>' +
                '</tr>';
        });
    }
    // Poner HTML directamente en la tabla
    try {
        var $tbody = $('#tablaServiciosProductos tbody');
        if ($tbody && $tbody.length) $tbody.html(html);
        else document.querySelector('#tablaServiciosProductos tbody').innerHTML = html;
    } catch(e) {
        console.warn('renderDetallesFromCot: fallo insertando html', e);
    }
    // actualizar resúmenes
    try { $('#resumenSubtotal').text((parseFloat(cot.subtotal)||0).toFixed(2)); } catch(e) { document.getElementById('resumenSubtotal').textContent = (parseFloat(cot.subtotal)||0).toFixed(2); }
    try { $('#inputDescuento').val(parseFloat(cot.descuento_valor) || 0); } catch(e) { var el = document.getElementById('inputDescuento'); if (el) el.value = parseFloat(cot.descuento_valor) || 0; }
    var totalVal = parseFloat(cot.total) || 0;
    try { $('#resumenTotal').text(totalVal.toFixed(2)); $('#inputMontoTotal').val(totalVal.toFixed(2)); } catch(e) { document.getElementById('resumenTotal').textContent = totalVal.toFixed(2); var elm = document.getElementById('inputMontoTotal'); if (elm) elm.value = totalVal.toFixed(2); }
    try { $('#detalle_json').val(JSON.stringify(serviciosProductos)); } catch(e) { var dj = document.getElementById('detalle_json'); if (dj) dj.value = JSON.stringify(serviciosProductos); }
    console.log('renderDetallesFromCot: serviciosProductos sincronizados', serviciosProductos.length);
};
window.handleConfirmConvertRequest = function(id) {
    if (window._cot_confirm_in_progress) {
        console.log('handleConfirmConvertRequest: en progreso, ignorando llamada duplicada');
        return;
    }
    window._cot_confirm_in_progress = true;
    console.log('handleConfirmConvertRequest: solicitar preview para cotizacion', id);
    // cerrar modalConfirmConvert si existe
    try {
        var modalEl = document.getElementById('modalConfirmConvert');
        if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var inst = bootstrap.Modal.getInstance(modalEl);
            if (inst) inst.hide();
        }
    } catch(e) { console.warn('no se pudo cerrar modalConfirmConvert', e); }

    // preparar cuerpo form-urlencoded
    var body = 'id_cotizacion=' + encodeURIComponent(id) + '&preview=1';
    fetch(BASE_URL + '/cotizaciones/convertir', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body }).then(function(r){
        return r.text().then(function(text){
            try { return JSON.parse(text); } catch(e) { return { success: false, raw: text }; }
        });
    }).then(function(resp){
        console.log('resp convertir preview (fetch):', resp);
        if (resp && resp.success && resp.cotizacion) {
            var cot = resp.cotizacion;
            var pacienteText = '-';
            if (cot.id_paciente) {
                var opt = document.querySelector('.select2-paciente option[value="' + cot.id_paciente + '"]');
                if (opt) pacienteText = opt.textContent; else pacienteText = 'Paciente #' + cot.id_paciente;
            }
            // Renderizar detalles directamente desde la respuesta de preview (inline para evitar problemas de scope)
            serviciosProductos = [];
            var htmlLocal = '';
            if (Array.isArray(cot.detalles)) {
                cot.detalles.forEach(function(d, idx) {
                    var tipo = d.tipo || 'Item';
                    var referencia = d.id_referencia || d.referencia || null;
                    var descripcion = d.descripcion || '';
                    var cantidad = parseInt(d.cantidad) || 1;
                    var precio = parseFloat(d.precio) || 0;
                    var total = parseFloat(d.total) || (cantidad * precio);
                    serviciosProductos.push({ tipo: tipo, referencia: referencia, descripcion: descripcion, cantidad: cantidad, precio: precio, total: total });
                    htmlLocal += '<tr>' +
                            '<td>' + (descripcion || '') + '</td>' +
                            '<td class="text-center">' + cantidad + '</td>' +
                            '<td class="text-end">$' + precio.toFixed(2) + '</td>' +
                            '<td class="text-end">$' + total.toFixed(2) + '</td>' +
                            '<td><button type="button" class="btn btn-sm btn-danger" onclick="eliminarServicioProducto(' + idx + ')"><i class="fas fa-trash"></i></button></td>' +
                        '</tr>';
                });
            }
            try {
                var $tbodyLocal = $('#tablaServiciosProductos tbody');
                if ($tbodyLocal && $tbodyLocal.length) $tbodyLocal.html(htmlLocal);
                else document.querySelector('#tablaServiciosProductos tbody').innerHTML = htmlLocal;
            } catch(e) { console.warn('render inline: fallo insertando html', e); }
            try { $('#resumenSubtotal').text((parseFloat(cot.subtotal)||0).toFixed(2)); } catch(e) { document.getElementById('resumenSubtotal').textContent = (parseFloat(cot.subtotal)||0).toFixed(2); }
            try { $('#inputDescuento').val(parseFloat(cot.descuento_valor) || 0); } catch(e) { var el = document.getElementById('inputDescuento'); if (el) el.value = parseFloat(cot.descuento_valor) || 0; }
            var total = parseFloat(cot.total) || 0;
            try { $('#resumenTotal').text(total.toFixed(2)); $('#inputMontoTotal').val(total.toFixed(2)); } catch(e) { document.getElementById('resumenTotal').textContent = total.toFixed(2); var elm = document.getElementById('inputMontoTotal'); if (elm) elm.value = total.toFixed(2); }
            try { $('#detalle_json').val(JSON.stringify(serviciosProductos)); } catch(e) { var dj = document.getElementById('detalle_json'); if (dj) dj.value = JSON.stringify(serviciosProductos); }
            var cotIdVal = cot.id_cotizacion || cot.id || id;
            var elCobroId = document.getElementById('cobro_id'); if (elCobroId) elCobroId.value = '';
            var elCobroCot = document.getElementById('cobro_id_cotizacion'); if (elCobroCot) elCobroCot.value = cotIdVal;
            var elPaciente = document.getElementById('cobro_paciente'); if (elPaciente) elPaciente.textContent = pacienteText;
            var elFolio = document.getElementById('cobro_folio'); if (elFolio) elFolio.textContent = cot.folio ? ('Folio: ' + cot.folio) : ('Cot.' + cotIdVal);
            var total = parseFloat(cot.total) || 0;
            var elSubtotal = document.getElementById('resumenSubtotal'); if (elSubtotal) elSubtotal.textContent = (parseFloat(cot.subtotal)||0).toFixed(2);
            var elDesc = document.getElementById('inputDescuento'); if (elDesc) elDesc.value = parseFloat(cot.descuento_valor) || 0;
            var elTotal = document.getElementById('resumenTotal'); if (elTotal) elTotal.textContent = total.toFixed(2);
            var elInputMonto = document.getElementById('inputMontoTotal'); if (elInputMonto) elInputMonto.value = total.toFixed(2);
            var elDetalleJson = document.getElementById('detalle_json'); if (elDetalleJson) elDetalleJson.value = JSON.stringify(serviciosProductos);
            // Mostrar modal inmediatamente para reducir latencia percibida
            var el = document.getElementById('modalCobrar');
            if (!el) { console.error('modalCobrar no encontrado en el DOM'); alert('Error interno: modal de cobro no disponible.'); window._cot_confirm_in_progress = false; return; }
            try { showBootstrapModalById('modalCobrar'); } catch(e) { console.warn('Error mostrando modal inmediatamente', e); }

            // Renderizar tabla inmediatamente (no bloquear la apertura del modal)
            try {
                var $tbodyLocal = $('#tablaServiciosProductos tbody');
                var htmlLocal2 = '';
                serviciosProductos.forEach(function(item, idx) {
                    var descripcion = item.descripcion || '';
                    var cantidad = parseInt(item.cantidad) || 0;
                    var precio = parseFloat(item.precio) || 0;
                    var totalRow = cantidad * precio;
                    htmlLocal2 += '<tr>' +
                        '<td>' + descripcion + '</td>' +
                        '<td class="text-center">' + cantidad + '</td>' +
                        '<td class="text-end">$' + precio.toFixed(2) + '</td>' +
                        '<td class="text-end">$' + totalRow.toFixed(2) + '</td>' +
                        '<td><button type="button" class="btn btn-sm btn-danger" onclick="eliminarServicioProducto(' + idx + ')"><i class="fas fa-trash"></i></button></td>' +
                    '</tr>';
                });
                if ($tbodyLocal && $tbodyLocal.length) $tbodyLocal.html(htmlLocal2);
                else document.querySelector('#tablaServiciosProductos tbody').innerHTML = htmlLocal2;
            } catch(e) { console.warn('Error renderizando tabla tras mostrar modal', e); }
        } else {
            console.error('Respuesta inválida al pedir preview:', resp);
            alert('No se pudo cargar la cotización (preview).');
        }
    }).catch(function(err){
        console.error('fetch error:', err);
        alert('Error al solicitar la cotización. Revisa la consola para más detalles.');
    }).finally(function(){
        setTimeout(function(){ window._cot_confirm_in_progress = false; }, 300);
    });
};

// Intentar atar el handler con jQuery si está disponible; si no, el fallback JS lo manejará
try {
    if (typeof $ !== 'undefined') {
        $('#confirmConvertAccept').off('click').on('click', function() {
            var id = $('#modalConfirmConvert').data('cotid');
            window.handleConfirmConvertRequest(id);
        });
    }
} catch(e) { console.warn('No fue posible atar handler jQuery, se usará fallback JS', e); }

// Fallback: handler en JS puro por si jQuery no está listo
var btnConfirm = document.getElementById('confirmConvertAccept');
if (btnConfirm) {
    btnConfirm.addEventListener('click', function(e) {
        // Si jQuery ya ató el evento, éste también se ejecutará; el lock evita duplicados
        var id = document.getElementById('modalConfirmConvert') ? document.getElementById('modalConfirmConvert').dataset.cotid : null;
        if (!id) return;
        window.handleConfirmConvertRequest(id);
    });
}
</script>

<?php require_once APP_ROOT . '/views/layouts/footer.php'; ?>
