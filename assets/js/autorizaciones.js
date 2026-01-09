class AutorizacionesView {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        this.api = 'api/autorizaciones_manejo.php';
        this.apiEmpleados = 'api/empleados.php';
        this.apiVehiculos = 'api/vehiculos.php';
        this.data = {
            autorizaciones: [],
            empleados: [],
            vehiculos: []
        };
        this.init();
    }

    init() {
        this.loadInitialData();
        this.initEventListeners();
    }

    async loadInitialData() {
        try {
            const [autorizacionesRes, empleadosRes, vehiculosRes] = await Promise.all([
                this.fetchData(this.api),
                this.fetchData(this.apiEmpleados),
                this.fetchData(this.apiVehiculos)
            ]);

            if (autorizacionesRes.success) this.data.autorizaciones = autorizacionesRes.data;
            if (empleadosRes.success) this.data.empleados = empleadosRes.data;
            if (vehiculosRes.success) this.data.vehiculos = vehiculosRes.data.filter(v => v.estado !== 'baja');

            this.render();
        } catch (error) {
            alert('❌ Error cargando datos iniciales.');
            console.error('Error en loadInitialData:', error);
        }
    }

    initEventListeners() {
        const btnNueva = document.getElementById('btn-nueva-autorizacion');
        const btnGuardar = document.getElementById('btn-guardar-autorizacion');
        const tabla = document.getElementById('tabla-autorizaciones');

        if (btnNueva) btnNueva.addEventListener('click', () => this.openModal());
        if (btnGuardar) btnGuardar.addEventListener('click', () => this.save());

        if (tabla) {
            tabla.addEventListener('click', e => {
                if (e.target.closest('.btn-edit-autorizacion')) {
                    const id = e.target.closest('.btn-edit-autorizacion').dataset.id;
                    this.edit(id);
                } else if (e.target.closest('.btn-delete-autorizacion')) {
                    const id = e.target.closest('.btn-delete-autorizacion').dataset.id;
                    this.delete(id);
                }
            });
        }
    }

    render() {
        this.renderTable();
        this.populateSelects();
    }

    renderTable() {
        const tbody = document.querySelector('#tabla-autorizaciones tbody');
        tbody.innerHTML = '';

        if (this.data.autorizaciones.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No hay autorizaciones registradas</td></tr>';
            return;
        }

        this.data.autorizaciones.forEach(a => {
            tbody.innerHTML += `
                <tr>
                    <td><strong>${a.nombre} ${a.apellido}</strong></td>
                    <td>${a.marca} ${a.modelo}</td>
                    <td><span class="badge bg-primary">${a.patente}</span></td>
                    <td>${this.formatDate(a.fecha_otorgamiento)}</td>
                    <td>${this.getBadgeEstado(a.activa)}</td>
                    <td>
                        <button class="btn btn-sm btn-warning btn-edit-autorizacion" data-id="${a.id}" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-delete-autorizacion" data-id="${a.id}" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
    }

    getBadgeEstado(activa) {
        return activa == 1
            ? '<span class="badge bg-success">Activa</span>'
            : '<span class="badge bg-danger">Revocada</span>';
    }

    populateSelects() {
        const selectEmpleado = document.getElementById('autorizacion-empleado');
        const selectVehiculo = document.getElementById('autorizacion-vehiculo');

        if (selectEmpleado) {
            selectEmpleado.innerHTML = '<option value="">Seleccionar empleado</option>' +
                this.data.empleados.map(e =>
                    `<option value="${e.id}">${e.apellido}, ${e.nombre} (DNI: ${e.dni || 'N/A'})</option>`
                ).join('');
        }

        if (selectVehiculo) {
            selectVehiculo.innerHTML = '<option value="">Seleccionar vehículo</option>' +
                this.data.vehiculos.map(v =>
                    `<option value="${v.id}">${v.patente} - ${v.marca} ${v.modelo}</option>`
                ).join('');
        }
    }

    openModal() {
        const form = document.getElementById('form-autorizacion');
        form.reset();
        document.getElementById('autorizacion-id').value = '';
        document.getElementById('autorizacion-fecha').value = new Date().toISOString().split('T')[0];
        document.getElementById('autorizacion-activa').checked = true;

        this.populateSelects();

        new bootstrap.Modal(document.getElementById('modalAutorizacion')).show();
    }

    async save() {
        const form = document.getElementById('form-autorizacion');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const id = formData.get('id');
        const method = id ? 'PUT' : 'POST';
        formData.set('csrf_token', this.csrfToken);

        try {
            const res = await this.fetchData(this.api, method, formData);
            if (res.success) {
                alert(res.message);
                bootstrap.Modal.getInstance(document.getElementById('modalAutorizacion')).hide();
                this.loadInitialData();
            } else {
                alert('Error: ' + res.message);
            }
        } catch (error) {
            alert('❌ Error al guardar la autorización');
            console.error('Error guardando autorización:', error);
        }
    }

    async edit(id) {
        try {
            const res = await this.fetchData(`${this.api}?id=${id}`);
            if (res.success) {
                const autorizacion = res.data;
                const form = document.getElementById('form-autorizacion');
                document.getElementById('autorizacion-id').value = autorizacion.id;
                document.getElementById('autorizacion-empleado').value = autorizacion.empleado_id;
                document.getElementById('autorizacion-vehiculo').value = autorizacion.vehiculo_id;
                document.getElementById('autorizacion-fecha').value = autorizacion.fecha_otorgamiento ? autorizacion.fecha_otorgamiento.split(' ')[0] : '';
                document.getElementById('autorizacion-activa').checked = autorizacion.activa == 1;
                document.getElementById('autorizacion-observaciones').value = autorizacion.observaciones || '';

                new bootstrap.Modal(document.getElementById('modalAutorizacion')).show();
            } else {
                alert('Error: ' + res.message);
            }
        } catch (error) {
            alert('❌ Error al cargar la autorización');
            console.error('Error cargando autorización:', error);
        }
    }

    async delete(id) {
        if (!confirm('¿Está seguro de eliminar esta autorización de manejo?')) return;

        try {
            const res = await this.fetchData(this.api, 'DELETE', new URLSearchParams({id, csrf_token: this.csrfToken}));
            if (res.success) {
                alert(res.message);
                this.loadInitialData();
            } else {
                alert('Error: ' + res.message);
            }
        } catch (error) {
            alert('❌ Error al eliminar la autorización');
            console.error('Error eliminando autorización:', error);
        }
    }

    async fetchData(url, method = 'GET', body = null) {
        const options = { method };
        if (body) {
            const effectiveBody = (method === 'PUT' || method === 'DELETE') ? new URLSearchParams(body) : body;
            effectiveBody.set('csrf_token', this.csrfToken);
            options.body = effectiveBody;
        }
        const res = await fetch(url, options);
        return res.json();
    }

    formatDate(dateStr) {
        if (!dateStr) return 'N/A';
        return new Date(dateStr + 'T00:00:00').toLocaleDateString('es-AR');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('tabla-autorizaciones')) {
        new AutorizacionesView();
    }
});
