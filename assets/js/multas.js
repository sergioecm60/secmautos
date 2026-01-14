class MultasView {
    constructor() {
        this.apiMultas = 'api/multas.php';
        this.apiVehiculos = 'api/vehiculos.php';
        this.apiAsignaciones = 'api/asignaciones.php';
        this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        this.multas = [];
        this.modal = null;
        this.init();
    }

    init() {
        this.modal = new bootstrap.Modal(document.getElementById('modalMulta'));
        this.cargarDatosIniciales();
        this.initEventListeners();
    }

    async cargarDatosIniciales() {
        try {
            const [multasRes, vehiculosRes] = await Promise.all([
                this.fetchData(this.apiMultas),
                this.fetchData(this.apiVehiculos)
            ]);

            if (multasRes.success) {
                this.multas = multasRes.data;
                this.renderTabla(this.multas);
            }
            if (vehiculosRes.success) {
                this.populateSelect('#multa-vehiculo', vehiculosRes.data, 'id', v => `${v.patente} - ${v.marca} ${v.modelo}`, 'Seleccione un vehículo');
            }
        } catch (error) {
            this.mostrarError('Error al cargar datos iniciales.');
            console.error('Error en cargarDatosIniciales:', error);
        }
    }

    initEventListeners() {
        document.getElementById('btn-nueva-multa').addEventListener('click', () => this.abrirModalMulta());
        document.getElementById('btn-guardar-multa').addEventListener('click', () => this.guardarMulta());
        
        // Autocompletar conductor al cambiar vehículo o fecha
        document.getElementById('multa-vehiculo').addEventListener('change', () => this.buscarConductorResponsable());
        document.getElementById('multa-fecha').addEventListener('change', () => this.buscarConductorResponsable());

        // Delegación de eventos para botones de la tabla
        document.getElementById('tabla-multas').addEventListener('click', (e) => {
            if (e.target.closest('.btn-pagar')) {
                const multaId = e.target.closest('.btn-pagar').dataset.id;
                this.marcarComoPagada(multaId);
            } else if (e.target.closest('.btn-edit-multa')) {
                const multaId = e.target.closest('.btn-edit-multa').dataset.id;
                this.editarMulta(multaId);
            } else if (e.target.closest('.btn-delete-multa')) {
                const multaId = e.target.closest('.btn-delete-multa').dataset.id;
                this.eliminarMulta(multaId);
            }
        });
        
         document.getElementById('multa-estado-form').addEventListener('change', (e) => {
            document.getElementById('fecha-pago-container').style.display = e.target.value === '1' ? 'block' : 'none';
        });
    }

    renderTabla(data) {
        const tbody = document.querySelector('#tabla-multas tbody');
        tbody.innerHTML = '';
        data.forEach(multa => {
            tbody.innerHTML += `
                <tr>
                    <td>${this.formatDate(multa.fecha_multa)}</td>
                    <td><strong>${multa.patente}</strong></td>
                    <td>${multa.nombre_empleado || 'No asignado'}</td>
                    <td>${multa.motivo}</td>
                    <td>$${parseFloat(multa.monto).toLocaleString('es-AR')}</td>
                    <td>${this.getBadgeEstado(multa.pagada)}</td>
                    <td>
                        ${multa.pagada == 0 ? `<button class="btn btn-sm btn-success btn-pagar" data-id="${multa.id}"><i class="bi bi-check-circle"></i></button>` : ''}
                        <button class="btn btn-sm btn-warning btn-edit-multa" data-id="${multa.id}"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-danger btn-delete-multa" data-id="${multa.id}"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
            `;
        });
    }
    
    getBadgeEstado(pagada) {
        return pagada == 1 
            ? '<span class="badge bg-success">Pagada</span>'
            : '<span class="badge bg-danger">Pendiente</span>';
    }

    abrirModalMulta() {
        document.getElementById('form-multa').reset();
        document.getElementById('multa-id').value = '';
        document.getElementById('multa-empleado-info').textContent = 'Seleccione vehículo y fecha para ver al responsable.';
        document.getElementById('multa-empleado-id').value = '';
        document.getElementById('multa-estado-form').disabled = true;
        document.getElementById('fecha-pago-container').style.display = 'none';
        document.getElementById('modal-multa-title').textContent = 'Registrar Nueva Multa';
        document.getElementById('btn-guardar-multa').textContent = 'Guardar Multa';
        this.modal.show();
    }

    async buscarConductorResponsable() {
        const vehiculoId = document.getElementById('multa-vehiculo').value;
        const fecha = document.getElementById('multa-fecha').value;
        const infoP = document.getElementById('multa-empleado-info');
        const empleadoIdInput = document.getElementById('multa-empleado-id');

        if (!vehiculoId || !fecha) {
            infoP.textContent = 'Seleccione vehículo y fecha para ver al responsable.';
            return;
        }

        infoP.textContent = 'Buscando...';
        try {
            const url = `${this.apiAsignaciones}?vehiculo_id=${vehiculoId}&fecha=${fecha}`;
            const res = await this.fetchData(url);

            if (res.success && res.data) {
                infoP.textContent = `${res.data.nombre_empleado} (DNI: ${res.data.dni_empleado})`;
                empleadoIdInput.value = res.data.empleado_id;
            } else {
                infoP.textContent = 'No se encontró asignación para esa fecha.';
                empleadoIdInput.value = '';
            }
        } catch (error) {
            infoP.textContent = 'Error al buscar responsable.';
            console.error('Error buscando responsable:', error);
        }
    }

    async guardarMulta() {
        const form = document.getElementById('form-multa');
        if (!form.checkValidity()) {
            this.mostrarError('Por favor, complete todos los campos requeridos.');
            return;
        }
        
        const formData = new FormData(form);
        const method = formData.get('id') ? 'PUT' : 'POST';

        try {
            const res = await this.fetchData(this.apiMultas, method, formData);
            if (res.success) {
                this.mostrarExito('Multa guardada correctamente.');
                this.modal.hide();
                this.cargarDatosIniciales();
            } else {
                this.mostrarError(res.message || 'Error al guardar la multa.');
            }
        } catch (error) {
            this.mostrarError('Error de red al guardar la multa.');
            console.error('Error en guardarMulta:', error);
        }
    }

    async editarMulta(multaId) {
        try {
            const res = await this.fetchData(`${this.apiMultas}?id=${multaId}`);
            if (res.success) {
                const multa = res.data;
                const form = document.getElementById('form-multa');
                document.getElementById('multa-id').value = multa.id;
                document.getElementById('modal-multa-title').textContent = 'Editar Multa';
                document.getElementById('btn-guardar-multa').textContent = 'Actualizar Multa';
                document.getElementById('multa-vehiculo').value = multa.vehiculo_id;
                
                // Convertir fecha para datetime-local
                if (multa.fecha_multa) {
                    const fecha = new Date(multa.fecha_multa);
                    const fechaFormateada = fecha.toISOString().slice(0, 16);
                    document.getElementById('multa-fecha').value = fechaFormateada;
                }
                
                document.getElementById('multa-motivo').value = multa.motivo;
                document.getElementById('multa-acta').value = multa.numero_acta || '';
                document.getElementById('multa-monto').value = multa.monto;
                document.getElementById('multa-estado-form').value = multa.pagada;
                document.getElementById('multa-empleado-id').value = multa.empleado_id || '';
                
                if (multa.empleado_id) {
                    document.getElementById('multa-empleado-info').textContent = 'Conductor asignado en el registro original.';
                }
                
                if (multa.pagada == 1) {
                    document.getElementById('multa-estado-form').disabled = false;
                    document.getElementById('fecha-pago-container').style.display = 'block';
                    document.getElementById('multa-fecha-pago').value = multa.fecha_pago ? multa.fecha_pago.split(' ')[0] : '';
                }
                
                this.modal.show();
            } else {
                this.showError(res.message);
            }
        } catch (error) {
            this.showError('Error al cargar multa');
            console.error('Error cargando multa:', error);
        }
    }

    async eliminarMulta(multaId) {
        if (!confirm('¿Está seguro de eliminar esta multa?')) return;

        try {
            const body = new URLSearchParams({ id: multaId });
            const res = await this.fetchData(this.apiMultas, 'DELETE', body);
            if (res.success) {
                this.mostrarExito('Multa eliminada correctamente');
                this.cargarDatosIniciales();
            } else {
                this.mostrarError(res.message);
            }
        } catch (error) {
            this.mostrarError('Error al eliminar multa');
            console.error('Error eliminando multa:', error);
        }
    }

    async marcarComoPagada(multaId) {
        const fechaPago = prompt("Ingrese la fecha de pago (YYYY-MM-DD):", new Date().toISOString().split('T')[0]);
        if (!fechaPago) return; // Si el usuario cancela

        const body = new URLSearchParams({
            id: multaId,
            pagada: 1,
            fecha_pago: fechaPago
        });

        try {
            const res = await this.fetchData(this.apiMultas, 'PUT', body);
            if (res.success) {
                this.mostrarExito('Multa marcada como pagada.');
                this.cargarDatosIniciales();
            } else {
                this.mostrarError(res.message || 'Error al actualizar la multa.');
            }
        } catch (error) {
            this.mostrarError('Error de red al actualizar la multa.');
            console.error('Error en marcarComoPagada:', error);
        }
    }

    // --- Utilidades ---
    async fetchData(url, method = 'GET', body = null) {
        const options = { method };
        if (method === 'POST' || method === 'PUT' || method === 'DELETE') {
             if (body) {
                 const effectiveBody = (body instanceof FormData) ? body : new URLSearchParams(body);
                 effectiveBody.set('csrf_token', this.csrfToken);
                 options.body = effectiveBody;
             }
        }
        const res = await fetch(url, options);
        return res.json();
    }

    populateSelect(selector, data, valueField, textField, placeholder) {
        const select = document.querySelector(selector);
        select.innerHTML = `<option value="">${placeholder}</option>`;
        data.forEach(item => {
            const valor = item[valueField];
            select.innerHTML += `<option value="${valor}">${textField(item)}</option>`;
        });
    }
    
    formatDate(dateStr) {
        if (!dateStr) return 'N/A';
        const date = new Date(dateStr);
        return date.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    mostrarError(mensaje) { alert('Error: ' + mensaje); }
    mostrarExito(mensaje) { alert(mensaje); }
}

window.MultasView = MultasView;
window.multasView = new MultasView();
