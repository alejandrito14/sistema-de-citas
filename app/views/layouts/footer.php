</div> </div> </div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    // Toggle Sidebar
    document.getElementById("menu-toggle").addEventListener("click", function(e) {
        e.preventDefault();
        document.getElementById("sidebar").classList.toggle("toggled");
    });

    $(document).ready(function() {
        // DataTables
        $('#tablaPro').DataTable({
            responsive: true,
            dom: 'Bfrtip',
            buttons: [
                { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm' },
                { extend: 'pdfHtml5', text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm' },
                { extend: 'print', text: '<i class="fas fa-print"></i> Imprimir', className: 'btn btn-secondary btn-sm' }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
        });

        // Select2 para Modales
        const BASE_URL_JS = (typeof BASE_URL !== 'undefined') ? BASE_URL : '<?php echo BASE_URL; ?>';

        $('.select2-paciente').each(function() {
            const $el = $(this);
            const parentModal = $el.closest('.modal');
            $el.select2({
                theme: "bootstrap-5",
                dropdownParent: parentModal.length ? parentModal : $(document.body),
                width: '100%',
                ajax: {
                    url: BASE_URL_JS + '/index.php?ajax=buscar_pacientes',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return { q: params.term }; },
                    processResults: function(data) { return data; },
                    cache: true
                },
                minimumInputLength: 1,
                templateResult: function(item) { return item.text; },
                templateSelection: function(item) { return item.text || item.id ? item.text : ''; }
            });
        });

        $('.select2-medico').each(function() {
            const $el = $(this);
            const parentModal = $el.closest('.modal');
            $el.select2({
                theme: "bootstrap-5",
                dropdownParent: parentModal.length ? parentModal : $(document.body),
                width: '100%'
            });
        });
    });

    // Alertas
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    
    if(msg) {
        let title = 'Notificación';
        let text = 'Acción realizada';
        let icon = 'info';

        if(msg === 'creado') { title = '¡Éxito!'; text = 'Registro guardado correctamente.'; icon = 'success'; }
        else if(msg === 'actualizado') { title = '¡Actualizado!'; text = 'Los cambios se han guardado.'; icon = 'success'; }
        else if(msg === 'eliminado') { title = 'Eliminado'; text = 'El registro ha sido eliminado.'; icon = 'success'; }
        else if(msg === 'ocupado') { title = 'Agenda Llena'; text = 'Horario no disponible.'; icon = 'warning'; }
        else if(msg === 'pasado') { title = 'Fecha Inválida'; text = 'No puedes usar fechas pasadas.'; icon = 'error'; }
        else if(msg === 'fuera_horario') { title = 'Médico No Disponible'; text = 'El médico no atiende en ese horario.'; icon = 'warning'; }
        else if(msg === 'atendido') { title = 'Consulta Finalizada'; text = 'La atención se registró con éxito.'; icon = 'success'; }
        else if(msg === 'pagado') { title = '¡Pago Exitoso!'; text = 'El cobro se ha registrado.'; icon = 'success'; }
        else if(msg === 'subido') { title = 'Subido'; text = 'Archivo adjuntado.'; icon = 'success'; }
        else if(msg === 'error_permisos') { title = 'Acceso Denegado'; text = 'No tienes permisos para esto.'; icon = 'error'; }
        else if(msg === 'error') { title = 'Error'; text = 'Ocurrió un problema técnico.'; icon = 'error'; }

        Swal.fire({
            title: title, text: text, icon: icon, confirmButtonColor: '#0d6efd'
        }).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    }
</script>

<script>
    // Exponer moneda de la empresa para scripts externos
    window.EMPRESA_MONEDA = (typeof window.EMPRESA_MONEDA !== 'undefined') ? window.EMPRESA_MONEDA : '<?php echo isset($empresa["moneda"]) ? $empresa["moneda"] : "S/."; ?>';
    // Stub queue: si alguna vista llama a cargarCuotas antes de que el archivo JS cargue,
    // encolamos la llamada para procesarla cuando el script esté disponible.
    window.__cargarCuotasQueue = window.__cargarCuotasQueue || [];
    if (typeof window.cargarCuotas !== 'function') {
        window.cargarCuotas = function() { window.__cargarCuotasQueue.push(arguments); };
    }
    if (typeof window.mostrarDetalle !== 'function') {
        window.mostrarDetalle = function() { /* stub hasta que cargue el script */ };
    }
    // Helper para esconder modales de Bootstrap 5 de forma segura
    window.hideModalById = function(id) {
        var el = document.getElementById(id);
        if (!el) return;
        try {
            if (typeof bootstrap !== 'undefined' && bootstrap && typeof bootstrap.Modal === 'function') {
                var inst = bootstrap.Modal.getInstance(el);
                if (inst) { try { inst.hide(); return; } catch(e) { console.warn('hideModalById: instancia.hide() falló', e); } }
                try { var tmp = new bootstrap.Modal(el); tmp.hide(); return; } catch(e) { console.warn('hideModalById: new Modal(el) falló', e); }
            }
        } catch(e) { console.warn('hideModalById bootstrap error', e); }
        // Fallback manual: quitar clases y backdrop
        try {
            el.classList.remove('show');
            el.style.display = 'none';
            el.setAttribute('aria-hidden', 'true');
            el.removeAttribute('aria-modal');
            el.removeAttribute('role');
            document.body.classList.remove('modal-open');
            var back = document.querySelectorAll('.modal-backdrop');
            back.forEach(function(b){ b.parentNode && b.parentNode.removeChild(b); });
        } catch(e) { console.warn('hideModalById fallback error', e); }
    };
</script>
<script src="<?php echo BASE_URL; ?>/js/pagos-cuotas.js?v=1"></script>

<!-- Modal de Cuotas: Registrar Pago de Cuotas (centralizado) -->
<div class="modal fade" id="modalCuotas" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0" style="box-shadow:0 0 32px #00bfff55;">
            <div class="modal-header" style="background: linear-gradient(90deg,#1ec6ea,#00bfff); color:#fff;">
                <h5 class="modal-title fw-bold"><i class="fas fa-list-ol me-2"></i>Registrar Pago de Cuotas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" style="background:#fafdff;">
                <div class="alert border-0 d-flex align-items-center mb-4" style="background:#e6f7fb;">
                    <i class="fas fa-user fa-2x me-3" style="color:#0096c7;"></i>
                    <div>
                        <span class="fw-bold" style="font-size:1.1rem; color:#0096c7;">Paciente: <span id="cuotasPaciente"></span></span><br>
                        <span id="msgEncabezadoCuotas" style="color:#0096c7;">Selecciona las cuotas que deseas pagar y el método de pago.</span>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-md-7">
                        <div class="fw-bold mb-2" style="color:#0096c7;">1. Seleccionar cuotas a pagar</div>
                        <div id="cuotasContenido"><div class="text-center text-secondary py-5"><i class="fas fa-spinner fa-spin fa-2x"></i><br> Cargando cuotas...</div></div>
                    </div>
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
                            <div class="mt-2" id="comprobanteTransferenciaBox" style="display:none;"><label class="fw-bold mb-2">Comprobante de Transferencia</label><input type="file" id="comprobanteTransferencia" class="form-control" accept="image/*,application/pdf"></div>
                        </div>
                        <div class="mb-3 p-3 rounded" style="background:#f3fafd;border:1px solid #e0f0fa;">
                            <label class="fw-bold mb-2" style="color:#0096c7;">3. Detalle del pago</label>
                            <div class="mb-2 d-flex justify-content-between"><span>Total seleccionado</span> <span id="detalleTotalSeleccionado">S/. 0.00</span></div>
                            <div class="mb-2 d-flex justify-content-between align-items-center"><span>Descuento</span><input type="number" class="form-control form-control-sm d-inline-block ms-2" style="width:100px;vertical-align:middle;" id="inputDescuentoCuota" value="0" min="0" step="0.01" /></div>
                            <div class="mb-2 d-flex justify-content-between fs-5"><strong>Total a pagar</strong> <span id="detalleTotalPagar" style="color:#00b894;font-weight:bold">S/. 0.00</span></div>
                        </div>
                        <div class="mb-3"><label class="fw-bold mb-2" style="color:#0096c7;">4. Observaciones <span class="text-muted">(opcional)</span></label><textarea id="observacionesCuota" class="form-control" rows="2" placeholder="Agregar observaciones..."></textarea></div>
                    </div>
                </div>
            </div>
            <div class="alert alert-info mt-4 mb-0" id="msgSeleccionCuotas" style="background:#e6f7fb;color:#0096c7;border:0;font-weight:500;"></div>
            <div class="modal-footer p-0" id="footerCuotas" style="background:linear-gradient(90deg,#1ec6ea,#00bfff);border:0;">
                <div class="w-100 d-flex justify-content-center"><button type="button" class="btn fw-bold text-white" id="btnPagarCuotas" style="background:none;font-size:1.1rem;max-width:340px;width:100%;margin:16px 24px;box-shadow:0 2px 8px #00bfff33;background:linear-gradient(90deg,#1ec6ea,#00bfff);border-radius:12px;"><i class="fas fa-credit-card me-2"></i>Registrar Pago</button></div>
            </div>
        </div>
    </div>
</div>

</body>
</html>