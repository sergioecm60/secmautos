let pagosData = [];
let vehiculosDataPagos = [];

async function cargarPagos() {
    try {
        const res = await fetch('api/pagos.php');
        const data = await res.json();

        if (data.success) {
            pagosData = data.data;
            renderTablaPagos(pagosData);
        }
    } catch (error) {
        console.error('Error cargando pagos:', error);
    }
}

async function cargarVehiculos() {
    try {
        const res = await fetch('api/vehiculos.php');
        const data = await res.json();

        if (data.success) {
            vehiculosDataPagos = data.data;
            actualizarSelectVehiculos();
        }
    } catch (error) {
        console.error('Error cargando vehículos:', error);
    }
}

function actualizarSelectVehiculos() {
    const select = document.querySelector('select[name="vehiculo_id"]');
    const currentValue = select.value;

    select.innerHTML = '<option value="">Seleccionar vehículo</option>';

    vehiculosDataPagos.forEach(v => {
        if (v.estado !== 'baja') {
            select.innerHTML += `<option value="${v.id}">${v.patente} - ${v.marca} ${v.modelo}</option>`;
        }
    });

    if (currentValue) {
        select.value = currentValue;
    }
}

function renderTablaPagos(pagos) {
    const tbody = document.querySelector('#tabla-pagos tbody');
    tbody.innerHTML = '';

    if (pagos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No hay pagos registrados</td></tr>';
        return;
    }

    pagos.forEach(p => {
        const estadoBadge = p.pagado
            ? '<span class="badge bg-success">Pagado</span>'
            : '<span class="badge bg-warning">Pendiente</span>';

        const vencimiento = formatDate(p.fecha_vencimiento);
        const fechaPago = p.fecha_pago ? formatDate(p.fecha_pago) : '-';
        const esVencido = !p.pagado && new Date(p.fecha_vencimiento) < new Date();
        const vencimientoClass = esVencido ? 'text-danger fw-bold' : '';

        tbody.innerHTML += `
            <tr>
                <td><strong>${p.patente}</strong></td>
                <td>${getTipoPagoBadge(p.tipo)}</td>
                <td class="${vencimientoClass}">${vencimiento}</td>
                <td>${p.monto ? '$' + parseFloat(p.monto).toFixed(2) : '-'}</td>
                <td>${estadoBadge}</td>
                <td>${fechaPago}</td>
                <td>${p.comprobante || '-'}</td>
                <td>
                    ${!p.pagado ? `
                        <button class="btn btn-sm btn-success" onclick="marcarPagado(${p.id})" title="Marcar como pagado">
                            <i class="bi bi-check-circle"></i>
                        </button>
                    ` : ''}
                    <button class="btn btn-sm btn-info" onclick="verDetalles(${p.id})" title="Ver detalles">
                        <i class="bi bi-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    });
}

function getTipoPagoBadge(tipo) {
    const badges = {
        'patente': '<span class="badge bg-primary">Patente</span>',
        'seguro': '<span class="badge bg-info">Seguro</span>',
        'servicios': '<span class="badge bg-warning">Servicios</span>',
        'otro': '<span class="badge bg-secondary">Otro</span>'
    };
    return badges[tipo] || tipo;
}

function abrirModalPago() {
    document.getElementById('form-pago').reset();
    document.getElementById('modalPagoTitle').textContent = 'Nuevo Pago';
    actualizarSelectVehiculos();
    new bootstrap.Modal(document.getElementById('modalPago')).show();
}

async function guardarPago() {
    const form = document.getElementById('form-pago');

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);

    try {
        const res = await fetch('api/pagos.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            alert(data.message);
            bootstrap.Modal.getInstance(document.getElementById('modalPago')).hide();
            cargarPagos();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error guardando pago:', error);
        alert('Error al guardar pago');
    }
}

async function marcarPagado(id) {
    if (!confirm('¿Marcar este pago como pagado?')) {
        return;
    }

    try {
        const res = await fetch('api/pagos.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                id: id,
                csrf_token: document.querySelector('[name="csrf_token"]').value
            })
        });
        const data = await res.json();

        if (data.success) {
            alert(data.message);
            cargarPagos();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error marcando pago:', error);
        alert('Error al marcar el pago');
    }
}

function verDetalles(id) {
    const pago = pagosData.find(p => p.id === id);
    if (!pago) return;

    const detalles = `
        Patente: ${pago.patente || 'No especificado'}
        Tipo: ${pago.tipo || 'No especificado'}
        Vencimiento: ${pago.fecha_vencimiento ? formatDate(pago.fecha_vencimiento) : 'No especificado'}
        Monto: ${pago.monto ? `$${parseFloat(pago.monto).toFixed(2)}` : 'No especificado'}
        Estado: ${pago.pagado ? 'Pagado' : 'Pendiente'}
        Fecha de pago: ${pago.fecha_pago ? formatDate(pago.fecha_pago) : 'No pagado'}
        Comprobante: ${pago.comprobante || 'No especificado'}
        Observaciones: ${pago.observaciones || 'Sin observaciones'}
    `;

    alert(detalles);
}

function filtrarPagos() {
    const patente = document.getElementById('filtro-patente').value.toLowerCase();
    const tipo = document.getElementById('filtro-tipo').value;
    const pagado = document.getElementById('filtro-pagado').value;

    let filtrados = pagosData;

    if (patente) {
        filtrados = filtrados.filter(p => p.patente.toLowerCase().includes(patente));
    }

    if (tipo) {
        filtrados = filtrados.filter(p => p.tipo === tipo);
    }

    if (pagado !== '') {
        const estado = pagado === '1';
        filtrados = filtrados.filter(p => p.pagado === estado);
    }

    renderTablaPagos(filtrados);
}

function formatDate(dateString) {
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString('es-AR');
}

cargarPagos();
cargarVehiculos();
