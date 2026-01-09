class MantenimientosView {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        this.api = {
            mantenimientos: 'api/mantenimientos.php',
            vehiculos: 'api/vehiculos.php'
        };
        this.data = {
            mantenimientos: [],
            vehiculos: []
        };
        this.modal = null;
        this.init();
    }

    init() {
        this.modal = new bootstrap.Modal(document.getElementById('modalMantenimiento'));
        this.loadInitialData();
        this.initEventListeners();
    }

    async loadInitialData() {
        try {
            const [mantenimientosRes, vehiculosRes] = await Promise.all([
                this.fetchData(this.api.mantenimientos),
                this.fetchData(this.api.vehiculos)
            ]);

            if (mantenimientosRes.success) this.data.mantenimientos = mantenimientosRes.data;
            if (vehiculosRes.success) this.data.vehiculos = vehiculosRes.data;

            this.render();
        } catch (error) {
            this.showError('Error cargando datos iniciales.');
            console.error('Error en loadInitialData:', error);
        }
    }
    
    initEventListeners() {
        document.getElementById('btn-nuevo-mantenimiento').addEventListener('click', () => this.openModal());
        document.getElementById('btn-guardar-mantenimiento').addEventListener('click', () => this.save());
        
        document.getElementById('tabla-mantenimientos').addEventListener('click', e => {
            if (e.target.closest('.btn-edit')) {
                const id = e.target.closest('.btn-edit').dataset.id;
                this.openModal(id);
            } else if (e.target.closest('.btn-delete')) {
                const id = e.target.closest('.btn-delete').dataset.id;
                this.delete(id);
            }
        });
    }

    render() {
        this.renderTable();
        this.populateVehiculosSelect();
    }

    renderTable() {
        const tbody = document.querySelector(`#tabla-mantenimientos tbody`);
        tbody.innerHTML = this.data.mantenimientos.map(item => `
            <tr>
                <td>${this.formatDate(item.fecha)}</td>
                <td>${item.patente}</td>
                <td><span class="badge bg-info">${item.tipo}</span></td>
                <td>${item.descripcion}</td>
                <td>$${parseFloat(item.costo || 0).toLocaleString('es-AR')}</td>
                <td>${(item.kilometraje || 0).toLocaleString('es-AR')} km</td>
                <td>
                    <button class="btn btn-sm btn-warning btn-edit" data-id="${item.id}"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="${item.id}"><i class="bi bi-trash"></i></button>
                </td>
            </tr>
        `).join('');
    }
    
    populateVehiculosSelect() {
        const select = document.querySelector('#form-mantenimiento select[name="vehiculo_id"]');
        select.innerHTML = '<option value="">Seleccione un vehículo</option>' + this.data.vehiculos.map(v => `<option value="${v.id}" data-km="${v.kilometraje_actual}">${v.patente} - ${v.marca} ${v.modelo}</option>`).join('');
        
        select.addEventListener('change', (e) => {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const km = selectedOption.dataset.km;
            document.querySelector('#form-mantenimiento input[name="kilometraje"]').value = km;
        });
    }

    openModal(id = null) {
        const form = document.getElementById('form-mantenimiento');
        form.reset();
        document.getElementById('mantenimiento-id').value = id || '';
        
        if (id) {
            const item = this.data.mantenimientos.find(i => i.id == id);
            Object.keys(item).forEach(key => {
                const input = form.elements[key];
                if (input) {
                    if (input.type === 'date') {
                        input.value = item[key] ? item[key].split(' ')[0] : '';
                    } else {
                        input.value = item[key];
                    }
                }
            });
        }
        this.modal.show();
    }

    async save() {
        const form = document.getElementById('form-mantenimiento');
        if (!form.checkValidity()) {
            this.showError('Por favor, complete los campos obligatorios.');
            return;
        }

        const formData = new FormData(form);
        const id = formData.get('id');
        const method = id ? 'PUT' : 'POST';
        
        // Handle file upload
        const fileInput = form.querySelector('input[name="comprobante_file"]');
        if (fileInput.files.length > 0) {
            // Here you would typically upload the file and get back a URL or path
            // For this project, we'll just simulate it by putting the filename.
            // In a real app, this requires a separate file upload endpoint.
            formData.set('comprobante', fileInput.files[0].name);
        }
        formData.delete('comprobante_file');

        try {
            const res = await this.fetchData(this.api.mantenimientos, method, formData);
            if (res.success) {
                this.showSuccess(res.message);
                this.modal.hide();
                this.loadInitialData();
            } else {
                this.showError(res.message);
            }
        } catch (error) {
            this.showError('Error al guardar el mantenimiento.');
            console.error('Error guardando:', error);
        }
    }
    
    async delete(id) {
        if (!confirm('¿Está seguro de que desea eliminar este registro de mantenimiento?')) return;
        
        try {
            const res = await this.fetchData(this.api.mantenimientos, 'DELETE', new URLSearchParams({id}));
            if (res.success) {
                this.showSuccess(res.message);
                this.loadInitialData();
            } else {
                this.showError(res.message);
            }
        } catch(error) {
            this.showError('Error al eliminar el mantenimiento.');
            console.error('Error eliminando:', error);
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

    showError(message) { alert(`❌ ${message}`); }
    showSuccess(message) { alert(`✅ ${message}`); }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('tabla-mantenimientos')) {
        new MantenimientosView();
    }
});

new MantenimientosView();
