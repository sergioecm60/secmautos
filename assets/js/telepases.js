class TelepasesView {
    constructor() {
        this.telepases = [];
        this.vehiculos = [];
        this.modalTelepase = null;
        this.modalPagos = null;
        this.modalImportar = null;
        this.datosImportar = [];
    }

    async init() {
        this.modalTelepase = new bootstrap.Modal(document.getElementById('modalTelepase'));
        this.modalPagos = new bootstrap.Modal(document.getElementById('modalPagosTelepase'));
        this.modalImportar = new bootstrap.Modal(document.getElementById('modalImportarPagos'));

        await this.cargar();
        await this.cargarVehiculos();
    }

    async cargar() {
        try {
            const res = await fetch('api/telepases.php');
            const data = await res.json();

            if (data.success) {
                this.telepases = data.data;
                this.renderTabla();
            } else {
                this.mostrarError('Error al cargar dispositivos: ' + data.message);
            }
        } catch (error) {
            console.error('Error cargando dispositivos:', error);
            this.mostrarError('Error de conexión al cargar dispositivos');
        }
    }

    async cargarVehiculos() {
        try {
            const res = await fetch('api/vehiculos.php');
            const data = await res.json();

            if (data.success) {
                this.vehiculos = data.data;
                this.actualizarSelectVehiculos();
            }
        } catch (error) {
            console.error('Error cargando vehículos:', error);
        }
    }

    actualizarSelectVehiculos() {
        const selects = ['telepase-vehiculo', 'import-dispositivo'];

        selects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (!select) return;

            if (selectId === 'telepase-vehiculo') {
                select.innerHTML = '<option value="">Seleccionar vehículo...</option>';
                this.vehiculos.forEach(v => {
                    if (v.estado !== 'baja') {
                        select.innerHTML += `<option value="${v.id}">${v.patente} - ${v.marca} ${v.modelo}</option>`;
                    }
                });
            } else if (selectId === 'import-dispositivo') {
                select.innerHTML = '<option value="">Seleccionar dispositivo...</option>';
                this.telepases.forEach(t => {
                    if (t.estado === 'habilitado') {
                        select.innerHTML += `<option value="${t.id}" data-vehiculo="${t.vehiculo_id}">${t.numero_dispositivo} - ${t.patente}</option>`;
                    }
                });
            }
        });
    }

    renderTabla(telepases = null) {
        const tbody = document.querySelector('#tabla-telepases tbody');
        const items = telepases || this.telepases;

        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center">No hay dispositivos registrados</td></tr>';
            return;
        }

        tbody.innerHTML = items.map(t => {
            const estadoBadge = {
                'habilitado': '<span class="badge bg-success">Habilitado</span>',
                'deshabilitado': '<span class="badge bg-warning">Deshabilitado</span>',
                'baja': '<span class="badge bg-secondary">Baja</span>'
            }[t.estado] || t.estado;

            const fechaBaja = t.fecha_baja ? this.formatFecha(t.fecha_baja) : '-';
            const pagosPendientes = t.pagos_pendientes > 0
                ? `<span class="badge bg-danger">${t.pagos_pendientes} ($${parseFloat(t.monto_pendiente).toFixed(2)})</span>`
                : '<span class="badge bg-success">Sin pendientes</span>';

            return `
                <tr>
                    <td><strong>${t.patente}</strong></td>
                    <td>${t.marca || '-'}</td>
                    <td>${t.modelo || '-'}</td>
                    <td><code>${t.numero_dispositivo}</code></td>
                    <td>${this.formatFecha(t.fecha_activacion)}</td>
                    <td>${fechaBaja}</td>
                    <td>${estadoBadge}</td>
                    <td>${pagosPendientes}</td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="window.telepasesView.verPagos(${t.id})" title="Ver pagos">
                            <i class="bi bi-receipt"></i>
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="window.telepasesView.editar(${t.id})" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="window.telepasesView.eliminar(${t.id})" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    nuevoTelepase() {
        document.getElementById('modalTelepaseTitle').textContent = 'Nuevo Dispositivo Telepase';
        document.getElementById('form-telepase').reset();
        document.getElementById('telepase-id').value = '';
        document.getElementById('campo-fecha-baja').style.display = 'none';
        document.getElementById('telepase-fecha-baja').required = false;

        // Establecer fecha de hoy por defecto
        const hoy = new Date().toISOString().split('T')[0];
        document.getElementById('telepase-fecha-activacion').value = hoy;

        this.modalTelepase.show();
    }

    editar(id) {
        const telepase = this.telepases.find(t => t.id == id);
        if (!telepase) {
            this.mostrarError('Dispositivo no encontrado');
            return;
        }

        document.getElementById('modalTelepaseTitle').textContent = 'Editar Dispositivo Telepase';
        document.getElementById('telepase-id').value = telepase.id;
        document.getElementById('telepase-vehiculo').value = telepase.vehiculo_id || '';
        document.getElementById('telepase-dispositivo').value = telepase.numero_dispositivo || '';
        document.getElementById('telepase-fecha-activacion').value = telepase.fecha_activacion || '';
        document.getElementById('telepase-fecha-baja').value = telepase.fecha_baja || '';
        document.getElementById('telepase-estado').value = telepase.estado || 'habilitado';
        document.getElementById('telepase-observaciones').value = telepase.observaciones || '';

        // Mostrar campo fecha de baja si el estado es 'baja'
        if (telepase.estado === 'baja') {
            document.getElementById('campo-fecha-baja').style.display = 'block';
            document.getElementById('telepase-fecha-baja').required = true;
        }

        this.modalTelepase.show();
    }

    async guardar() {
        const form = document.getElementById('form-telepase');

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const id = document.getElementById('telepase-id').value;
        const method = id ? 'PUT' : 'POST';

        try {
            let body;
            if (method === 'PUT') {
                body = new URLSearchParams(formData).toString();
            } else {
                body = formData;
            }

            const res = await fetch('api/telepases.php', {
                method: method,
                body: body,
                headers: method === 'PUT' ? { 'Content-Type': 'application/x-www-form-urlencoded' } : {}
            });

            const data = await res.json();

            if (data.success) {
                this.mostrarExito(data.message);
                this.modalTelepase.hide();
                await this.cargar();
            } else {
                this.mostrarError(data.message);
            }
        } catch (error) {
            console.error('Error guardando dispositivo:', error);
            this.mostrarError('Error de conexión al guardar dispositivo');
        }
    }

    async eliminar(id) {
        if (!confirm('¿Está seguro de eliminar este dispositivo? Esto también eliminará su historial de pagos.')) {
            return;
        }

        try {
            const params = new URLSearchParams({
                id: id,
                csrf_token: document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('[name="csrf_token"]').value
            }).toString();

            const res = await fetch('api/telepases.php', {
                method: 'DELETE',
                body: params,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });

            const data = await res.json();

            if (data.success) {
                this.mostrarExito(data.message);
                await this.cargar();
            } else {
                this.mostrarError(data.message);
            }
        } catch (error) {
            console.error('Error eliminando dispositivo:', error);
            this.mostrarError('Error de conexión al eliminar dispositivo');
        }
    }

    async verPagos(telepaseId) {
        const telepase = this.telepases.find(t => t.id == telepaseId);
        if (!telepase) return;

        document.getElementById('pagos-dispositivo-numero').textContent =
            `${telepase.numero_dispositivo} (${telepase.patente})`;

        try {
            const res = await fetch(`api/telepases.php?pagos=${telepaseId}`);
            const data = await res.json();

            if (data.success) {
                this.renderTablaPagos(data.data);
                this.modalPagos.show();
            } else {
                this.mostrarError('Error al cargar pagos: ' + data.message);
            }
        } catch (error) {
            console.error('Error cargando pagos:', error);
            this.mostrarError('Error de conexión al cargar pagos');
        }
    }

    renderTablaPagos(pagos) {
        const tbody = document.getElementById('tabla-pagos-telepase');

        if (pagos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">No hay pagos registrados</td></tr>';
            return;
        }

        tbody.innerHTML = pagos.map(p => {
            const estadoBadge = {
                'pendiente': '<span class="badge bg-warning">Pendiente</span>',
                'pagado': '<span class="badge bg-success">Pagado</span>',
                'vencido': '<span class="badge bg-danger">Vencido</span>'
            }[p.estado] || p.estado;

            return `
                <tr>
                    <td>${this.formatFecha(p.periodo)}</td>
                    <td>${p.concesionario}</td>
                    <td>${p.numero_comprobante}</td>
                    <td>${this.formatFecha(p.fecha_vencimiento)}</td>
                    <td>$${parseFloat(p.monto).toFixed(2)}</td>
                    <td>${estadoBadge}</td>
                    <td>
                        ${p.estado === 'pendiente' ? `
                            <button class="btn btn-sm btn-success" onclick="window.telepasesView.marcarPagado(${p.id})" title="Marcar como pagado">
                                <i class="bi bi-check-circle"></i>
                            </button>
                        ` : ''}
                        <button class="btn btn-sm btn-danger" onclick="window.telepasesView.eliminarPago(${p.id})" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    async marcarPagado(pagoId) {
        if (!confirm('¿Marcar este pago como pagado?')) {
            return;
        }

        try {
            const params = new URLSearchParams({
                id: pagoId,
                estado: 'pagado',
                fecha_pago: new Date().toISOString().split('T')[0],
                csrf_token: document.querySelector('[name="csrf_token"]').value
            }).toString();

            const res = await fetch('api/pagos_telepase.php', {
                method: 'PUT',
                body: params,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });

            const data = await res.json();

            if (data.success) {
                this.mostrarExito(data.message);
                // Recargar pagos
                const telepaseId = this.telepases.find(t =>
                    t.id == document.getElementById('pagos-dispositivo-numero').textContent.split('(')[1]?.split(')')[0]
                )?.id;
                if (telepaseId) {
                    await this.verPagos(telepaseId);
                }
                await this.cargar(); // Actualizar contador de pendientes
            } else {
                this.mostrarError(data.message);
            }
        } catch (error) {
            console.error('Error marcando pago:', error);
            this.mostrarError('Error de conexión');
        }
    }

    async eliminarPago(pagoId) {
        if (!confirm('¿Está seguro de eliminar este pago?')) {
            return;
        }

        try {
            const params = new URLSearchParams({
                id: pagoId,
                csrf_token: document.querySelector('[name="csrf_token"]').value
            }).toString();

            const res = await fetch('api/pagos_telepase.php', {
                method: 'DELETE',
                body: params,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });

            const data = await res.json();

            if (data.success) {
                this.mostrarExito(data.message);
                // Recargar lista actual
                const currentTelepaseId = this.getCurrentTelepaseIdFromModal();
                if (currentTelepaseId) {
                    await this.verPagos(currentTelepaseId);
                }
                await this.cargar();
            } else {
                this.mostrarError(data.message);
            }
        } catch (error) {
            console.error('Error eliminando pago:', error);
            this.mostrarError('Error de conexión');
        }
    }

    getCurrentTelepaseIdFromModal() {
        // Extraer el ID del telepase desde el modal activo
        const texto = document.getElementById('pagos-dispositivo-numero').textContent;
        const match = texto.match(/\(([A-Z0-9]+)\)/);
        if (match) {
            const patente = match[1];
            const telepase = this.telepases.find(t => t.patente === patente);
            return telepase?.id;
        }
        return null;
    }

    abrirModalImportarPagos() {
        this.actualizarSelectVehiculos();
        document.getElementById('import-file').value = '';
        document.getElementById('preview-import').style.display = 'none';
        document.getElementById('btn-importar').disabled = true;
        this.modalImportar.show();
    }

    async previewImport() {
        const fileInput = document.getElementById('import-file');
        const file = fileInput.files[0];

        if (!file) return;

        const reader = new FileReader();
        reader.onload = (e) => {
            const text = e.target.result;
            this.datosImportar = this.parsearArchivoImport(text);

            if (this.datosImportar.length > 0) {
                this.renderPreviewImport();
                document.getElementById('preview-import').style.display = 'block';
                document.getElementById('btn-importar').disabled = false;
            } else {
                this.mostrarError('No se pudieron extraer datos del archivo');
            }
        };
        reader.readAsText(file);
    }

    parsearArchivoImport(text) {
        const lineas = text.split('\n').filter(l => l.trim());
        const datos = [];

        for (let linea of lineas) {
            // Intentar parsear formato: Período | Concesionario | N° Comprobante | Venc. | Venc. Recargo | Monto | Monto Recargo | Estado
            const partes = linea.split(/[|\t]/).map(p => p.trim());

            if (partes.length >= 8) {
                datos.push({
                    periodo: this.parseFecha(partes[0]),
                    concesionario: partes[1],
                    numero_comprobante: partes[2],
                    fecha_vencimiento: this.parseFecha(partes[3]),
                    fecha_vencimiento_recargo: this.parseFecha(partes[4]),
                    monto: this.parseMonto(partes[5]),
                    monto_recargo: this.parseMonto(partes[6]),
                    estado: partes[7].toLowerCase() === 'pagado' ? 'pagado' : 'pendiente'
                });
            }
        }

        return datos;
    }

    parseFecha(fechaStr) {
        // Formato esperado: YYYY-MM-DD o DD/MM/YYYY
        if (!fechaStr) return null;

        if (fechaStr.includes('/')) {
            const [dia, mes, anio] = fechaStr.split('/');
            return `${anio}-${mes.padStart(2, '0')}-${dia.padStart(2, '0')}`;
        }
        return fechaStr;
    }

    parseMonto(montoStr) {
        // Eliminar símbolos de moneda y separadores de miles
        return parseFloat(montoStr.replace(/[$.,]/g, '').trim()) / 100;
    }

    renderPreviewImport() {
        const tbody = document.getElementById('preview-import-body');

        tbody.innerHTML = this.datosImportar.map((d, idx) => `
            <tr>
                <td><input type="checkbox" class="form-check-input import-checkbox" data-index="${idx}" checked></td>
                <td>${this.formatFecha(d.periodo)}</td>
                <td>${d.concesionario}</td>
                <td>${d.numero_comprobante}</td>
                <td>${this.formatFecha(d.fecha_vencimiento)}</td>
                <td>$${d.monto.toFixed(2)}</td>
                <td><span class="badge bg-${d.estado === 'pagado' ? 'success' : 'warning'}">${d.estado}</span></td>
            </tr>
        `).join('');
    }

    toggleSelectAll() {
        const selectAll = document.getElementById('select-all-import').checked;
        document.querySelectorAll('.import-checkbox').forEach(cb => {
            cb.checked = selectAll;
        });
    }

    async importarPagos() {
        const dispositivo = document.getElementById('import-dispositivo').value;

        if (!dispositivo) {
            this.mostrarError('Debe seleccionar un dispositivo');
            return;
        }

        const selectedOption = document.getElementById('import-dispositivo').options[document.getElementById('import-dispositivo').selectedIndex];
        const vehiculoId = selectedOption.getAttribute('data-vehiculo');

        // Obtener solo los seleccionados
        const seleccionados = [];
        document.querySelectorAll('.import-checkbox:checked').forEach(cb => {
            const idx = parseInt(cb.getAttribute('data-index'));
            seleccionados.push(this.datosImportar[idx]);
        });

        if (seleccionados.length === 0) {
            this.mostrarError('Debe seleccionar al menos un pago');
            return;
        }

        const btnImportar = document.getElementById('btn-importar');
        btnImportar.disabled = true;
        btnImportar.textContent = 'Importando...';

        try {
            const res = await fetch('api/pagos_telepase.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    telepase_id: dispositivo,
                    vehiculo_id: vehiculoId,
                    pagos: seleccionados,
                    csrf_token: document.querySelector('[name="csrf_token"]').value
                })
            });

            const data = await res.json();

            if (data.success) {
                this.mostrarExito(`Se importaron ${seleccionados.length} pagos correctamente`);
                this.modalImportar.hide();
                await this.cargar();
            } else {
                this.mostrarError(data.message);
            }
        } catch (error) {
            console.error('Error importando pagos:', error);
            this.mostrarError('Error de conexión al importar pagos');
        } finally {
            btnImportar.disabled = false;
            btnImportar.textContent = 'Importar Seleccionados';
        }
    }

    filtrar() {
        const patente = document.getElementById('filtro-patente').value.toLowerCase();
        const dispositivo = document.getElementById('filtro-dispositivo').value.toLowerCase();
        const estado = document.getElementById('filtro-estado').value;

        let filtrados = this.telepases;

        if (patente) {
            filtrados = filtrados.filter(t => t.patente.toLowerCase().includes(patente));
        }

        if (dispositivo) {
            filtrados = filtrados.filter(t => t.numero_dispositivo.toLowerCase().includes(dispositivo));
        }

        if (estado) {
            filtrados = filtrados.filter(t => t.estado === estado);
        }

        this.renderTabla(filtrados);
    }

    formatFecha(fecha) {
        if (!fecha) return '-';
        const f = new Date(fecha + 'T00:00:00');
        return f.toLocaleDateString('es-AR');
    }

    mostrarExito(mensaje) {
        alert('✅ ' + mensaje);
    }

    mostrarError(mensaje) {
        alert('❌ ' + mensaje);
    }
}

// Instancia global
window.TelepasesView = TelepasesView;
window.telepasesView = new TelepasesView();

// Inicializar cuando se carga el módulo
setTimeout(() => {
    if (document.getElementById('tabla-telepases')) {
        window.telepasesView.init();
    }
}, 100);

// Funciones globales para compatibilidad con onclick
function nuevoTelepase() {
    window.telepasesView.nuevoTelepase();
}

function filtrarTelepases() {
    window.telepasesView.filtrar();
}

function guardarTelepase() {
    window.telepasesView.guardar();
}
