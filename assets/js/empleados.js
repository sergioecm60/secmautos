class EmpleadosView {
    constructor() {
        this.empleados = [];
        this.modal = null;
    }

    async init() {
        this.modal = new bootstrap.Modal(document.getElementById('modalEmpleado'));
        document.getElementById('empleado-csrf').value = csrfToken;
        await this.cargar();
    }

    async cargar() {
        try {
            const res = await fetch('api/empleados.php');
            const data = await res.json();

            if (data.success) {
                this.empleados = data.data;
                this.renderTabla();
            } else {
                this.mostrarError('Error al cargar empleados: ' + data.message);
            }
        } catch (error) {
            console.error('Error cargando empleados:', error);
            this.mostrarError('Error de conexión al cargar empleados');
        }
    }

    renderTabla(empleados = null) {
        const tbody = document.getElementById('tabla-empleados-body');
        const items = empleados || this.empleados;

        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">No hay empleados registrados</td></tr>';
            return;
        }

        tbody.innerHTML = items.map(e => `
            <tr>
                <td><strong>${e.apellido}, ${e.nombre}</strong></td>
                <td>${e.dni || '-'}</td>
                <td>${e.email || '-'}</td>
                <td>${e.telefono || '-'}</td>
                <td>${e.direccion || '-'}</td>
                <td>${this.getBadgeEstado(e.activo)}</td>
                <td>
                    <button class="btn btn-sm btn-warning" onclick="window.empleadosView.editar(${e.id})" title="Editar">
                        📝
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="window.empleadosView.eliminar(${e.id})" title="Dar de baja">
                        🗑️
                    </button>
                </td>
            </tr>
        `).join('');
    }

    getBadgeEstado(activo) {
        return activo ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>';
    }

    nuevo() {
        document.getElementById('modalEmpleadoTitulo').textContent = 'Nuevo Empleado';
        document.getElementById('form-empleado').reset();
        document.getElementById('empleado-id').value = '';
        document.getElementById('empleado-csrf').value = csrfToken;
        this.modal.show();
    }

    editar(id) {
        const empleado = this.empleados.find(e => e.id == id);
        if (!empleado) {
            this.mostrarError('Empleado no encontrado');
            return;
        }

        document.getElementById('modalEmpleadoTitulo').textContent = 'Editar Empleado';
        document.getElementById('empleado-id').value = empleado.id;
        document.getElementById('empleado-nombre').value = empleado.nombre || '';
        document.getElementById('empleado-apellido').value = empleado.apellido || '';
        document.getElementById('empleado-dni').value = empleado.dni || '';
        document.getElementById('empleado-telefono').value = empleado.telefono || '';
        document.getElementById('empleado-email').value = empleado.email || '';
        document.getElementById('empleado-direccion').value = empleado.direccion || '';
        document.getElementById('empleado-csrf').value = csrfToken;

        this.modal.show();
    }

    async guardar() {
        const form = document.getElementById('form-empleado');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const id = document.getElementById('empleado-id').value;
        const method = id ? 'PUT' : 'POST';

        try {
            let body = method === 'PUT' ? new URLSearchParams(formData).toString() : formData;

            const res = await fetch('api/empleados.php', {
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
            console.error('Error guardando empleado:', error);
            this.mostrarError('Error de conexión al guardar empleado');
        }
    }

    async eliminar(id) {
        if (!confirm('¿Está seguro de dar de baja este empleado?')) {
            return;
        }

        try {
            const params = new URLSearchParams({ id: id, csrf_token: csrfToken }).toString();

            const res = await fetch('api/empleados.php', {
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
            console.error('Error eliminando empleado:', error);
            this.mostrarError('Error de conexión al eliminar empleado');
        }
    }

    filtrar() {
        const texto = document.getElementById('filtro-empleado').value.toLowerCase();

        const filtrados = this.empleados.filter(e => {
            return !texto ||
                   e.nombre.toLowerCase().includes(texto) ||
                   e.apellido.toLowerCase().includes(texto) ||
                   (e.dni && e.dni.includes(texto));
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

window.empleadosView = new EmpleadosView();
