class UsuariosView {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        this.api = 'api/usuarios.php';
        this.data = [];
        this.modal = null;
        this.init();
    }

    init() {
        this.modal = new bootstrap.Modal(document.getElementById('modalUsuario'));
        this.loadInitialData();
        this.initEventListeners();
    }

    async loadInitialData() {
        try {
            const res = await this.fetchData(this.api);
            if (res.success) {
                this.data = res.data;
                this.renderTable();
            }
        } catch (error) {
            this.showError('Error cargando usuarios.');
            console.error('Error en loadInitialData:', error);
        }
    }

    initEventListeners() {
        document.getElementById('btn-nuevo-usuario').addEventListener('click', () => this.openModal());
        document.getElementById('btn-guardar-usuario').addEventListener('click', () => this.save());

        document.getElementById('tabla-usuarios').addEventListener('click', e => {
            if (e.target.closest('.btn-edit')) {
                const id = e.target.closest('.btn-edit').dataset.id;
                this.openModal(id);
            } else if (e.target.closest('.btn-delete')) {
                const id = e.target.closest('.btn-delete').dataset.id;
                this.delete(id);
            }
        });
    }

    renderTable() {
        const tbody = document.querySelector('#tabla-usuarios tbody');
        tbody.innerHTML = this.data.map(item => `
            <tr>
                <td>${item.nombre}</td>
                <td>${item.apellido}</td>
                <td>${item.email}</td>
                <td>${this.getRolBadge(item.rol)}</td>
                <td>${item.activo ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>'}</td>
                <td>${item.ultimo_acceso ? this.formatDate(item.ultimo_acceso) : 'Nunca'}</td>
                <td>
                    <button class="btn btn-sm btn-warning btn-edit" data-id="${item.id}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="${item.id}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    getRolBadge(rol) {
        const badges = {
            'superadmin': '<span class="badge bg-danger">Superadmin</span>',
            'admin': '<span class="badge bg-warning">Admin</span>',
            'user': '<span class="badge bg-primary">Usuario</span>'
        };
        return badges[rol] || rol;
    }

    openModal(id = null) {
        const form = document.getElementById('form-usuario');
        form.reset();
        document.getElementById('usuario-id').value = id || '';

        if (id) {
            const item = this.data.find(i => i.id == id);
            document.getElementById('usuario-nombre').value = item.nombre || '';
            document.getElementById('usuario-apellido').value = item.apellido || '';
            document.getElementById('usuario-email').value = item.email || '';
            document.getElementById('usuario-rol').value = item.rol || 'user';
            document.getElementById('usuario-activo').checked = item.activo == 1;
            document.getElementById('usuario-password').value = '';
            document.getElementById('modalUsuario').querySelector('.modal-title').textContent = 'Editar Usuario';
        } else {
            document.getElementById('usuario-activo').checked = true;
            document.getElementById('modalUsuario').querySelector('.modal-title').textContent = 'Nuevo Usuario';
        }
        this.modal.show();
    }

    async save() {
        const form = document.getElementById('form-usuario');
        if (!form.checkValidity()) {
            this.showError('Por favor, complete los campos obligatorios.');
            return;
        }

        const formData = new FormData(form);
        const id = formData.get('id');
        const method = id ? 'PUT' : 'POST';

        try {
            const res = await this.fetchData(this.api, method, formData);
            if (res.success) {
                this.showSuccess(res.message);
                this.modal.hide();
                this.loadInitialData();
            } else {
                this.showError(res.message);
            }
        } catch (error) {
            this.showError('Error al guardar el usuario.');
            console.error('Error guardando usuario:', error);
        }
    }

    async delete(id) {
        if (!confirm('¿Está seguro de que desea eliminar este usuario?')) return;

        try {
            const res = await this.fetchData(this.api, 'DELETE', new URLSearchParams({id, csrf_token: this.csrfToken}));
            if (res.success) {
                this.showSuccess(res.message);
                this.loadInitialData();
            } else {
                this.showError(res.message);
            }
        } catch (error) {
            this.showError('Error al eliminar el usuario.');
            console.error('Error eliminando usuario:', error);
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
        return new Date(dateStr).toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    showError(message) { alert(`❌ ${message}`); }
    showSuccess(message) { alert(`✅ ${message}`); }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('tabla-usuarios')) {
        new UsuariosView();
    }
});