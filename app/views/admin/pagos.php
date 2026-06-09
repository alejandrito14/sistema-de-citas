<?php require_once APP_ROOT . '/views/layouts/header.php'; ?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #2c3e50, #4ca1af);">
            <div class="card-body d-flex align-items-center justify-content-between p-4">
                <div>
                        <h6 class="mb-0 text-white-50">Total Recaudado (Selección)</h6>
                        <h2 class="mb-0 fw-bold" id="totalMonto"><?php echo ($empresa['moneda'] ?? 'S/.') . ' ' . number_format($totalRecaudado ?? 0, 2); ?></h2>
                </div>
                <i class="fas fa-cash-register fa-3x opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<div class="card shadow border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <h5 class="mb-0 fw-bold text-secondary"><i class="fas fa-file-invoice-dollar me-2"></i> Historial de Pagos</h5>
        
        <form action="<?php echo BASE_URL; ?>/pagos" method="GET" class="d-flex gap-2">
            <input type="date" name="inicio" class="form-control form-control-sm" value="<?php echo $fechaInicio; ?>" required>
            <input type="date" name="fin" class="form-control form-control-sm" value="<?php echo $fechaFin; ?>" required>
            <button type="submit" class="btn btn-dark btn-sm px-3"><i class="fas fa-filter"></i> Filtrar</button>
        </form>
    </div>
    
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tablaPro">
                <thead class="bg-light">
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Paciente</th>
                        <th>Concepto</th>
                        <th>Cuotas</th>
                        <th>Método</th>
                        <th>Obs.</th>
                        <th class="text-end">Monto</th>
                        <th class="text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if(!empty($pagos) && is_array($pagos)):
                        foreach($pagos as $row):
                    ?>
                        <tr>
                            <td>
                                <?php echo date('d/m/Y', strtotime($row['fecha_pago'])); ?> 
                                <small class="text-muted"><?php echo date('H:i', strtotime($row['fecha_pago'])); ?></small>
                            </td>
                            <td class="fw-bold text-dark"><?php echo $row['paciente'] ?? '-'; ?></td>
                            <td><?php echo !empty($row['conceptos']) ? htmlspecialchars($row['conceptos']) : '—'; ?></td>
                            <td>
                                <?php 
                                        if (!empty($row['total_cuotas']) && $row['total_cuotas'] > 0) {
                                            $enRango = isset($row['cuotas_pagadas_en_rango']) ? (int)$row['cuotas_pagadas_en_rango'] : 0;
                                            $pagadasTot = isset($row['cuotas_pagadas_total']) ? (int)$row['cuotas_pagadas_total'] : 0;
                                            echo $enRango . ' / ' . $row['total_cuotas'];
                                            if ($pagadasTot == $row['total_cuotas']) echo ' <span class="badge bg-success ms-2">Todas pagadas</span>';
                                        } else {
                                            echo '<span class="text-muted">Sin cuotas</span>';
                                        }
                                ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?php echo $row['metodo_pago']; ?>
                                </span>
                            </td>
                            <td class="small text-muted fst-italic"><?php echo $row['observaciones']; ?></td>
                            <td class="text-end fw-bold text-success">
                                <?php
                                    // Mostrar monto: si el pago padre está en el rango mostrar p.monto, sino mostrar la suma de cuotas pagadas en el rango
                                    $montoMostrar = 0.0;
                                    $fechaPago = isset($row['fecha_pago']) ? date('Y-m-d', strtotime($row['fecha_pago'])) : null;
                                    if (!empty($fechaPago) && isset($fechaInicio) && isset($fechaFin) && $fechaPago >= $fechaInicio && $fechaPago <= $fechaFin) {
                                        $montoMostrar = isset($row['monto']) ? floatval($row['monto']) : 0.0;
                                    } else {
                                        $montoMostrar = isset($row['monto_cuotas_en_rango']) ? floatval($row['monto_cuotas_en_rango']) : 0.0;
                                    }
                                    echo ($empresa['moneda'] ?? 'S/.') . ' ' . number_format($montoMostrar, 2);
                                ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary" onclick="mostrarDetalle(<?php echo $row['id_cita'] ?? 'null'; ?>)" title="Detalle">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

    <script>
    // Total ya calculado en PHP y mostrado en la tarjeta

    function confirmarEliminacion(id) {
        Swal.fire({
            title: '¿Anular Pago?',
            text: "El registro de dinero será eliminado. Esto afecta el reporte de caja.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, anular',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "<?php echo BASE_URL; ?>/pagos/eliminar?id=" + id;
            }
        })
    }
</script>

<?php require_once APP_ROOT . '/views/layouts/footer.php'; ?>

<!-- Modal Detalle Pago -->
<div class="modal fade" id="modalDetallePago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detallePagoContent">
                    <div class="text-center py-3" id="detalleLoading"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function mostrarDetalle(id_cita) {
        if (!id_cita) return alert('No hay cita asociada.');
        var modal = new bootstrap.Modal(document.getElementById('modalDetallePago'));
        $('#detallePagoContent').html('<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>');
        modal.show();
        $.get(BASE_URL + '/index.php?ajax=detalle_pago', { id_cita: id_cita }, function(resp) {
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
}
</script>