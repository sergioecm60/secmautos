let pagosData = [];
let vehiculosDataPagos = [];
let multasDataPagos = [];
let tipoFiltroActual = '';

// Función para filtrar por tipo mediante pestañas
function filtrarPorTipo(tipo) {
    tipoFiltroActual = tipo;

    // Actualizar estado activo de pestañas
    document.querySelectorAll('.nav-pills .nav-link').forEach(btn => {
        btn.classList.remove('active');
    });

    if (tipo === '') {
        document.getElementById('pills-todos-tab').classList.add('active');
    } else {
        document.getElementById(`pills-${tipo}-tab`).classList.add('active');
    }

    filtrarPagos();
}

// Función principal de filtrado
function filtrarPagos() {
    const patente = document.getElementById('filtro-patente').value.toLowerCase();
    const pagado = document.getElementById('filtro-pagado').value;
    const fechaDesde = document.getElementById('filtro-fecha-desde').value;
    const fechaHasta = document.getElementById('filtro-fecha-hasta').value;

    let filtrados = pagosData;

    // Filtro por tipo (pestaña activa)
    if (tipoFiltroActual) {
        filtrados = filtrados.filter(p => p.tipo === tipoFiltroActual);
    }

    // Filtro por patente
    if (patente) {
        filtrados = filtrados.filter(p => p.patente && p.patente.toLowerCase().includes(patente));
    }

    // Filtro por estado de pago
    if (pagado !== '') {
        const estadoPagado = pagado === '1';
        filtrados = filtrados.filter(p => p.pagado == estadoPagado);
    }

    // Filtro por rango de fechas
    if (fechaDesde) {
        filtrados = filtrados.filter(p => p.fecha_vencimiento >= fechaDesde);
    }

    if (fechaHasta) {
        filtrados = filtrados.filter(p => p.fecha_vencimiento <= fechaHasta);
    }

    renderTablaPagos(filtrados);
}

// Cargar datos iniciales
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
            actualizarSelectVehiculo();
        }
    } catch (error) {
        console.error('Error cargando vehículos:', error);
    }
}

async function cargarMultas() {
    try {
        const res = await fetch('api/multas.php?pendientes=1');
        const data = await res.json();

        if (data.success) {
            multasDataPagos = data.data;
        }
    } catch (error) {
        console.error('Error cargando multas:', error);
    }
}

function actualizarSelectVehiculo() {
    const select = document.getElementById('pago-vehiculo');
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

        let detalle = '-';

        // Construir detalle según tipo
        switch(p.tipo) {
            case 'patente':
                detalle = 'Impuesto Automotor';
                if (p.observaciones && p.observaciones.includes('Cuota')) {
                    detalle += ' - ' + p.observaciones.split(' - ')[0];
                }
                break;
            case 'seguro':
                detalle = (p.aseguradora || '') + (p.poliza_numero ? ' • Póliza ' + p.poliza_numero : '');
                if (p.estado_poliza) {
                    detalle += ` <span class="badge bg-${getColorEstadoPoliza(p.estado_poliza)}">${p.estado_poliza}</span>`;
                }
                break;
            case 'vtv':
                detalle = 'Verificación Técnica';
                if (p.observaciones && p.observaciones.includes('Resultado:')) {
                    const resultado = p.observaciones.match(/Resultado: (\w+)/)?.[1];
                    detalle += ` <span class="badge bg-${getColorResultadoVTV(resultado)}">${resultado || ''}</span>`;
                }
                break;
            case 'multa':
                const empleado = p.nombre_empleado ? p.nombre_empleado + ' ' + (p.apellido_empleado || '') : '-';
                detalle = empleado !== '-' ? 'Responsable: ' + empleado : 'Multa';
                if (p.motivo_multa) {
                    detalle += ' • ' + p.motivo_multa.substring(0, 50);
                }
                break;
            case 'servicios':
            case 'otro':
                detalle = p.observaciones || p.comprobante || '-';
                break;
        }

        const vencimiento = formatDate(p.fecha_vencimiento);
        const fechaPago = p.fecha_pago ? formatDate(p.fecha_pago) : '-';
        const esVencido = !p.pagado && new Date(p.fecha_vencimiento) < new Date();
        const vencimientoClass = esVencido ? 'text-danger fw-bold' : '';

        tbody.innerHTML += `
            <tr>
                <td><strong>${p.patente}</strong></td>
                <td>${getTipoPagoBadge(p.tipo)}</td>
                <td>${detalle}</td>
                <td class="${vencimientoClass}">${vencimiento}</td>
                <td>$${parseFloat(p.monto).toFixed(2)}</td>
                <td>${estadoBadge}</td>
                <td>${fechaPago}</td>
                <td>
                    ${!p.pagado ? `
                        <button class="btn btn-sm btn-success" onclick="marcarPagado(${p.id})" title="Marcar como pagado">
                            <i class="bi bi-check-circle"></i>
                        </button>
                    ` : ''}
                    <button class="btn btn-sm btn-info" onclick="verDetalles(${p.id})" title="Ver detalles">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="eliminarPago(${p.id})" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
}

function getTipoPagoBadge(tipo) {
    const badges = {
        'patente': '<span class="badge bg-primary">🏛️ Patente</span>',
        'seguro': '<span class="badge bg-info">🛡️ Seguro</span>',
        'vtv': '<span class="badge bg-success">🔍 VTV</span>',
        'multa': '<span class="badge bg-danger">⚠️ Multa</span>',
        'servicios': '<span class="badge bg-warning">🛠️ Servicios</span>',
        'otro': '<span class="badge bg-secondary">📦 Otro</span>'
    };
    return badges[tipo] || tipo;
}

function getColorEstadoPoliza(estado) {
    return {
        'vigente': 'success',
        'vencida': 'danger',
        'cancelada': 'secondary'
    }[estado] || 'secondary';
}

function getColorResultadoVTV(resultado) {
    return {
        'aprobada': 'success',
        'condicional': 'warning',
        'rechazada': 'danger'
    }[resultado] || 'secondary';
}

// Mostrar/ocultar campos según tipo de pago
function toggleCamposPorTipo() {
    const tipo = document.getElementById('pago-tipo').value;

    // Ocultar todos los campos específicos
    document.querySelectorAll('.campos-tipo').forEach(campo => {
        campo.style.display = 'none';
    });

    // Mostrar campos según tipo
    switch(tipo) {
        case 'patente':
            document.getElementById('campos-patente').style.display = 'block';
            break;
        case 'seguro':
            document.getElementById('campos-seguro').style.display = 'block';
            break;
        case 'vtv':
            document.getElementById('campos-vtv').style.display = 'block';
            break;
        case 'multa':
            document.getElementById('campos-multa').style.display = 'block';
            cargarSelectMultas();
            break;
        case 'servicios':
        case 'otro':
            document.getElementById('campos-general').style.display = 'block';
            break;
    }
}

// Cargar multas pendientes en el select
function cargarSelectMultas() {
    const select = document.getElementById('select-multa');
    select.innerHTML = '<option value="">Seleccionar multa pendiente...</option>';

    multasDataPagos.forEach(m => {
        if (!m.pagada) {
            select.innerHTML += `<option value="${m.id}">${m.patente} - ${m.motivo.substring(0, 50)} - $${m.monto}</option>`;
        }
    });
}

// Toggle fecha de pago
function toggleFechaPago() {
    const pagado = document.getElementById('pago-pagado').checked;
    const campoFechaPago = document.getElementById('campo-fecha-pago');

    if (pagado) {
        campoFechaPago.style.display = 'block';
        // Auto-llenar con fecha de hoy
        const hoy = new Date().toISOString().split('T')[0];
        document.getElementById('pago-fecha-pago').value = hoy;
    } else {
        campoFechaPago.style.display = 'none';
        document.getElementById('pago-fecha-pago').value = '';
    }
}

// Abrir modal
function abrirModalPago() {
    document.getElementById('form-pago').reset();
    document.getElementById('modalPagoTitle').textContent = 'Nuevo Pago';
    document.getElementById('pago-id').value = '';

    // Ocultar todos los campos específicos
    document.querySelectorAll('.campos-tipo').forEach(campo => {
        campo.style.display = 'none';
    });

    document.getElementById('campo-fecha-pago').style.display = 'none';

    new bootstrap.Modal(document.getElementById('modalPago')).show();
}

// Guardar pago
async function guardarPago() {
    const form = document.getElementById('form-pago');

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    const tipo = formData.get('tipo');

    // Consolidar fecha_vencimiento según el tipo
    let fechaVencimiento = null;
    switch(tipo) {
        case 'patente':
            fechaVencimiento = formData.get('fecha_vencimiento_patente');
            break;
        case 'seguro':
            fechaVencimiento = formData.get('fecha_vencimiento_seguro');
            break;
        case 'vtv':
            fechaVencimiento = formData.get('fecha_vencimiento_vtv');
            break;
        case 'multa':
            fechaVencimiento = formData.get('fecha_vencimiento_multa');
            break;
        case 'servicios':
        case 'otro':
            fechaVencimiento = formData.get('fecha_vencimiento_general');
            break;
    }

    if (!fechaVencimiento) {
        alert('❌ La fecha de vencimiento es obligatoria');
        return;
    }

    // Crear FormData final
    const finalFormData = new FormData();
    finalFormData.append('csrf_token', formData.get('csrf_token'));
    finalFormData.append('vehiculo_id', formData.get('vehiculo_id'));
    finalFormData.append('tipo', tipo);
    finalFormData.append('fecha_vencimiento', fechaVencimiento);
    finalFormData.append('monto', formData.get('monto'));
    finalFormData.append('comprobante', formData.get('comprobante') || '');
    finalFormData.append('pagado', formData.get('pagado') ? '1' : '0');
    finalFormData.append('fecha_pago', formData.get('fecha_pago') || '');

    // Agregar observaciones con datos específicos
    let observaciones = formData.get('observaciones') || '';

    switch(tipo) {
        case 'patente':
            const cuota = formData.get('cuota_patente');
            observaciones = `Cuota: ${cuota}. ${observaciones}`;
            break;
        case 'seguro':
            finalFormData.append('aseguradora', formData.get('aseguradora') || '');
            finalFormData.append('poliza_numero', formData.get('poliza_numero') || '');
            finalFormData.append('fecha_inicio', formData.get('fecha_inicio') || '');
            finalFormData.append('estado_poliza', formData.get('estado_poliza') || 'vigente');
            break;
        case 'vtv':
            const resultado = formData.get('resultado_vtv');
            const oblea = formData.get('numero_oblea_vtv');
            const fechaInspeccion = formData.get('fecha_inspeccion_vtv');
            observaciones = `Resultado: ${resultado}. Oblea: ${oblea || 'N/A'}. Inspección: ${fechaInspeccion || 'N/A'}. ${observaciones}`;
            break;
        case 'multa':
            const multaId = formData.get('multa_id');
            if (multaId) {
                finalFormData.append('multa_id', multaId);
            }
            const numeroActa = formData.get('numero_acta_multa');
            if (numeroActa) {
                observaciones = `Acta: ${numeroActa}. ${observaciones}`;
            }
            break;
        case 'servicios':
        case 'otro':
            const descripcion = formData.get('descripcion_servicio');
            if (descripcion) {
                observaciones = `${descripcion}. ${observaciones}`;
            }
            break;
    }

    finalFormData.append('observaciones', observaciones.trim());

    try {
        const res = await fetch('api/pagos.php', {
            method: 'POST',
            body: finalFormData
        });

        const data = await res.json();

        if (data.success) {
            alert('✅ Pago guardado exitosamente');
            bootstrap.Modal.getInstance(document.getElementById('modalPago')).hide();
            cargarPagos();
        } else {
            alert('❌ Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error guardando pago:', error);
        alert('❌ Error al guardar el pago');
    }
}

// Marcar como pagado
async function marcarPagado(id) {
    if (!confirm('¿Marcar este pago como pagado?')) {
        return;
    }

    const fechaPago = prompt('Fecha de pago (YYYY-MM-DD):', new Date().toISOString().split('T')[0]);
    if (!fechaPago) return;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const res = await fetch('api/pagos.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                id: id,
                pagado: 1,
                fecha_pago: fechaPago,
                csrf_token: csrfToken
            })
        });

        const data = await res.json();

        if (data.success) {
            alert('✅ ' + data.message);
            cargarPagos();
        } else {
            alert('❌ Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error marcando pago:', error);
        alert('❌ Error al marcar el pago');
    }
}

// Ver detalles
function verDetalles(id) {
    const pago = pagosData.find(p => p.id === id);
    if (!pago) return;

    let detalles = `Patente: ${pago.patente || "No especificado"}\n`;
    detalles += `Tipo: ${pago.tipo || "No especificado"}\n`;
    detalles += `Vencimiento: ${pago.fecha_vencimiento ? formatDate(pago.fecha_vencimiento) : "No especificado"}\n`;
    detalles += `Monto: ${pago.monto ? "$" + parseFloat(pago.monto).toFixed(2) : "No especificado"}\n`;
    detalles += `Estado: ${pago.pagado ? "Pagado" : "Pendiente"}\n`;
    detalles += `Fecha de pago: ${pago.fecha_pago ? formatDate(pago.fecha_pago) : "No pagado"}\n`;
    detalles += `Comprobante: ${pago.comprobante || "No especificado"}\n`;

    if (pago.tipo === 'seguro') {
        detalles += `\nAseguradora: ${pago.aseguradora || "N/A"}\n`;
        detalles += `Póliza: ${pago.poliza_numero || "N/A"}\n`;
        detalles += `Estado póliza: ${pago.estado_poliza || "N/A"}\n`;
    }

    detalles += `\nObservaciones: ${pago.observaciones || "Sin observaciones"}`;

    alert(detalles);
}

// Eliminar pago
async function eliminarPago(id) {
    if (!confirm('¿Está seguro de eliminar este pago?')) {
        return;
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const res = await fetch('api/pagos.php', {
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
            alert('✅ Pago eliminado correctamente');
            cargarPagos();
        } else {
            alert('❌ Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error eliminando pago:', error);
        alert('❌ Error al eliminar el pago');
    }
}

function formatDate(dateString) {
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString('es-AR');
}

// Inicialización automática cuando se carga el módulo
setTimeout(() => {
    if (document.getElementById('tabla-pagos')) {
        cargarVehiculos();
        cargarMultas();
        cargarPagos();
    }
}, 100);
