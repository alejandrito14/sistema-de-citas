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

</body>
</html>