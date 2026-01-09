class CetaView {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        this.api = {
            ceta: 'api/ceta.php',
            vehiculos: 'api/vehiculos.php'
        };
        this.data = {
            ceta: [],
            vehiculos: []
        };
        this.modal = null;
        this.init();
    }

    init() {
        this.modal = new bootstrap.Modal(document.getElementById('modalCeta'));
        this.loadInitialData();
        this.initEventListeners();
    }

    async loadInitialData() {
        try {
            const [cetaRes, vehiculosRes] = await Promise.all([
                this.fetchData(this.api.ceta),
                this.fetchData(this.api.vehiculos)
            ]);

            if (cetaRes.success) this.data.ceta = cetaRes.data;
            if (vehiculosRes.success) this.data.vehiculos = vehiculosRes.data;

            this.render();
        } catch (error) {
            this.showError('Error cargando datos iniciales.');
            console.error('Error en loadInitialData:', error);
        }
    }
    
    initEventListeners() {
        document.getElementById('btn-nuevo-ceta').addEventListener('click', () => this.openModal());
        document.getElementById('btn-guardar-ceta').addEventListener('click', () => this.save());
        
        // Event delegation for edit buttons
        document.getElementById('tabla-ceta').addEventListener('click', e => {
            if (e.target.closest('.btn-edit')) {
                const id = e.target.closest('.btn-edit').dataset.id;
                this.openModal(id);
            }
        });
    }

    render() {
        this.renderTable();
        this.populateVehiculosSelect();
    }

    renderTable() {
        const tbody = document.querySelector(`#tabla-ceta tbody`);
        tbody.innerHTML = this.data.ceta.map(item => `
            <tr>
                <td>${item.patente} (${item.marca} ${item.modelo})</td>
                <td>${item.cedula_azul_numero || '-'}</td>
                <td class="${this.isVencido(item.fecha_vencimiento) ? 'text-danger' : ''}">${this.formatDate(item.fecha_vencimiento)}</td>
                <td>${this.getBadgeEnviado(item.enviado, item.fecha_envio)}</td>
                <td>
                    <button class="btn btn-sm btn-warning btn-edit" data-id="${item.id}">
                        <i class="bi bi-pencil"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }
    
    getBadgeEnviado(enviado, fecha_envio) {
        if (enviado == 1) {
            return `<span class="badge bg-success">Enviado ${this.formatDate(fecha_envio)}</span>`;
        }
        return '<span class="badge bg-secondary">Pendiente</span>';
    }

    isVencido(fecha) {
        return new Date(fecha) < new Date();
    }
    
    populateVehiculosSelect() {
        const select = document.querySelector('#form-ceta select[name="vehiculo_id"]');
        select.innerHTML = '<option value="">Seleccione un vehículo</option>' + this.data.vehiculos.map(v => `<option value="${v.id}">${v.patente} - ${v.marca} ${v.modelo}</option>`).join('');
    }

    openModal(id = null) {
        const form = document.getElementById('form-ceta');
        form.reset();
        document.getElementById('ceta-id').value = id || '';
        
        if (id) {
            const item = this.data.ceta.find(i => i.id == id);
            Object.keys(item).forEach(key => {
                const input = form.elements[key];
                if (input) {
                    if (input.type === 'checkbox') {
                        input.checked = item[key] == 1;
                    } else if (input.type === 'date') {
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
        const form = document.getElementById('form-ceta');
        if (!form.checkValidity()) {
            this.showError('Por favor, complete los campos obligatorios.');
            return;
        }

        const formData = new FormData(form);
        // Handle checkbox value
        if (!formData.has('enviado')) {
            formData.set('enviado', '0');
        }
        
        const id = formData.get('id');
        const method = id ? 'PUT' : 'POST';

        try {
            const res = await this.fetchData(this.api.ceta, method, formData);
            if (res.success) {
                this.showSuccess(res.message);
                this.modal.hide();
                this.loadInitialData(); // Recargar
            } else {
                this.showError(res.message);
            }
        } catch (error) {
            this.showError('Error al guardar el registro CETA.');
            console.error('Error guardando CETA:', error);
        }
    }
    
    async fetchData(url, method = 'GET', body = null) {
        const options = { method };
        if (body) {
            const effectiveBody = (method === 'PUT') ? new URLSearchParams(body) : body;
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
    if (document.getElementById('tabla-ceta')) {
        new CetaView();
    }
});

new CetaView();
