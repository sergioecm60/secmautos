class TalleresView {
    constructor() {
        this.talleres = [];
        this.modal = null;
    }

    async init() {
        this.modal = new bootstrap.Modal(document.getElementById('modalTaller'));
        await this.cargar();
    }

    async cargar() {
        try {
            const res = await fetch('api/talleres.php');
            const data = await res.json();

            if (data.success) {
                this.talleres = data.data;
                this.renderTabla();
            } else {
                this.mostrarError('Error al cargar talleres: ' + data.message);
            }
        } catch (error) {
            console.error('Error cargando talleres:', error);
            this.mostrarError('Error de conexión al cargar talleres');
        }
    }

    renderTabla() {
        const tbody = document.getElementById('tabla-talleres-body');

        if (this.talleres.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">No hay talleres registrados</td></tr>';
            return;
        }

        tbody.innerHTML = this.talleres.map(t => `
            <tr>
                <td><strong>${t.nombre}</strong></td>
                <td>${t.direccion || '-'}</td>
                <td>${t.telefono || '-'}</td>
                <td>${t.email || '-'}</td>
                <td>${t.contacto_principal || '-'}</td>
                <td>
                    <span class="badge ${t.activo ? 'bg-success' : 'bg-danger'}">
                        ${t.activo ? 'Activo' : 'Inactivo'}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-warning" onclick="window.talleresView.editar(${t.id})" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="window.talleresView.eliminar(${t.id})" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    nuevoTaller() {
        document.getElementById('modalTallerTitle').textContent = 'Nuevo Taller';
        document.getElementById('form-taller').reset();
        document.getElementById('taller-id').value = '';
        document.getElementById('taller-activo').checked = true;
        this.modal.show();
    }

    editar(id) {
        const taller = this.talleres.find(t => t.id == id);
        if (!taller) {
            this.mostrarError('Taller no encontrado');
            return;
        }

        document.getElementById('modalTallerTitle').textContent = 'Editar Taller';
        document.getElementById('taller-id').value = taller.id;
        document.getElementById('taller-nombre').value = taller.nombre || '';
        document.getElementById('taller-direccion').value = taller.direccion || '';
        document.getElementById('taller-telefono').value = taller.telefono || '';
        document.getElementById('taller-email').value = taller.email || '';
        document.getElementById('taller-contacto').value = taller.contacto_principal || '';
        document.getElementById('taller-observaciones').value = taller.observaciones || '';
        document.getElementById('taller-activo').checked = taller.activo == 1;

        this.modal.show();
    }

    async guardar() {
        const form = document.getElementById('form-taller');

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const id = document.getElementById('taller-id').value;
        const method = id ? 'PUT' : 'POST';
        const url = 'api/talleres.php';

        try {
            let body;
            if (method === 'PUT') {
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
            console.error('Error guardando taller:', error);
            this.mostrarError('Error de conexión al guardar taller');
        }
    }

    async eliminar(id) {
        if (!confirm('¿Está seguro de eliminar este taller?')) {
            return;
        }

        try {
            const params = new URLSearchParams({
                id: id,
                csrf_token: document.querySelector('meta[name="csrf-token"]').content || ''
            }).toString();

            const res = await fetch('api/talleres.php', {
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
            console.error('Error eliminando taller:', error);
            this.mostrarError('Error de conexión al eliminar taller');
        }
    }

    mostrarExito(mensaje) {
        alert('✅ ' + mensaje);
    }

    mostrarError(mensaje) {
        alert('❌ ' + mensaje);
    }
}

window.TalleresView = TalleresView;
window.talleresView = new TalleresView();
