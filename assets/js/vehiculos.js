class VehiculosView {
    constructor() {
        this.vehiculos = [];
        this.modal = null;
    }

    async init() {
        this.modal = new bootstrap.Modal(document.getElementById('modalVehiculo'));
        document.getElementById('vehiculo-csrf').value = csrfToken;
        await this.cargar();
    }

    async cargar() {
        try {
            const res = await fetch('api/vehiculos.php');
            const data = await res.json();

            if (data.success) {
                this.vehiculos = data.data;
                this.renderTabla();
            } else {
                this.mostrarError('Error al cargar vehículos: ' + data.message);
            }
        } catch (error) {
            console.error('Error cargando vehículos:', error);
            this.mostrarError('Error de conexión al cargar vehículos');
        }
    }

    renderTabla(vehiculos = null) {
        const tbody = document.getElementById('tabla-vehiculos-body');
        const items = vehiculos || this.vehiculos;

        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="13" class="text-center">No hay vehículos registrados</td></tr>';
            return;
        }

        tbody.innerHTML = items.map(v => `
            <tr>
                <td><strong>${v.patente}</strong></td>
                <td>${v.tipo_vehiculo || '-'}</td>
                <td>${v.marca || '-'}</td>
                <td>${v.modelo || '-'}</td>
                <td>${v.color || '-'}</td>
                <td>${v.anio || '-'}</td>
                <td>${v.kilometraje_actual ? v.kilometraje_actual.toLocaleString() + ' km' : '-'}</td>
                <td>${this.getBadgeEstado(v.estado)}</td>
                <td>${this.getBadgeEstadoDocumentacion(v.estado_documentacion)}</td>
                <td>${this.formatFecha(v.fecha_vtv)}</td>
                <td>${this.formatFecha(v.fecha_seguro)}</td>
                <td>${v.empleado_actual || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="window.vehiculosView.verFicha(${v.id})" title="Ver ficha completa">
                        👁️
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="window.vehiculosView.editar(${v.id})" title="Editar">
                        📝
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="window.vehiculosView.eliminar(${v.id})" title="Dar de baja">
                        🗑️
                    </button>
                </td>
            </tr>
        `).join('');
    }

    getBadgeEstado(estado) {
        const badges = {
            'disponible': '<span class="badge bg-success">Disponible</span>',
            'asignado': '<span class="badge bg-primary">Asignado</span>',
            'mantenimiento': '<span class="badge bg-warning">Mantenimiento</span>',
            'baja': '<span class="badge bg-danger">Baja</span>'
        };
        return badges[estado] || estado;
    }

    getBadgeEstadoDocumentacion(estado) {
        const badges = {
            'al_dia': '<span class="badge bg-success">Al día</span>',
            'deuda_una': '<span class="badge bg-warning">Deuda (1)</span>',
            'deuda_varias': '<span class="badge bg-danger">Deuda (+1)</span>'
        };
        return badges[estado] || estado;
    }

    formatFecha(fecha) {
        if (!fecha) return '-';
        const date = new Date(fecha + 'T00:00:00');
        return date.toLocaleDateString('es-AR');
    }

    nuevoVehiculo() {
        document.getElementById('modalVehiculoTitulo').textContent = 'Nuevo Vehículo';
        document.getElementById('form-vehiculo').reset();
        document.getElementById('vehiculo-id').value = '';
        document.getElementById('vehiculo-csrf').value = csrfToken;
        this.modal.show();
    }

    editar(id) {
        const vehiculo = this.vehiculos.find(v => v.id == id);
        if (!vehiculo) {
            this.mostrarError('Vehículo no encontrado');
            return;
        }

        document.getElementById('modalVehiculoTitulo').textContent = 'Editar Vehículo';
        document.getElementById('vehiculo-id').value = vehiculo.id;
        document.getElementById('vehiculo-patente').value = vehiculo.patente || '';
        document.getElementById('vehiculo-tipo').value = vehiculo.tipo_vehiculo || 'Auto';
        document.getElementById('vehiculo-color').value = vehiculo.color || '';
        document.getElementById('vehiculo-marca').value = vehiculo.marca || '';
        document.getElementById('vehiculo-modelo').value = vehiculo.modelo || '';
        document.getElementById('vehiculo-anio').value = vehiculo.anio || '';
        document.getElementById('vehiculo-carga-maxima').value = vehiculo.carga_maxima_kg || '';
        document.getElementById('vehiculo-motor').value = vehiculo.motor || '';
        document.getElementById('vehiculo-chasis').value = vehiculo.chasis || '';
        document.getElementById('vehiculo-titulo-dnrpa').value = vehiculo.titulo_dnrpa || '';
        document.getElementById('vehiculo-titularidad').value = vehiculo.titularidad || '';
        document.getElementById('vehiculo-titulo-automotor').value = vehiculo.titulo_automotor || '';
        document.getElementById('vehiculo-cedula-verde').value = vehiculo.cedula_verde || '';
        document.getElementById('vehiculo-fecha-vtv').value = vehiculo.fecha_vtv || '';
        document.getElementById('vehiculo-fecha-seguro').value = vehiculo.fecha_seguro || '';
        document.getElementById('vehiculo-fecha-patente').value = vehiculo.fecha_patente || '';
        document.getElementById('vehiculo-odometro-inicial').value = vehiculo.km_odometro_inicial || 0;
        document.getElementById('vehiculo-kilometraje').value = vehiculo.kilometraje_actual || '';
        document.getElementById('vehiculo-ciclo-preventivo').value = vehiculo.ciclo_mantenimiento_preventivo_km || '';
        document.getElementById('vehiculo-km-service').value = vehiculo.km_proximo_service || '';
        document.getElementById('vehiculo-estado').value = vehiculo.estado || 'disponible';
        document.getElementById('vehiculo-observaciones').value = vehiculo.observaciones || '';
        document.getElementById('vehiculo-csrf').value = csrfToken;

        this.modal.show();
    }

    verFicha(id) {
        const container = document.getElementById('module-ficha_vehiculo');
        container.innerHTML = '';

        Promise.all([
            fetch('modules/ficha_vehiculo.html').then(r => r.text()),
            fetch('assets/js/ficha_vehiculo.js').then(() => {})
        ])
        .then(([html]) => {
            container.innerHTML = html;

            const script = document.createElement('script');
            script.src = 'assets/js/ficha_vehiculo.js';
            script.onload = () => {
                cargarFichaVehiculo(id);
            };
            document.body.appendChild(script);

            document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.module').forEach(m => m.classList.remove('active'));
            container.classList.add('active');
        })
        .catch(error => {
            console.error('Error cargando ficha:', error);
            alert('Error al cargar la ficha del vehículo');
        });
    }

    async guardar() {
        const form = document.getElementById('form-vehiculo');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const id = document.getElementById('vehiculo-id').value;
        const method = id ? 'PUT' : 'POST';
        const url = 'api/vehiculos.php';

        try {
            let body;
            if (method === 'PUT') {
                // Para PUT, convertir FormData a URL-encoded string
                const params = new URLSearchParams(formData).toString();
                body = params;
            } else {
                body = formData;
            }

            const res = await fetch(url, {
                method: method,
                body: body,
                headers: method === 'PUT' ? { 'Content-Type': 'application/x-www-form-urlencoded' } : {}
            });

            const data = await res.json();

            if (data.success) {
                this.mostrarExito(data.message);
                this.modal.hide();
                await this.cargar();
            } else {
                this.mostrarError(data.message);
            }
        } catch (error) {
            console.error('Error guardando vehículo:', error);
            this.mostrarError('Error de conexión al guardar vehículo');
        }
    }

    async eliminar(id) {
        if (!confirm('¿Está seguro de dar de baja este vehículo?')) {
            return;
        }

        try {
            const params = new URLSearchParams({
                id: id,
                csrf_token: csrfToken
            }).toString();

            const res = await fetch('api/vehiculos.php', {
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
            console.error('Error eliminando vehículo:', error);
            this.mostrarError('Error de conexión al eliminar vehículo');
        }
    }

    filtrar() {
        const patente = document.getElementById('filtro-patente').value.toLowerCase();
        const estado = document.getElementById('filtro-estado').value;

        const filtrados = this.vehiculos.filter(v => {
            const cumplePatente = !patente || v.patente.toLowerCase().includes(patente);
            const cumpleEstado = !estado || v.estado === estado;
            return cumplePatente && cumpleEstado;
        });

        this.renderTabla(filtrados);
    }

    mostrarExito(mensaje) {
        alert('✅ ' + mensaje);
    }

    mostrarError(mensaje) {
        alert('❌ ' + mensaje);
    }
}

window.VehiculosView = VehiculosView;
window.vehiculosView = new VehiculosView();

