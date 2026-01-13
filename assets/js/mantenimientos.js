class MantenimientosView {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        this.api = {
            mantenimientos: 'api/mantenimientos.php',
            vehiculos: 'api/vehiculos.php',
            talleres: 'api/talleres.php',
            paquetes: 'api/paquetes_mantenimiento.php'
        };
        this.data = {
            mantenimientos: [],
            vehiculos: [],
            talleres: [],
            paquetes: []
        };
        this.modal = null;
    }

    async init() {
        this.modal = new bootstrap.Modal(document.getElementById('modalMantenimiento'));
        await this.loadInitialData();
        this.initEventListeners();
    }

    async loadInitialData() {
        try {
            const [mantenimientosRes, vehiculosRes, talleresRes, paquetesRes] = await Promise.all([
                this.fetchData(this.api.mantenimientos),
                this.fetchData(this.api.vehiculos),
                this.fetchData(this.api.talleres),
                this.fetchData(this.api.paquetes)
            ]);

            if (mantenimientosRes.success) this.data.mantenimientos = mantenimientosRes.data;
            if (vehiculosRes.success) this.data.vehiculos = vehiculosRes.data;
            if (talleresRes.success) this.data.talleres = talleresRes.data;
            if (paquetesRes.success) this.data.paquetes = paquetesRes.data;

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
        this.populateTalleresSelect();
        this.populatePaquetesSelect();
    }

    init() {
        this.modal = new bootstrap.Modal(document.getElementById('modalMantenimiento'));
        this.loadInitialData();
        this.initEventListeners();
    }

    async loadInitialData() {
        try {
            const [mantenimientosRes, vehiculosRes, talleresRes, paquetesRes] = await Promise.all([
                this.fetchData(this.api.mantenimientos),
                this.fetchData(this.api.vehiculos),
                this.fetchData(this.api.talleres),
                this.fetchData(this.api.paquetes)
            ]);

            if (mantenimientosRes.success) this.data.mantenimientos = mantenimientosRes.data;
            if (vehiculosRes.success) this.data.vehiculos = vehiculosRes.data;
            if (talleresRes.success) this.data.talleres = talleresRes.data;
            if (paquetesRes.success) this.data.paquetes = paquetesRes.data;

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
        this.populateTalleresSelect();
        this.populatePaquetesSelect();
    }

    renderTable() {
        const tbody = document.querySelector(`#tabla-mantenimientos tbody`);
        tbody.innerHTML = this.data.mantenimientos.map(item => `
            <tr>
                <td>${this.formatDate(item.fecha)}</td>
                <td><strong>${item.patente || ''}</strong></td>
                <td>${item.nombre_taller || item.proveedor || '-'}</td>
                <td><span class="badge ${item.tipo === 'preventivo' ? 'bg-info' : 'bg-warning'}">${item.tipo}</span></td>
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

    populateTalleresSelect() {
        const select = document.querySelector('#form-mantenimiento select[name="taller_id"]');
        select.innerHTML = '<option value="">Seleccione un taller</option>' + this.data.talleres.map(t => `<option value="${t.id}">${t.nombre}</option>`).join('');
    }

    populatePaquetesSelect() {
        const select = document.querySelector('#form-mantenimiento select[name="paquete_mantenimiento"]');
        select.innerHTML = '<option value="">Seleccionar paquete...</option>';
        
        this.data.paquetes.forEach(p => {
            select.innerHTML += `<option value="${p.codigo}" data-id="${p.id}">${p.codigo} - ${p.nombre}</option>`;
        });
    }

    togglePaquetes() {
        const tipo = document.querySelector('#form-mantenimiento select[name="tipo"]').value;
        const seccionPaquetes = document.getElementById('seccion-paquetes');
        
        if (tipo === 'preventivo') {
            seccionPaquetes.style.display = 'block';
        } else {
            seccionPaquetes.style.display = 'none';
        }
    }

    cargarItemsPaquete() {
        const select = document.querySelector('#form-mantenimiento select[name="paquete_mantenimiento"]');
        const selectedOption = select.options[select.selectedIndex];
        const paqueteId = selectedOption.dataset.id;
        const descripcionTextarea = document.querySelector('#form-mantenimiento textarea[name="descripcion"]');
        const listaItems = document.getElementById('items-paquete-lista');
        const seccionLista = document.getElementById('lista-items-paquete');
        
        if (!paqueteId) {
            seccionLista.style.display = 'none';
            return;
        }

        const paquete = this.data.paquetes.find(p => p.id == paqueteId);
        
        if (paquete && paquete.items && paquete.items.length > 0) {
            seccionLista.style.display = 'block';
            listaItems.innerHTML = paquete.items.map(item => `<li class="list-group-item">${item}</li>`).join('');
            
            descripcionTextarea.value = paquete.items.map((item, index) => `${index + 1}. ${item}`).join('\n');
        } else {
            seccionLista.style.display = 'none';
            descripcionTextarea.value = '';
        }
    }

    openModal(id = null) {
        const form = document.getElementById('form-mantenimiento');
        form.reset();
        document.getElementById('mantenimiento-id').value = id || '';
        
        this.populateVehiculosSelect();
        this.populateTalleresSelect();
        this.populatePaquetesSelect();
        document.getElementById('seccion-paquetes').style.display = 'none';
        document.getElementById('lista-items-paquete').style.display = 'none';
        
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
            
            if (item.tipo === 'preventivo') {
                document.getElementById('seccion-paquetes').style.display = 'block';
                if (item.paquete_mantenimiento) {
                    const paqueteSelect = document.querySelector('#form-mantenimiento select[name="paquete_mantenimiento"]');
                    paqueteSelect.value = item.paquete_mantenimiento;
                    this.cargarItemsPaquete();
                }
            }
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

window.MantenimientosView = MantenimientosView;
window.mantenimientosView = new MantenimientosView();
