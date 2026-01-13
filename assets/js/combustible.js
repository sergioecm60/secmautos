class CombustibleView {
    constructor() {
        this.registros = [];
        this.vehiculos = [];
        this.empleados = [];
    }

    async init() {
        await this.cargarVehiculos();
        await this.cargarEmpleados();
        await this.cargar();
        this.initEventListeners();
    }

    initEventListeners() {
        document.getElementById('carga-vehiculo').addEventListener('change', (e) => this.onVehiculoChange(e));
        document.getElementById('carga-conductor-nombre').addEventListener('change', (e) => this.onEmpleadoChange(e));
    }

    async cargarVehiculos() {
        try {
            const res = await fetch('api/vehiculos.php');
            const data = await res.json();
            if (data.success) {
                this.vehiculos = data.data;
            }
        } catch (error) {
            console.error('Error cargando vehículos:', error);
        }
    }

    async cargarEmpleados() {
        try {
            const res = await fetch('api/empleados.php');
            const data = await res.json();
            if (data.success) {
                this.empleados = data.data;
                // Llenar el datalist de conductores
                const datalist = document.getElementById('lista-conductores');
                if (datalist) {
                    data.data.forEach(emp => {
                        const option = document.createElement('option');
                        option.value = `${emp.nombre} ${emp.apellido}`;
                        option.setAttribute('data-id', emp.id);
                        datalist.appendChild(option);
                    });
                }
            }
        } catch (error) {
            console.error('Error cargando empleados:', error);
        }
    }

    onEmpleadoChange(e) {
        const nombreSeleccionado = e.target.value;
        const empleado = this.empleados.find(emp => `${emp.nombre} ${emp.apellido}` === nombreSeleccionado);

        if (empleado) {
            // Es un empleado seleccionado del datalist
            document.getElementById('carga-empleado-id').value = empleado.id;
            document.getElementById('carga-conductor').value = `${empleado.nombre} ${empleado.apellido}`;
        } else {
            // Es una empresa o texto manual
            document.getElementById('carga-empleado-id').value = '';
            document.getElementById('carga-conductor').value = nombreSeleccionado;
        }
    }

    onVehiculoChange(e) {
        const vehiculoId = e.target.value;
        const vehiculo = this.vehiculos.find(v => v.id == vehiculoId);
        
        if (vehiculo) {
            document.getElementById('carga-vehiculo-id').value = vehiculo.id;
            document.getElementById('carga-patente').value = vehiculo.patente;
            document.getElementById('carga-marca').value = vehiculo.marca;
            document.getElementById('carga-modelo').value = vehiculo.modelo;
            document.getElementById('carga-version').value = vehiculo.anio || '';
            document.getElementById('carga-odometro').value = vehiculo.kilometraje_actual || 0;
            document.getElementById('vehiculo-info').textContent = `${vehiculo.patente} - ${vehiculo.marca} ${vehiculo.modelo} (${vehiculo.anio || 'N/A'})`;
            document.getElementById('odometro-info').textContent = `Odómetro actual del vehículo: ${vehiculo.kilometraje_actual || 0} km. Se usará para calcular km recorridos desde la última carga`;
        } else {
            document.getElementById('carga-vehiculo-id').value = '';
            document.getElementById('vehiculo-info').textContent = '';
            document.getElementById('odometro-info').textContent = 'Se usará para calcular km recorridos desde la última carga';
        }
    }

    onEmpleadoChange(e) {
        const nombreSeleccionado = e.target.value;
        const empleado = this.empleados.find(emp => `${emp.nombre} ${emp.apellido}` === nombreSeleccionado);

        if (empleado) {
            // Es un empleado seleccionado del datalist
            document.getElementById('carga-empleado-id').value = empleado.id;
            document.getElementById('carga-conductor').value = `${empleado.nombre} ${empleado.apellido}`;
        } else {
            // Es una empresa o texto manual
            document.getElementById('carga-empleado-id').value = '';
            document.getElementById('carga-conductor').value = nombreSeleccionado;
        }
    }

    async cargar() {
        try {
            const res = await fetch('combustible/api.php');
            const data = await res.json();

            if (data.success) {
                this.registros = data.data;
                this.renderTabla();
            } else {
                this.mostrarError('Error al cargar registros: ' + data.message);
            }
        } catch (error) {
            console.error('Error cargando registros:', error);
            this.mostrarError('Error de conexión al cargar registros');
        }
    }

    renderTabla(registros = null) {
        const tbody = document.getElementById('tabla-consumo-body');
        const items = registros || this.registros;

        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="12" class="text-center">No hay registros de consumo</td></tr>';
            return;
        }

        tbody.innerHTML = items.map(r => `
            <tr>
                <td>${this.formatFechaHora(r.fecha_carga, r.hora_carga)}</td>
                <td><strong>${r.patente}</strong></td>
                <td>${r.marca} ${r.modelo || '-'}</td>
                <td>${r.conductor || '-'}</td>
                <td>${this.getTipoBadge(r.tipo_comb)}</td>
                <td>${parseFloat(r.litros).toFixed(2)} L</td>
                <td>$${parseFloat(r.precio_litro).toFixed(2)}</td>
                <td>${number_format(r.odometro, 0, '.', '.')}</td>
                <td>${r.km_recorridos !== null ? r.km_recorridos + ' km' : '-'}</td>
                <td>${r.rendimiento !== null ? r.rendimiento + ' km/L' : '-'}</td>
                <td>$${parseFloat(r.costo_total).toFixed(2)}</td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="window.combustibleView.editar(${r.id})" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="window.combustibleView.eliminar(${r.id})" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    nuevaCarga() {
        document.getElementById('modalCargaTitle').textContent = 'Nueva Carga';
        document.getElementById('form-carga').reset();
        document.getElementById('carga-id').value = '';
        document.getElementById('vehiculo-info').textContent = '';

        this.poblarSelectVehiculos();
        // No necesitamos poblarSelectEmpleados(), ya están en el datalist

        const modalElement = document.getElementById('modalCargaCombustible');
        const modalInstance = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
        modalInstance.show();
    }

    poblarSelectVehiculos() {
        const select = document.getElementById('carga-vehiculo');
        select.innerHTML = '<option value="">Seleccione un vehículo</option>';
        this.vehiculos.forEach(v => {
            select.innerHTML += `<option value="${v.id}" data-patente="${v.patente}" data-marca="${v.marca}" data-modelo="${v.modelo}" data-anio="${v.anio || ''}" data-odometro="${v.kilometraje_actual || 0}">${v.patente} - ${v.marca} ${v.modelo} (${v.anio || 'N/A'})</option>`;
        });
    }

    poblarSelectEmpleados() {
        // Ya no se usa, los empleados están en el datalist
        // Se llenan en cargarEmpleados()
    }

    editar(id) {
        const registro = this.registros.find(r => r.id == id);
        if (!registro) {
            this.mostrarError('Registro no encontrado');
            return;
        }

        document.getElementById('modalCargaTitle').textContent = 'Editar Carga';
        document.getElementById('carga-id').value = registro.id;

        this.poblarSelectVehiculos();
        // No necesitamos poblarSelectEmpleados(), ya están en el datalist

        document.getElementById('carga-vehiculo').value = '';
        document.getElementById('carga-conductor-nombre').value = '';

        const vehiculo = this.vehiculos.find(v => v.patente === registro.patente);
        if (vehiculo) {
            document.getElementById('carga-vehiculo').value = vehiculo.id;
            document.getElementById('carga-vehiculo-id').value = vehiculo.id;
            document.getElementById('carga-patente').value = vehiculo.patente;
            document.getElementById('carga-marca').value = vehiculo.marca;
            document.getElementById('carga-modelo').value = vehiculo.modelo;
            document.getElementById('carga-version').value = vehiculo.anio || '';
        }

        // Buscar si es un empleado o una empresa
        const empleado = this.empleados.find(e => `${e.nombre} ${e.apellido}` === registro.conductor);
        if (empleado) {
            document.getElementById('carga-empleado-id').value = empleado.id;
            document.getElementById('carga-conductor-nombre').value = `${empleado.nombre} ${empleado.apellido}`;
        } else {
            // Es una empresa u otro texto manual
            document.getElementById('carga-empleado-id').value = '';
            document.getElementById('carga-conductor-nombre').value = registro.conductor || '';
        }

        document.getElementById('carga-fecha').value = registro.fecha_carga || '';
        document.getElementById('carga-hora').value = registro.hora_carga || '';
        document.getElementById('carga-lugar').value = registro.lugar_carga || '';
        document.getElementById('carga-tipo').value = registro.tipo_comb || 'Nafta';
        document.getElementById('carga-litros').value = registro.litros || '';
        document.getElementById('carga-precio').value = registro.precio_litro || '';
        document.getElementById('carga-odometro').value = registro.odometro || '';
        document.getElementById('carga-observaciones').value = registro.observaciones || '';
        document.getElementById('vehiculo-info').textContent = '';

        const modalElement = document.getElementById('modalCargaCombustible');
        const modalInstance = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
        modalInstance.show();
    }

    async guardar() {
        const form = document.getElementById('form-carga');

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const id = document.getElementById('carga-id').value;
        const method = id ? 'PUT' : 'POST';
        const url = 'combustible/api.php';

        // Asegurarnos de que el conductor se guarde correctamente
        const nombreConductor = document.getElementById('carga-conductor-nombre').value;
        document.getElementById('carga-conductor').value = nombreConductor;

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
                const modalElement = document.getElementById('modalCargaCombustible');
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) {
                    modalInstance.hide();
                }
                await this.cargar();
            } else {
                this.mostrarError(data.message);
            }
        } catch (error) {
            console.error('Error guardando carga:', error);
            this.mostrarError('Error de conexión al guardar carga');
        }
    }

    async eliminar(id) {
        if (!confirm('¿Está seguro de eliminar este registro de consumo?')) {
            return;
        }

        try {
            const params = new URLSearchParams({
                id: id,
                csrf_token: document.querySelector('meta[name="csrf-token"]').content || ''
            }).toString();

            const res = await fetch('combustible/api.php', {
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
            console.error('Error eliminando registro:', error);
            this.mostrarError('Error de conexión al eliminar registro');
        }
    }

    filtrar() {
        const patente = document.getElementById('filtro-patente').value.toUpperCase();
        const fechaDesde = document.getElementById('filtro-fecha-desde').value;
        const fechaHasta = document.getElementById('filtro-fecha-hasta').value;

        let filtrados = this.registros;

        if (patente) {
            filtrados = filtrados.filter(r => r.patente.includes(patente));
        }

        if (fechaDesde) {
            filtrados = filtrados.filter(r => r.fecha_carga >= fechaDesde);
        }

        if (fechaHasta) {
            filtrados = filtrados.filter(r => r.fecha_carga <= fechaHasta);
        }

        this.renderTabla(filtrados);
    }

    exportarRegistros() {
        const patente = document.getElementById('filtro-patente').value.toUpperCase();
        const fechaDesde = document.getElementById('filtro-fecha-desde').value;
        const fechaHasta = document.getElementById('filtro-fecha-hasta').value;

        let url = 'combustible/api.php?exportar=1';

        if (patente) {
            url += '&patente=' + encodeURIComponent(patente);
        }
        if (fechaDesde) {
            url += '&fecha_desde=' + encodeURIComponent(fechaDesde);
        }
        if (fechaHasta) {
            url += '&fecha_hasta=' + encodeURIComponent(fechaHasta);
        }

        window.open(url, '_blank');
    }

    formatFechaHora(fecha, hora) {
        const fechaObj = new Date(fecha + 'T' + hora);
        return fechaObj.toLocaleDateString('es-AR') + ' ' + fechaObj.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
    }

    getTipoBadge(tipo) {
        const badges = {
            'Nafta': '<span class="badge bg-primary">Nafta</span>',
            'Nafta Super': '<span class="badge bg-primary">Nafta Super</span>',
            'Nafta Premium': '<span class="badge bg-primary">Nafta Premium</span>',
            'Diesel': '<span class="badge bg-warning">Diesel</span>',
            'Diesel Comun': '<span class="badge bg-warning">Diesel Común</span>',
            'Diesel Premium': '<span class="badge bg-warning">Diesel Premium</span>',
            'Otro': '<span class="badge bg-secondary">Otro</span>'
        };
        return badges[tipo] || tipo;
    }

    mostrarExito(mensaje) {
        alert('✅ ' + mensaje);
    }

    mostrarError(mensaje) {
        alert('❌ ' + mensaje);
    }
}

window.CombustibleView = CombustibleView;
