let autorizacionesData = [];
let empleadosAutorizaciones = [];
let vehiculosAutorizaciones = [];

async function cargarAutorizaciones() {
    try {
        const res = await fetch('api/autorizaciones_manejo.php');
        const data = await res.json();

        if (data.success) {
            autorizacionesData = data.data;
            renderTablaAutorizaciones(autorizacionesData);
        }
    } catch (error) {
        console.error('Error cargando autorizaciones:', error);
    }
}

async function cargarEmpleadosAutorizaciones() {
    try {
        const res = await fetch('api/empleados.php');
        const data = await res.json();

        if (data.success) {
            empleadosAutorizaciones = data.data;
            actualizarSelectEmpleadosAutorizaciones();
        }
    } catch (error) {
        console.error('Error cargando empleados:', error);
    }
}

async function cargarVehiculosAutorizaciones() {
    try {
        const res = await fetch('api/vehiculos.php');
        const data = await res.json();

        if (data.success) {
            vehiculosAutorizaciones = data.data.filter(v => v.estado !== 'baja');
            actualizarSelectVehiculosAutorizaciones();
        }
    } catch (error) {
        console.error('Error cargando vehículos:', error);
    }
}

function actualizarSelectEmpleadosAutorizaciones() {
    const select = document.getElementById('autorizacion-empleado');
    if (!select) return;

    select.innerHTML = '<option value="">Seleccionar empleado</option>';
    empleadosAutorizaciones.forEach(e => {
        select.innerHTML += `<option value="${e.id}">${e.apellido}, ${e.nombre} (DNI: ${e.dni || 'N/A'})</option>`;
    });
}

function actualizarSelectVehiculosAutorizaciones() {
    const select = document.getElementById('autorizacion-vehiculo');
    if (!select) return;

    select.innerHTML = '<option value="">Seleccionar vehículo</option>';
    vehiculosAutorizaciones.forEach(v => {
        select.innerHTML += `<option value="${v.id}">${v.patente} - ${v.marca} ${v.modelo}</option>`;
    });
}

function renderTablaAutorizaciones(autorizaciones) {
    const tbody = document.querySelector('#tabla-autorizaciones tbody');
    tbody.innerHTML = '';

    if (autorizaciones.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No hay autorizaciones registradas</td></tr>';
        return;
    }

    autorizaciones.forEach(a => {
        tbody.innerHTML += `
            <tr>
                <td><strong>${a.nombre} ${a.apellido}</strong></td>
                <td>${a.marca} ${a.modelo}</td>
                <td><span class="badge bg-primary">${a.patente}</span></td>
                <td>${formatDate(a.fecha_otorgamiento)}</td>
                <td>${getBadgeEstadoAutorizacion(a.activa)}</td>
                <td>
                    <button class="btn btn-sm btn-warning btn-edit-autorizacion" data-id="${a.id}" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger btn-delete-autorizacion" data-id="${a.id}" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
}

function getBadgeEstadoAutorizacion(activa) {
    return activa == 1
        ? '<span class="badge bg-success">Activa</span>'
        : '<span class="badge bg-danger">Revocada</span>';
}

function filtrarAutorizaciones() {
    const empleado = document.getElementById('filtro-autorizacion-empleado').value.toLowerCase();
    const patente = document.getElementById('filtro-autorizacion-patente').value.toLowerCase();
    const estado = document.getElementById('filtro-autorizacion-estado').value;

    let filtradas = autorizacionesData;

    if (empleado) {
        filtradas = filtradas.filter(a =>
            (a.nombre + ' ' + a.apellido).toLowerCase().includes(empleado) ||
            (a.apellido + ' ' + a.nombre).toLowerCase().includes(empleado)
        );
    }

    if (patente) {
        filtradas = filtradas.filter(a => a.patente.toLowerCase().includes(patente));
    }

    if (estado !== '') {
        filtradas = filtradas.filter(a => a.activa == estado);
    }

    renderTablaAutorizaciones(filtradas);
}

function abrirModalAutorizacion() {
    document.getElementById('form-autorizacion').reset();
    document.getElementById('autorizacion-id').value = '';
    document.getElementById('autorizacion-fecha').value = new Date().toISOString().split('T')[0];
    document.getElementById('autorizacion-activa').checked = true;
    actualizarSelectEmpleadosAutorizaciones();
    actualizarSelectVehiculosAutorizaciones();
    new bootstrap.Modal(document.getElementById('modalAutorizacion')).show();
}

async function guardarAutorizacion() {
    const form = document.getElementById('form-autorizacion');

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    const id = formData.get('id');
    const method = id ? 'PUT' : 'POST';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    formData.set('csrf_token', csrfToken);

    try {
        const res = await fetch('api/autorizaciones_manejo.php', {
            method: method,
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            alert(data.message);
            bootstrap.Modal.getInstance(document.getElementById('modalAutorizacion')).hide();
            cargarAutorizaciones();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error guardando autorización:', error);
        alert('Error al guardar la autorización');
    }
}

async function editarAutorizacion(id) {
    try {
        const res = await fetch(`api/autorizaciones_manejo.php?id=${id}`);
        const data = await res.json();

        if (data.success) {
            const autorizacion = data.data;
            document.getElementById('autorizacion-id').value = autorizacion.id;
            document.getElementById('autorizacion-empleado').value = autorizacion.empleado_id;
            document.getElementById('autorizacion-vehiculo').value = autorizacion.vehiculo_id;
            document.getElementById('autorizacion-fecha').value = autorizacion.fecha_otorgamiento ? autorizacion.fecha_otorgamiento.split(' ')[0] : '';
            document.getElementById('autorizacion-activa').checked = autorizacion.activa == 1;
            document.getElementById('autorizacion-observaciones').value = autorizacion.observaciones || '';

            new bootstrap.Modal(document.getElementById('modalAutorizacion')).show();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error cargando autorización:', error);
        alert('Error al cargar la autorización');
    }
}

async function eliminarAutorizacion(id) {
    if (!confirm('¿Está seguro de eliminar esta autorización de manejo?')) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    try {
        const res = await fetch('api/autorizaciones_manejo.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                id: id,
                csrf_token: csrfToken
            })
        });
        const data = await res.json();

        if (data.success) {
            alert(data.message);
            cargarAutorizaciones();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error eliminando autorización:', error);
        alert('Error al eliminar la autorización');
    }
}

function formatDate(dateString) {
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString('es-AR');
}

document.addEventListener('DOMContentLoaded', function() {
    cargarAutorizaciones();
    cargarEmpleadosAutorizaciones();
    cargarVehiculosAutorizaciones();

    document.getElementById('btn-nueva-autorizacion').addEventListener('click', abrirModalAutorizacion);
    document.getElementById('btn-guardar-autorizacion').addEventListener('click', guardarAutorizacion);

    document.getElementById('tabla-autorizaciones').addEventListener('click', function(e) {
        const btnEdit = e.target.closest('.btn-edit-autorizacion');
        const btnDelete = e.target.closest('.btn-delete-autorizacion');

        if (btnEdit) {
            editarAutorizacion(btnEdit.dataset.id);
        } else if (btnDelete) {
            eliminarAutorizacion(btnDelete.dataset.id);
        }
    });
});
