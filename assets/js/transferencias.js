class TransferenciasView {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        this.api = {
            transferencias: 'api/transferencias.php',
            vehiculos: 'api/vehiculos.php'
        };
        this.data = {
            transferencias: [],
            vehiculos: []
        };
        this.modal = null;
        this.init();
    }

    init() {
        this.modal = new bootstrap.Modal(document.getElementById('modalTransferencia'));
        this.loadInitialData();
        this.initEventListeners();
    }

    async loadInitialData() {
        try {
            const [transferenciasRes, vehiculosRes] = await Promise.all([
                this.fetchData(this.api.transferencias),
                this.fetchData(this.api.vehiculos)
            ]);

            if (transferenciasRes.success) this.data.transferencias = transferenciasRes.data;
            if (vehiculosRes.success) this.data.vehiculos = vehiculosRes.data;

            this.render();
        } catch (error) {
            this.showError('Error cargando datos iniciales.');
            console.error('Error en loadInitialData:', error);
        }
    }
    
    initEventListeners() {
        document.getElementById('btn-nueva-transferencia').addEventListener('click', () => this.openModal());
        document.getElementById('btn-guardar-transferencia').addEventListener('click', () => this.save());
        
        document.getElementById('tabla-transferencias').addEventListener('click', e => {
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
        const tbody = document.querySelector(`#tabla-transferencias tbody`);
        tbody.innerHTML = this.data.transferencias.map(item => `
            <tr>
                <td>${this.formatDate(item.fecha)}</td>
                <td>${item.patente} (${item.marca} ${item.modelo})</td>
                <td>${item.registro || '-'}</td>
                <td>${item.numero_tramite || '-'}</td>
                <td>${this.getBadgeEstado(item.estado)}</td>
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
    
    getBadgeEstado(estado) {
        const badges = {
            'en_proceso': 'bg-primary',
            'completa': 'bg-success',
            'cancelada': 'bg-danger'
        };
        const className = badges[estado] || 'bg-secondary';
        return `<span class="badge ${className}">${estado.replace('_', ' ')}</span>`;
    }
    
    populateVehiculosSelect() {
        const select = document.querySelector('#form-transferencia select[name="vehiculo_id"]');
        select.innerHTML = '<option value="">Seleccione un vehículo</option>' + this.data.vehiculos.map(v => `<option value="${v.id}">${v.patente} - ${v.marca} ${v.modelo}</option>`).join('');
    }

    openModal(id = null) {
        const form = document.getElementById('form-transferencia');
        form.reset();
        document.getElementById('transferencia-id').value = id || '';
        
        if (id) {
            const item = this.data.transferencias.find(i => i.id == id);
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

    async delete(id) {
        if (!confirm('¿Está seguro de eliminar esta transferencia?')) return;

        try {
            const res = await this.fetchData(this.api.transferencias, 'DELETE', new URLSearchParams({id}));
            if (res.success) {
                this.showSuccess(res.message);
                this.loadInitialData();
            } else {
                this.showError(res.message);
            }
        } catch (error) {
            this.showError('Error al eliminar la transferencia.');
            console.error('Error eliminando transferencia:', error);
        }
    }

    async save() {
        const form = document.getElementById('form-transferencia');
        if (!form.checkValidity()) {
            this.showError('Por favor, complete los campos obligatorios.');
            return;
        }

        const formData = new FormData(form);
        const id = formData.get('id');
        const method = id ? 'PUT' : 'POST';

        try {
            const res = await this.fetchData(this.api.transferencias, method, formData);
            if (res.success) {
                this.showSuccess(res.message);
                this.modal.hide();
                this.loadInitialData(); // Recargar
            } else {
                this.showError(res.message);
            }
        } catch (error) {
            this.showError('Error al guardar la transferencia.');
            console.error('Error guardando transferencia:', error);
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

window.TransferenciasView = TransferenciasView;
