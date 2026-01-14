let vehiculoActual = null;

function cargarFichaVehiculo(id) {
    vehiculoActual = null;

    Promise.all([
        fetch(`api/vehiculos.php?id=${id}`).then(r => r.json()),
        fetch(`api/asignaciones.php?vehiculo_id=${id}`).then(r => r.json()),
        fetch(`api/multas.php?vehiculo_id=${id}`).then(r => r.json()),
        fetch(`api/mantenimientos.php?vehiculo_id=${id}`).then(r => r.json()),
        fetch(`api/pagos.php?vehiculo_id=${id}`).then(r => r.json()),
        fetch(`api/compras.php?vehiculo_id=${id}`).then(r => r.json()),
        fetch(`api/ventas.php?vehiculo_id=${id}`).then(r => r.json()),
        fetch(`api/transferencias.php?vehiculo_id=${id}`).then(r => r.json())
    ])
    .then(([vehiculo, asignaciones, multas, mantenimientos, pagos, compras, ventas, transferencias]) => {
        if (vehiculo.success && vehiculo.data.length > 0) {
            vehiculoActual = vehiculo.data[0];
            mostrarFicha(vehiculoActual, asignaciones, multas, mantenimientos, pagos, compras, ventas, transferencias);
        } else {
            mostrarError('Vehículo no encontrado');
        }
    })
    .catch(error => {
        console.error('Error cargando ficha:', error);
        mostrarError('Error al cargar los datos del vehículo');
    });
}

function mostrarFicha(vehiculo, asignaciones, multas, mantenimientos, pagos, compras, ventas, transferencias) {
    document.getElementById('ficha-loading').classList.add('d-none');
    document.getElementById('ficha-error').classList.add('d-none');
    document.getElementById('ficha-content').classList.remove('d-none');

    document.getElementById('ficha-patente').textContent = vehiculo.patente || '-';
    document.getElementById('ficha-marca-modelo').textContent = `${vehiculo.marca} ${vehiculo.modelo || ''}`;
    document.getElementById('ficha-anio').textContent = vehiculo.anio || '-';
    document.getElementById('ficha-motor').textContent = vehiculo.motor || '-';
    document.getElementById('ficha-chasis').textContent = vehiculo.chasis || '-';

    // Título DNRPA con enlace
    const tituloDnrpaElement = document.getElementById('ficha-titulo-dnrpa');
    if (vehiculo.titulo_dnrpa && vehiculo.titulo_dnrpa.trim()) {
        const codigo = vehiculo.titulo_dnrpa.trim();
        tituloDnrpaElement.innerHTML = `
            <span style="margin-right: 10px;">${codigo}</span>
            <a href="https://www2.jus.gov.ar/dnrpa-site/#!/consultarTramite"
               target="_blank"
               class="btn btn-sm btn-primary"
               title="Consultar en DNRPA"
               style="font-size: 11px; padding: 2px 8px;">
                🔗 Consultar en DNRPA
            </a>
            <small style="display: block; margin-top: 5px; color: var(--text-secondary);">
                Ingrese el código en el sitio del DNRPA
            </small>
        `;
    } else {
        tituloDnrpaElement.textContent = '-';
    }

    document.getElementById('ficha-titularidad').textContent = vehiculo.titularidad || '-';
    document.getElementById('ficha-km').textContent = vehiculo.kilometraje_actual ? `${vehiculo.kilometraje_actual.toLocaleString()} km` : '-';

    const estadoBadge = getEstadoBadge(vehiculo.estado);
    document.getElementById('ficha-estado').innerHTML = estadoBadge;

    document.getElementById('ficha-vtv').textContent = vehiculo.fecha_vtv ? formatDate(vehiculo.fecha_vtv) : '-';
    document.getElementById('ficha-seguro').textContent = vehiculo.fecha_seguro ? formatDate(vehiculo.fecha_seguro) : '-';
    document.getElementById('ficha-patente-vencimiento').textContent = vehiculo.fecha_patente ? formatDate(vehiculo.fecha_patente) : '-';

    if (compras.success && compras.data.length > 0) {
        const compra = compras.data[0];
        document.getElementById('ficha-compra').textContent = `${formatDate(compra.fecha)} - $${compra.total}`;
    } else {
        document.getElementById('ficha-compra').textContent = '-';
    }

    if (ventas.success && ventas.data.length > 0) {
        const venta = ventas.data[0];
        document.getElementById('ficha-venta').textContent = `${formatDate(venta.fecha)} - $${venta.importe}`;
    } else {
        document.getElementById('ficha-venta').textContent = '-';
    }

    if (transferencias.success && transferencias.data.length > 0) {
        const transf = transferencias.data[transferencias.data.length - 1];
        const estadoTransf = {
            'en_proceso': '<span class="badge bg-warning">En Proceso</span>',
            'completa': '<span class="badge bg-success">Completa</span>',
            'cancelada': '<span class="badge bg-danger">Cancelada</span>'
        }[transf.estado] || transf.estado;

        document.getElementById('ficha-transferencia').innerHTML = `${formatDate(transf.fecha)} - ${estadoTransf}`;
    } else {
        document.getElementById('ficha-transferencia').textContent = '-';
    }

    if (asignaciones.success) {
        renderTablaAsignaciones(asignaciones.data);
    }

    if (multas.success) {
        renderTablaMultas(multas.data);
    }

    if (mantenimientos.success) {
        renderTablaMantenimientos(mantenimientos.data);
    }

    if (pagos.success) {
        renderTablaPagosFicha(pagos.data);
    }

    setupTabs();
}

function renderTablaAsignaciones(asignaciones) {
    const tbody = document.querySelector('#tabla-asignaciones tbody');
    tbody.innerHTML = '';

    if (asignaciones.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Sin asignaciones</td></tr>';
        return;
    }

    asignaciones.forEach(a => {
        const kmRecorridos = a.km_regreso && a.km_salida ? a.km_regreso - a.km_salida : '-';
        const estado = a.fecha_devolucion
            ? '<span class="badge bg-success">Devuelto</span>'
            : '<span class="badge bg-primary">Activo</span>';

        tbody.innerHTML += `
            <tr>
                <td>${a.empleado_nombre || '-'}</td>
                <td>${formatDate(a.fecha_salida)}</td>
                <td>${a.km_salida?.toLocaleString() || '-'}</td>
                <td>${a.fecha_devolucion ? formatDate(a.fecha_devolucion) : '-'}</td>
                <td>${a.km_regreso?.toLocaleString() || '-'}</td>
                <td>${kmRecorridos !== '-' ? kmRecorridos.toLocaleString() + ' km' : '-'}</td>
                <td>${estado}</td>
            </tr>
        `;
    });
}

function renderTablaMultas(multas) {
    const tbody = document.querySelector('#tabla-multas tbody');
    tbody.innerHTML = '';

    if (multas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Sin multas</td></tr>';
        return;
    }

    multas.forEach(m => {
        const estado = m.pagada
            ? '<span class="badge bg-success">Pagada</span>'
            : '<span class="badge bg-warning">Pendiente</span>';

        tbody.innerHTML += `
            <tr>
                <td>${formatDate(m.fecha)}</td>
                <td>${m.empleado_nombre || '-'}</td>
                <td>${m.motivo}</td>
                <td>${m.acta_numero}</td>
                <td>$${parseFloat(m.monto).toFixed(2)}</td>
                <td>${estado}</td>
            </tr>
        `;
    });
}

function renderTablaMantenimientos(mantenimientos) {
    const tbody = document.querySelector('#tabla-mantenimientos tbody');
    tbody.innerHTML = '';

    if (mantenimientos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Sin mantenimientos</td></tr>';
        return;
    }

    mantenimientos.forEach(m => {
        const tipoBadge = m.tipo === 'preventivo'
            ? '<span class="badge bg-info">Preventivo</span>'
            : '<span class="badge bg-warning">Correctivo</span>';

        tbody.innerHTML += `
            <tr>
                <td>${formatDate(m.fecha)}</td>
                <td>${tipoBadge}</td>
                <td>${m.descripcion}</td>
                <td>${m.km ? m.km.toLocaleString() + ' km' : '-'}</td>
                <td>$${parseFloat(m.costo).toFixed(2)}</td>
                <td>${m.proveedor || '-'}</td>
            </tr>
        `;
    });
}

function renderTablaPagosFicha(pagos) {
    const tbody = document.querySelector('#tabla-pagos tbody');
    tbody.innerHTML = '';

    if (pagos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Sin pagos registrados</td></tr>';
        return;
    }

    pagos.forEach(p => {
        const estado = p.pagado
            ? '<span class="badge bg-success">Pagado</span>'
            : '<span class="badge bg-warning">Pendiente</span>';

        tbody.innerHTML += `
            <tr>
                <td>${getTipoPagoBadge(p.tipo)}</td>
                <td>${formatDate(p.fecha_vencimiento)}</td>
                <td>${p.monto ? '$' + parseFloat(p.monto).toFixed(2) : '-'}</td>
                <td>${p.fecha_pago ? formatDate(p.fecha_pago) : '-'}</td>
                <td>${estado}</td>
            </tr>
        `;
    });
}

function setupTabs() {
    document.querySelectorAll('.nav-link[data-tab]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();

            document.querySelectorAll('.nav-link').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const tab = this.dataset.tab;
            document.querySelectorAll('.tab-content').forEach(t => t.classList.add('d-none'));
            document.getElementById(`tab-${tab}`).classList.remove('d-none');
        });
    });
}

function cerrarFicha() {
    if (window.dashboard && window.dashboard.mostrarVehiculos) {
        window.dashboard.mostrarVehiculos();
    } else {
        window.location.href = 'index.php?module=vehiculos';
    }
}

function mostrarError(message) {
    document.getElementById('ficha-loading').classList.add('d-none');
    document.getElementById('ficha-content').classList.add('d-none');
    document.getElementById('ficha-error').classList.remove('d-none');
    document.getElementById('error-message').textContent = message;
}

function getEstadoBadge(estado) {
    const badges = {
        'disponible': '<span class="badge bg-success">Disponible</span>',
        'asignado': '<span class="badge bg-primary">Asignado</span>',
        'mantenimiento': '<span class="badge bg-warning">Mantenimiento</span>',
        'baja': '<span class="badge bg-danger">Baja</span>'
    };
    return badges[estado] || estado;
}

function getTipoPagoBadge(tipo) {
    const badges = {
        'patente': '<span class="badge bg-primary">Patente</span>',
        'seguro': '<span class="badge bg-info">Seguro</span>',
        'otro': '<span class="badge bg-secondary">Otro</span>'
    };
    return badges[tipo] || tipo;
}

function formatDate(dateString) {
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString('es-AR');
}
