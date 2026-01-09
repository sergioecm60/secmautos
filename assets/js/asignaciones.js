class AsignacionesView {
    constructor() {
        this.apiAsignaciones = 'api/asignaciones.php';
        this.apiVehiculos = 'api/vehiculos.php';
        this.apiEmpleados = 'api/empleados.php';
        this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        this.init();
    }

    init() {
        this.cargarDatos();
        this.initEventListeners();
    }

    async cargarDatos() {
        try {
            const [asignaciones, vehiculos, empleados] = await Promise.all([
                this.fetchData(this.apiAsignaciones),
                this.fetchData(this.apiVehiculos + '?estado=disponible'),
                this.fetchData(this.apiEmpleados)
            ]);

            if (asignaciones.success) this.renderAsignaciones(asignaciones.data);
            if (vehiculos.success) this.populateSelect('#asignacion-vehiculo', vehiculos.data, 'id', v => `${v.patente} - ${v.marca} ${v.modelo}`, 'Seleccione un vehículo');
            if (empleados.success) this.populateSelect('#asignacion-empleado', empleados.data, 'id', e => `${e.nombre} ${e.apellido} (${e.dni})`, 'Seleccione un empleado');
            
            this.vehiculosDisponibles = vehiculos.success ? vehiculos.data : [];
        } catch (error) {
            this.mostrarError('Error al cargar datos iniciales.');
            console.error('Error en cargarDatos:', error);
        }
    }

    initEventListeners() {
        document.getElementById('btn-nueva-asignacion').addEventListener('click', () => this.abrirModalAsignacion());
        document.getElementById('btn-guardar-asignacion').addEventListener('click', () => this.guardarAsignacion());
        document.getElementById('btn-confirmar-devolucion').addEventListener('click', () => this.confirmarDevolucion());
        document.getElementById('asignacion-vehiculo').addEventListener('change', (e) => this.actualizarKmSalida(e.target.value));

        // Listener para botones de devolución (delegación de eventos)
        document.getElementById('tabla-asignaciones-activas').addEventListener('click', (e) => {
            if (e.target.closest('.btn-devolver')) {
                const asignacionId = e.target.closest('.btn-devolver').dataset.id;
                this.abrirModalDevolucion(asignacionId);
            }
        });
    }

    renderAsignaciones(data) {
        const activasTbody = document.querySelector('#tabla-asignaciones-activas tbody');
        const historialTbody = document.querySelector('#tabla-historial-asignaciones tbody');
        
        activasTbody.innerHTML = '';
        historialTbody.innerHTML = '';

        this.asignaciones = data; // Guardar los datos para modals
        
        data.forEach(asig => {
            if (asig.fecha_devolucion === null) { // Asignaciones activas
                activasTbody.innerHTML += this.crearFilaActiva(asig);
            } else { // Historial
                historialTbody.innerHTML += this.crearFilaHistorial(asig);
            }
        });
    }
    
    crearFilaActiva(asig) {
        return `
            <tr>
                <td><strong>${asig.patente}</strong> (${asig.marca_modelo})</td>
                <td>${asig.nombre_empleado}</td>
                <td>${this.formatDate(asig.fecha_asignacion)}</td>
                <td>${asig.km_salida.toLocaleString()} km</td>
                <td>${asig.observaciones || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-success btn-devolver" data-id="${asig.id}">
                        <i class="bi bi-arrow-return-left"></i> Devolver
                    </button>
                </td>
            </tr>
        `;
    }

    crearFilaHistorial(asig) {
        const kmRecorridos = (asig.km_regreso - asig.km_salida).toLocaleString();
        return `
            <tr>
                <td>${asig.patente}</td>
                <td>${asig.nombre_empleado}</td>
                <td>${this.formatDate(asig.fecha_asignacion)} al ${this.formatDate(asig.fecha_devolucion)}</td>
                <td>${kmRecorridos} km</td>
            </tr>
        `;
    }

    abrirModalAsignacion() {
        document.getElementById('form-asignacion').reset();
        new bootstrap.Modal(document.getElementById('modalAsignacion')).show();
    }

    actualizarKmSalida(vehiculoId) {
        const vehiculo = this.vehiculosDisponibles.find(v => v.id == vehiculoId);
        const kmInput = document.getElementById('asignacion-km_salida');
        if (vehiculo) {
            kmInput.value = vehiculo.kilometraje_actual;
        } else {
            kmInput.value = '';
        }
    }

    async guardarAsignacion() {
        const form = document.getElementById('form-asignacion');
        const formData = new FormData(form);

        if (!form.checkValidity()) {
            this.mostrarError('Por favor, complete todos los campos requeridos.');
            return;
        }

        try {
            const response = await this.fetchData(this.apiAsignaciones, 'POST', formData);
            if (response.success) {
                this.mostrarExito('Asignación creada correctamente.');
                bootstrap.Modal.getInstance(document.getElementById('modalAsignacion')).hide();
                this.cargarDatos(); // Recargar todo
            } else {
                this.mostrarError(response.message || 'Error al guardar la asignación.');
            }
        } catch (error) {
            this.mostrarError('Error de red al guardar la asignación.');
            console.error('Error en guardarAsignacion:', error);
        }
    }
    
    abrirModalDevolucion(asignacionId) {
        const asignacion = this.asignaciones.find(a => a.id == asignacionId);
        if (!asignacion) return;

        document.getElementById('form-devolucion').reset();
        document.getElementById('devolucion-asignacion-id').value = asignacion.id;
        document.getElementById('devolucion-vehiculo').textContent = `${asignacion.patente} - ${asignacion.marca_modelo}`;
        document.getElementById('devolucion-km-salida').textContent = `${asignacion.km_salida.toLocaleString()} km`;
        document.getElementById('devolucion-km_regreso').min = asignacion.km_salida;

        new bootstrap.Modal(document.getElementById('modalDevolucion')).show();
    }

    async confirmarDevolucion() {
        const form = document.getElementById('form-devolucion');
        const formData = new FormData(form);
        const kmRegreso = parseInt(formData.get('km_regreso'));
        const kmSalida = parseInt(document.getElementById('devolucion-km-salida').textContent.replace(/\D/g,''));

        if (kmRegreso < kmSalida) {
            this.mostrarError('El kilometraje de regreso no puede ser menor al de salida.');
            return;
        }

        try {
            const response = await this.fetchData(this.apiAsignaciones, 'PUT', new URLSearchParams(formData));

            if (response.success) {
                this.mostrarExito('Vehículo devuelto correctamente.');
                bootstrap.Modal.getInstance(document.getElementById('modalDevolucion')).hide();
                this.cargarDatos(); // Recargar todo
            } else {
                this.mostrarError(response.message || 'Error al procesar la devolución.');
            }
        } catch (error) {
            this.mostrarError('Error de red al procesar la devolución.');
            console.error('Error en confirmarDevolucion:', error);
        }
    }


    // --- Utilidades ---
    async fetchData(url, method = 'GET', body = null) {
        const options = { method };
        if (method === 'POST' || method === 'PUT') {
            if (body) {
                // FormData and URLSearchParams both have 'set' which overwrites.
                // This is fine and ensures the token is correctly set.
                body.set('csrf_token', this.csrfToken);
            }
            options.body = body;
        }
        const res = await fetch(url, options);
        return res.json();
    }

    populateSelect(selector, data, valueField, textField, placeholder) {
        const select = document.querySelector(selector);
        select.innerHTML = `<option value="">${placeholder}</option>`;
        data.forEach(item => {
            select.innerHTML += `<option value="${item[valueField]}">${textField(item)}</option>`;
        });
    }
    
    formatDate(dateStr) {
        if (!dateStr) return 'N/A';
        const date = new Date(dateStr);
        return date.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    mostrarError(mensaje) {
        // Implementar con un sistema de notificaciones (ej. Toast)
        alert('Error: ' + mensaje);
    }

    mostrarExito(mensaje) {
        // Implementar con un sistema de notificaciones (ej. Toast)
        alert(mensaje);
    }
}

// Inicializar la vista cuando el módulo se cargue
document.addEventListener('DOMContentLoaded', () => {
    // Esto se podría mejorar para que solo se ejecute cuando el módulo de asignaciones sea visible
    if (document.getElementById('tabla-asignaciones-activas')) {
        new AsignacionesView();
    }
});
