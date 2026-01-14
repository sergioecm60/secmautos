let currentPageLogs = 1;
const logsPerPage = 50;
let filtrosLogs = {};

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('logsTableBody')) {
        cargarUsuariosParaFiltro();
        cargarLogs();
    }
});

function cargarUsuariosParaFiltro() {
    fetch('api/usuarios.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('filtroUsuario');
                data.data.forEach(usuario => {
                    const option = document.createElement('option');
                    option.value = usuario.id;
                    option.textContent = `${usuario.username || usuario.email} - ${usuario.nombre} ${usuario.apellido}`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error al cargar usuarios:', error));
}

function cargarLogs(page = 1) {
    currentPageLogs = page;
    const tbody = document.getElementById('logsTableBody');

    // Construir parámetros de consulta
    const params = new URLSearchParams({
        page: page,
        limit: logsPerPage,
        ...filtrosLogs
    });

    fetch(`api/logs.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarLogs(data.data);
                mostrarPaginacion(data.total, page);
            } else {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--accent-danger);">${data.message}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error al cargar logs:', error);
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--accent-danger);">Error al cargar los logs</td></tr>';
        });
}

function mostrarLogs(logs) {
    const tbody = document.getElementById('logsTableBody');

    if (logs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No hay registros de actividad</td></tr>';
        return;
    }

    tbody.innerHTML = logs.map(log => {
        const fecha = new Date(log.created_at);
        const fechaFormateada = fecha.toLocaleString('es-AR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });

        const usuario = log.usuario_nombre
            ? `${log.usuario_username || log.usuario_email} (${log.usuario_nombre} ${log.usuario_apellido || ''})`.trim()
            : log.usuario_id || 'Sistema';

        const accionClass = getAccionClass(log.accion);
        const entidadBadge = getEntidadBadge(log.entidad);

        return `
            <tr>
                <td>${log.id}</td>
                <td>${fechaFormateada}</td>
                <td>${usuario}</td>
                <td><span class="badge ${accionClass}">${log.accion}</span></td>
                <td>${entidadBadge}</td>
                <td>${log.detalles || '-'}</td>
                <td>${log.ip || '-'}</td>
            </tr>
        `;
    }).join('');
}

function getAccionClass(accion) {
    if (accion.includes('CREAR') || accion.includes('EXITOSO')) return 'badge-success';
    if (accion.includes('ELIMINAR') || accion.includes('FALLIDO')) return 'badge-danger';
    if (accion.includes('EDITAR') || accion.includes('ACTUALIZAR')) return 'badge-warning';
    if (accion.includes('LOGIN') || accion.includes('LOGOUT')) return 'badge-info';
    return 'badge-info';
}

function getEntidadBadge(entidad) {
    if (!entidad) return '-';

    const colores = {
        'AUTH': 'badge-info',
        'USUARIOS': 'badge-primary',
        'VEHICULOS': 'badge-success',
        'MANTENIMIENTOS': 'badge-warning',
        'MULTAS': 'badge-danger',
        'PAGOS': 'badge-success',
        'compras': 'badge-info',
        'ventas': 'badge-warning'
    };

    const color = colores[entidad] || 'badge-info';
    return `<span class="badge ${color}">${entidad}</span>`;
}

function mostrarPaginacion(total, currentPage) {
    const totalPages = Math.ceil(total / logsPerPage);
    const paginacionDiv = document.getElementById('paginacionLogs');

    if (totalPages <= 1) {
        paginacionDiv.innerHTML = '';
        return;
    }

    let html = '<div class="pagination">';

    // Botón anterior
    if (currentPage > 1) {
        html += `<button class="btn btn-secondary" onclick="cargarLogs(${currentPage - 1})">« Anterior</button>`;
    }

    // Páginas
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);

    if (startPage > 1) {
        html += `<button class="btn btn-secondary" onclick="cargarLogs(1)">1</button>`;
        if (startPage > 2) html += '<span>...</span>';
    }

    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === currentPage ? 'btn-primary' : 'btn-secondary';
        html += `<button class="btn ${activeClass}" onclick="cargarLogs(${i})">${i}</button>`;
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += '<span>...</span>';
        html += `<button class="btn btn-secondary" onclick="cargarLogs(${totalPages})">${totalPages}</button>`;
    }

    // Botón siguiente
    if (currentPage < totalPages) {
        html += `<button class="btn btn-secondary" onclick="cargarLogs(${currentPage + 1})">Siguiente »</button>`;
    }

    html += '</div>';
    paginacionDiv.innerHTML = html;
}

function aplicarFiltrosLogs() {
    filtrosLogs = {};

    const usuario = document.getElementById('filtroUsuario').value;
    const accion = document.getElementById('filtroAccion').value;
    const entidad = document.getElementById('filtroEntidad').value;
    const fechaDesde = document.getElementById('filtroFechaDesde').value;
    const fechaHasta = document.getElementById('filtroFechaHasta').value;

    if (usuario) filtrosLogs.usuario_id = usuario;
    if (accion) filtrosLogs.accion = accion;
    if (entidad) filtrosLogs.entidad = entidad;
    if (fechaDesde) filtrosLogs.fecha_desde = fechaDesde;
    if (fechaHasta) filtrosLogs.fecha_hasta = fechaHasta;

    cargarLogs(1);
}

function limpiarFiltrosLogs() {
    document.getElementById('filtroUsuario').value = '';
    document.getElementById('filtroAccion').value = '';
    document.getElementById('filtroEntidad').value = '';
    document.getElementById('filtroFechaDesde').value = '';
    document.getElementById('filtroFechaHasta').value = '';

    filtrosLogs = {};
    cargarLogs(1);
}
