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
                                            // Mostrar como enlace que abre el modal de registro de pago de cuotas
                                            printf('<a href="#" class="badge bg-light text-dark me-2" data-bs-toggle="modal" data-bs-target="#modalCuotas" onclick="cargarCuotas(\'%s\', \'%s\')">%s/%s</a>', addslashes((string)($row['id_pago'] ?? '')), addslashes((string)($row['paciente'] ?? '')), $enRango, $row['total_cuotas']);
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
                                    <?php if (!empty($row['id_cita'])): ?>
                                        <button class="btn btn-sm btn-outline-primary" onclick="mostrarDetalle(<?php echo $row['id_cita']; ?>)" title="Detalle">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-primary" onclick="cargarCuotas('<?php echo addslashes((string)($row['id_pago'] ?? '')); ?>', '<?php echo addslashes((string)($row['paciente'] ?? '')); ?>')" title="Ver Cuotas">
                                            <i class="fas fa-list-ol"></i>
                                        </button>
                                    <?php endif; ?>
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



<?php require_once APP_ROOT . '/views/layouts/footer.php'; ?>