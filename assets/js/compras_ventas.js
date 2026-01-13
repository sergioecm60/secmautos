class ComprasVentasView {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        this.api = {
            compras: 'api/compras.php',
            ventas: 'api/ventas.php',
            vehiculos: 'api/vehiculos.php'
        };
        this.data = {
            compras: [],
            ventas: [],
            vehiculos: []
        };
        this.modals = {};
        this.init();
    }

    init() {
        this.modals.compras = new bootstrap.Modal(document.getElementById('modalCompra'));
        this.modals.ventas = new bootstrap.Modal(document.getElementById('modalVenta'));
        this.loadInitialData();
        this.initEventListeners();
    }

    async loadInitialData() {
        try {
            const [comprasRes, ventasRes, vehiculosRes] = await Promise.all([
                this.fetchData(this.api.compras),
                this.fetchData(this.api.ventas),
                this.fetchData(this.api.vehiculos)
            ]);

            if (comprasRes.success) this.data.compras = comprasRes.data;
            if (ventasRes.success) this.data.ventas = ventasRes.data;
            if (vehiculosRes.success) this.data.vehiculos = vehiculosRes.data;

            this.render();
        } catch (error) {
            this.showError('Error cargando datos iniciales.');
            console.error('Error en loadInitialData:', error);
        }
    }
    
    initEventListeners() {
        document.getElementById('btn-nueva-compra').addEventListener('click', () => this.openModal('compras'));
        document.getElementById('btn-nueva-venta').addEventListener('click', () => this.openModal('ventas'));
        document.getElementById('btn-guardar-compra').addEventListener('click', () => this.save('compras'));
        document.getElementById('btn-guardar-venta').addEventListener('click', () => this.save('ventas'));
        
        // Event delegation for edit buttons
        document.getElementById('tabla-compras').addEventListener('click', e => this.handleTableClick(e, 'compras'));
        document.getElementById('tabla-ventas').addEventListener('click', e => this.handleTableClick(e, 'ventas'));
    }
    
    handleTableClick(event, type) {
        if (event.target.closest('.btn-edit')) {
            const id = event.target.closest('.btn-edit').dataset.id;
            this.openModal(type, id);
        } else if (event.target.closest('.btn-delete')) {
            const id = event.target.closest('.btn-delete').dataset.id;
            this.delete(type, id);
        }
    }

    render() {
        this.renderTable('compras', this.data.compras, ['fecha', 'vehiculo', 'proveedor', 'factura_numero', 'total']);
        this.renderTable('ventas', this.data.ventas, ['fecha', 'vehiculo', 'comprador', 'factura_numero', 'importe']);
        this.populateVehiculosSelects();
    }

    renderTable(type, data, columns) {
        const tbody = document.querySelector(`#tabla-${type} tbody`);
        tbody.innerHTML = data.map(item => `
            <tr>
                <td>${this.formatDate(item.fecha)}</td>
                <td>${item.patente} (${item.marca} ${item.modelo})</td>
                <td>${item[columns[2]]}</td>
                <td>${item.factura_numero || '-'}</td>
                <td>$${parseFloat(item[columns[4]]).toLocaleString('es-AR')}</td>
                <td>
                    <button class="btn btn-sm btn-warning btn-edit" data-id="${item.id}" data-type="${type}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="${item.id}" data-type="${type}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }
    
    populateVehiculosSelects() {
        const createOption = v => `<option value="${v.id}">${v.patente} - ${v.marca} ${v.modelo}</option>`;
        const vehiculosActivos = this.data.vehiculos.filter(v => v.estado !== 'baja');

        const compraSelect = document.querySelector('#form-compras select[name="vehiculo_id"]');
        if (!compraSelect) {
            console.error('Select de compras no encontrado');
            return;
        }
        compraSelect.innerHTML = '<option value="">Seleccione un vehículo</option>' + this.data.vehiculos.map(createOption).join('');

        const ventaSelect = document.querySelector('#form-ventas select[name="vehiculo_id"]');
        if (!ventaSelect) {
            console.error('Select de ventas no encontrado');
            return;
        }
        ventaSelect.innerHTML = '<option value="">Seleccione un vehículo</option>' + vehiculosActivos.map(createOption).join('');
    }

    openModal(type, id = null) {
        const form = document.querySelector(`#form-${type}`);
        if (!form) {
            console.error(`Formulario form-${type} no encontrado`);
            return;
        }
        form.reset();
        document.getElementById(`${type}-id`).value = id || '';

        if (id) {
            const item = this.data[type].find(i => i.id == id);
            Object.keys(item).forEach(key => {
                const input = form.elements[key];
                if (input) {
                    if (input.type === 'date') {
                        input.value = item[key].split(' ')[0];
                    } else {
                        input.value = item[key];
                    }
                }
            });
        }

        this.modals[type].show();
    }

    async delete(type, id) {
        if (!confirm(`¿Está seguro de eliminar este registro de ${type}?`)) return;

        try {
            const res = await this.fetchData(this.api[type], 'DELETE', new URLSearchParams({id}));
            if (res.success) {
                this.showSuccess(res.message);
                this.loadInitialData();
            } else {
                this.showError(res.message);
            }
        } catch (error) {
            this.showError(`Error al eliminar ${type}`);
            console.error(`Error eliminando ${type}:`, error);
        }
    }

    async save(type) {
        const form = document.querySelector(`#form-${type}`);
        if (!form) {
            console.error(`Formulario form-${type} no encontrado`);
            return;
        }
        if (!form.checkValidity()) {
            this.showError('Por favor, complete los campos obligatorios.');
            return;
        }

        const formData = new FormData(form);
        const id = formData.get('id');
        const method = id ? 'PUT' : 'POST';

        try {
            const res = await this.fetchData(this.api[type], method, formData);
            if (res.success) {
                this.showSuccess(res.message);
                this.modals[type].hide();
                this.loadInitialData(); // Recargar todo
            } else {
                this.showError(res.message);
            }
        } catch (error) {
            this.showError(`Error al guardar ${type}.`);
            console.error(`Error guardando ${type}:`, error);
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

window.ComprasVentasView = ComprasVentasView;
