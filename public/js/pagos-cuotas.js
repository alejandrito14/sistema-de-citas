(function(window, $){
    if (typeof $ === 'undefined') return console.warn('jQuery required for pagos-cuotas.js');

    var MONEDA = window.EMPRESA_MONEDA || 'S/.';
    var BASE = (typeof BASE_URL !== 'undefined') ? BASE_URL : (window.BASE_URL || '');

    window.cargarCuotas = function(id_pago, paciente) {
        var monedaCuota = MONEDA || 'S/.';
        var cuotasSeleccionadas = [];
        console.log('cargarCuotas called with', id_pago, paciente);
        $('#cuotasPaciente').text(paciente || '-');
        $('#detalleTotalSeleccionado').text(monedaCuota + ' 0.00');
        $('#metodoPagoCuota').val('');
        $('#cuotasContenido').html('<div class="text-center text-secondary py-5"><i class="fas fa-spinner fa-spin fa-2x"></i><br> Cargando cuotas...</div>');
        if (!id_pago) {
            $('#cuotasContenido').html('<div class="alert alert-warning">Falta id de pago para cargar cuotas.</div>');
            console.warn('cargarCuotas: id_pago vacío');
            return;
        }
        $.ajax({
            url: BASE + '/index.php?ajax=cuotas_cita',
            method: 'GET',
            data: { id_pago: id_pago },
            dataType: 'json',
            success: function(res) {
                console.log('cargarCuotas AJAX response:', res);
                if (!res) {
                    $('#cuotasContenido').html('<div class="alert alert-danger">Respuesta vacía del servidor.</div>');
                    return;
                }
                // Si el servidor devolvió error
                if (res.error) {
                    $('#cuotasContenido').html('<div class="alert alert-danger">Error: ' + (res.error || 'No especificado') + '</div>');
                    console.error('cargarCuotas server error:', res.error);
                    return;
                }
                // Aceptar tanto array como objeto con clave 'cuotas'
                var dataArray = res;
                if (!Array.isArray(dataArray) && Array.isArray(res.cuotas)) dataArray = res.cuotas;
                if (!dataArray || !Array.isArray(dataArray) || dataArray.length === 0) {
                    $('#cuotasContenido').html('<div class="alert alert-info">No hay cuotas registradas para este pago.</div>');
                    return;
                }
                var html = '';
                html += '<div class="table-responsive"><table class="table align-middle table-bordered mb-0">'
                    + '<thead><tr><th></th><th>Cuota</th><th>Vencimiento</th><th>Monto</th><th>Estado</th></tr></thead><tbody>';
                dataArray.forEach(function(cuota, idx) {
                    var checked = cuota.pagada ? 'disabled checked' : '';
                    var estado = cuota.pagada ? '<span class="badge bg-success">Pagada</span>' : '<span class="badge bg-warning text-dark">Pendiente</span>';
                    var montoTxt = monedaCuota + ' ' + (isNaN(parseFloat(cuota.monto)) ? '0.00' : parseFloat(cuota.monto).toFixed(2));
                    var venc = cuota.fecha_vencimiento ? cuota.fecha_vencimiento : (cuota.vencimiento || '');
                    html += '<tr>' +
                        '<td><input type="checkbox" class="form-check-input cuota-checkbox" data-idx="' + idx + '" value="' + cuota.id_cuota + '" ' + checked + '></td>' +
                        '<td>' + (cuota.numero_cuota || cuota.numero || (idx+1)) + ' de ' + dataArray.length + '</td>' +
                        '<td>' + venc + '</td>' +
                        '<td>' + montoTxt + '</td>' +
                        '<td>' + estado + '</td>' +
                        '</tr>';
                });
                html += '</tbody></table></div>';
                $('#cuotasContenido').html(html);

                $('.cuota-checkbox').on('change', function() {
                    actualizarTotalesCuotas(dataArray);
                });

                actualizarTotalesCuotas(dataArray);

                function actualizarTotalesCuotas(dataRes) {
                    var total = 0;
                    cuotasSeleccionadas = [];
                    $('.cuota-checkbox:checked:not(:disabled)').each(function() {
                        var idx = $(this).data('idx');
                        var cuota = dataRes[idx];
                        if (cuota && !cuota.pagada) {
                            total += parseFloat(cuota.monto);
                            cuotasSeleccionadas.push(cuota.id_cuota);
                        }
                    });
                    $('#detalleTotalSeleccionado').text(monedaCuota + ' ' + total.toFixed(2));
                    var descuento = parseFloat($('#inputDescuentoCuota').val()) || 0;
                    var totalPagar = total - descuento;
                    if (totalPagar < 0) totalPagar = 0;
                    $('#detalleTotalPagar').text(monedaCuota + ' ' + totalPagar.toFixed(2));
                    if (dataRes.every(function(c){ return c.pagada === true || c.pagada === '1' || c.pagada === 1; })) {
                        $('#msgSeleccionCuotas').html('Todas las cuotas ya fueron pagadas.');
                    } else {
                        $('#msgSeleccionCuotas').html(cuotasSeleccionadas.length > 0 ? 'Has seleccionado <b>'+cuotasSeleccionadas.length+'</b> cuota(s) por un total de <b>'+monedaCuota+' '+total.toFixed(2)+'</b>.' : 'Selecciona cuotas para pagar.');
                    }
                }

                // Acción del botón Pagar
                $('#btnPagarCuotas').off('click').on('click', function() {
                    if (cuotasSeleccionadas.length === 0) {
                        alert('Selecciona al menos una cuota a pagar.'); return;
                    }
                    var metodo = $('#metodoPagoCuota').val();
                    if (!metodo) { alert('Selecciona el método de pago.'); return; }
                    var formData = new FormData();
                    formData.append('cuotas', JSON.stringify(cuotasSeleccionadas));
                    formData.append('metodo', metodo);
                    formData.append('descuento', $('#inputDescuentoCuota').val() || 0);
                    formData.append('observaciones', $('#observacionesCuota').val() || '');
                    var file = $('#comprobanteTransferencia')[0] ? $('#comprobanteTransferencia')[0].files[0] : null;
                    if (file) formData.append('comprobante', file);
                    $.ajax({
                        url: BASE + '/index.php?ajax=pagar_cuotas',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function(resp) {
                                if (resp && resp.success) {
                                alert('Pago registrado correctamente.');
                                try { if (typeof window.hideModalById === 'function') hideModalById('modalCuotas'); else { var m = bootstrap.Modal.getInstance(document.getElementById('modalCuotas')); if (m) m.hide(); } } catch(e) { console.warn('no se pudo ocultar modal via API', e); }
                                location.reload();
                            } else {
                                alert('No se pudo registrar el pago.');
                            }
                        },
                        error: function() { alert('Error al registrar el pago.'); }
                    });
                });
            },
            error: function() {
                $('#cuotasContenido').html('<div class="alert alert-danger">No se pudieron cargar las cuotas.</div>');
            }
        });
    };

    window.mostrarDetalle = function(id_cita) {
        if (!id_cita) return alert('No hay cita asociada.');
        var modal = new bootstrap.Modal(document.getElementById('modalDetallePago'));
        $('#detallePagoContent').html('<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>');
        modal.show();
        $.get(BASE + '/index.php?ajax=detalle_pago', { id_cita: id_cita }, function(resp) {
            if (!resp || !resp.success) {
                $('#detallePagoContent').html('<div class="alert alert-danger">No se pudo cargar el detalle.</div>');
                return;
            }
            var d = resp.data;
            var html = '';
            html += '<div class="mb-2"><strong>Paciente:</strong> ' + (d.paciente || '-') + '</div>';
            html += '<div class="mb-2"><strong>Nota:</strong> ' + (d.nro_nota || '-') + ' <strong class="ms-3">Fecha:</strong> ' + (d.fecha || '-') + '</div>';
            html += '<div class="mb-2"><strong>Estado:</strong> <span class="badge ' + (d.estado_badge || 'bg-secondary') + '">' + (d.estado_txt || '') + '</span></div>';
            html += '<div class="row"><div class="col-md-6"><h6>Conceptos</h6>';
            if (d.conceptos && d.conceptos.length>0) {
                html += '<table class="table table-sm"><thead><tr><th>Desc</th><th class="text-end">Total</th></tr></thead><tbody>';
                d.conceptos.forEach(function(c){ html += '<tr><td>' + c.descripcion + '</td><td class="text-end">' + d.moneda + ' ' + parseFloat(c.total).toFixed(2) + '</td></tr>'; });
                html += '</tbody></table>';
            } else html += '<div class="text-muted">Sin conceptos registrados.</div>';
            html += '</div>';
            html += '<div class="col-md-6"><h6>Pagos / Cuotas</h6>';
            if (d.pagos && d.pagos.length>0) {
                html += '<table class="table table-sm"><thead><tr><th>Fecha</th><th>Método</th><th class="text-end">Monto</th></tr></thead><tbody>';
                d.pagos.forEach(function(p){ html += '<tr><td>' + p.fecha + '</td><td>' + p.metodo + '</td><td class="text-end">' + d.moneda + ' ' + parseFloat(p.monto).toFixed(2) + '</td></tr>'; });
                html += '</tbody></table>';
            } else html += '<div class="text-muted">No hay pagos registrados.</div>';
            html += '</div></div>';
            html += '<div class="mt-3"><strong>Total Nota:</strong> ' + d.moneda + ' ' + parseFloat(d.total_nota).toFixed(2) + ' <strong class="ms-3">Total Pagado:</strong> ' + d.moneda + ' ' + parseFloat(d.total_pagado).toFixed(2) + '</div>';
            $('#detallePagoContent').html(html);
        }, 'json').fail(function(){
            $('#detallePagoContent').html('<div class="alert alert-danger">Error de comunicación.</div>');
        });
    };

})(window, jQuery);

// Procesar llamadas encoladas (si las hubo) para `cargarCuotas`
(function(){
    try {
        if (window.__cargarCuotasQueue && Array.isArray(window.__cargarCuotasQueue) && window.__cargarCuotasQueue.length) {
            var q = window.__cargarCuotasQueue.splice(0);
            q.forEach(function(args){
                try { window.cargarCuotas.apply(null, args); } catch(e) { console.error('Error procesando cola cargarCuotas', e); }
            });
        }
    } catch(e) { console.error('Error al procesar queue cargarCuotas', e); }
})();
