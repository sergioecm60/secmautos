let vehiculosDataReportes = [];

async function cargarVehiculosReportes() {
    try {
        const res = await fetch('api/vehiculos.php');
        const data = await res.json();

        if (data.success) {
            vehiculosDataReportes = data.data;
            actualizarSelectVehiculos();
        }
    } catch (error) {
        console.error('Error cargando vehículos:', error);
        alert('Error al cargar vehículos');
    }
}

function actualizarSelectVehiculos() {
    const select = document.getElementById('reporte-vehiculo');
    if (!select) return;

    select.innerHTML = '<option value="">Seleccionar vehículo...</option>';

    vehiculosDataReportes.forEach(v => {
        select.innerHTML += `<option value="${v.id}">${v.patente} - ${v.marca} ${v.modelo}</option>`;
    });

    select.addEventListener('change', function() {
        document.getElementById('btn-domino').disabled = !this.value;
    });
}

async function generarListadoGCBA() {
    const estado = document.getElementById('excel-estado').value;

    const params = new URLSearchParams();
    if (estado !== 'todos') {
        params.append('estado', estado);
    }

    try {
        const res = await fetch(`api/reportes/listado_gcba.php?${params}`);
        if (!res.ok) {
            throw new Error('Error al generar reporte');
        }

        const blob = await res.blob();
        const url = window.URL.createObjectURL(blob);
        window.open(url, '_blank');
    } catch (error) {
        console.error('Error generando listado:', error);
        alert('Error al generar el listado: ' + error.message);
    }
}

function generarInformeDominio() {
    const vehiculoId = document.getElementById('reporte-vehiculo').value;

    if (!vehiculoId) {
        alert('Por favor selecciona un vehículo');
        return;
    }

    const url = `api/reportes/pdf_dominio.php?vehiculo_id=${vehiculoId}`;
    window.open(url, '_blank');
}

async function generarReporteMultas() {
    try {
        const res = await fetch('api/multas.php');
        const data = await res.json();

        if (!data.success) {
            throw new Error(data.message);
        }

        const multas = data.data;

        const html = generarReporteMultasHTML(multas);

        const blob = new Blob([html], { type: 'text/html' });
        const url = window.URL.createObjectURL(blob);
        window.open(url, '_blank');

        setTimeout(() => {
            alert('Reporte de multas generado en la nueva pestaña. Usa el botón para imprimir o guardar como PDF.');
        }, 500);
    } catch (error) {
        console.error('Error generando reporte multas:', error);
        alert('Error al generar el reporte: ' + error.message);
    }
}

function generarReporteMultasHTML(multas) {
    const porEmpleado = {};

    multas.forEach(m => {
        const empleado = m.empleado_nombre || 'Sin asignar';
        if (!porEmpleado[empleado]) {
            porEmpleado[empleado] = { count: 0, total: 0, pendientes: 0, pagadas: 0 };
        }
        porEmpleado[empleado].count++;
        porEmpleado[empleado].total += parseFloat(m.monto) || 0;
        if (m.pagada) {
            porEmpleado[empleado].pagadas++;
        } else {
            porEmpleado[empleado].pendientes++;
        }
    });

    let rows = '';
    for (const [empleado, datos] of Object.entries(porEmpleado)) {
        rows += `
            <tr>
                <td>${empleado}</td>
                <td>${datos.count}</td>
                <td>$${datos.total.toFixed(2)}</td>
                <td>${datos.pendientes}</td>
                <td>${datos.pagadas}</td>
            </tr>
        `;
    }

    return `
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Reporte de Multas por Empleado</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { text-align: center; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                th { background: #f5f5f5; }
                .print-btn { position: fixed; top: 20px; right: 20px; padding: 10px 20px; background: #333; color: white; border: none; cursor: pointer; }
                @media print { .print-btn { display: none; } body { margin: 0; } }
            </style>
        </head>
        <body>
            <button class="print-btn" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
            <h1>📊 Reporte de Multas por Empleado</h1>
            <p>Fecha: ${new Date().toLocaleDateString('es-AR')}</p>
            <table>
                <tr><th>Empleado</th><th>Cantidad</th><th>Total Multas</th><th>Pendientes</th><th>Pagadas</th></tr>
                ${rows}
            </table>
        </body>
        </html>
    `;
}

async function generarReporteVencimientos() {
    try {
        const res = await fetch('api/vencimientos.php');
        const data = await res.json();

        if (!data.success) {
            throw new Error(data.message);
        }

        const vencimientos = data.data;

        if (vencimientos.length === 0) {
            alert('No hay vencimientos próximos para reportar');
            return;
        }

        const html = generarReporteVencimientosHTML(vencimientos);

        const blob = new Blob([html], { type: 'text/html' });
        const url = window.URL.createObjectURL(blob);
        window.open(url, '_blank');

        setTimeout(() => {
            alert('Reporte de vencimientos generado en la nueva pestaña. Usa el botón para imprimir o guardar como PDF.');
        }, 500);
    } catch (error) {
        console.error('Error generando reporte vencimientos:', error);
        alert('Error al generar el reporte: ' + error.message);
    }
}

function generarReporteVencimientosHTML(vencimientos) {
    const rows = vencimientos.map(v => `
        <tr>
            <td>${v.patente}</td>
            <td>${v.marca} ${v.modelo}</td>
            <td>${v.tipo_vencimiento}</td>
            <td>${v.fecha_vencimiento}</td>
            <td>${v.dias_restantes} días</td>
        </tr>
    `).join('');

    return `
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Vencimientos del Mes</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { text-align: center; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                th { background: #f5f5f5; }
                .urgente { color: #d9534f; font-weight: bold; }
                .print-btn { position: fixed; top: 20px; right: 20px; padding: 10px 20px; background: #333; color: white; border: none; cursor: pointer; }
                @media print { .print-btn { display: none; } body { margin: 0; } }
            </style>
        </head>
        <body>
            <button class="print-btn" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
            <h1>📅 Vencimientos del Mes</h1>
            <p>Fecha: ${new Date().toLocaleDateString('es-AR')}</p>
            <table>
                <tr><th>Patente</th><th>Vehículo</th><th>Tipo</th><th>Fecha Vencimiento</th><th>Días Restantes</th></tr>
                ${rows}
            </table>
        </body>
        </html>
    `;
}

async function generarReporteAsignaciones() {
    const fechaDesde = document.getElementById('fecha-desde').value;
    const fechaHasta = document.getElementById('fecha-hasta').value;

    try {
        const res = await fetch('api/asignaciones.php');
        const data = await res.json();

        if (!data.success) {
            throw new Error(data.message);
        }

        let asignaciones = data.data;

        if (fechaDesde) {
            asignaciones = asignaciones.filter(a => a.fecha_asignacion >= fechaDesde);
        }

        if (fechaHasta) {
            asignaciones = asignaciones.filter(a => !a.fecha_devolucion || a.fecha_devolucion <= fechaHasta);
        }

        if (asignaciones.length === 0) {
            alert('No hay asignaciones en el período seleccionado');
            return;
        }

        const html = generarReporteAsignacionesHTML(asignaciones, fechaDesde, fechaHasta);

        const blob = new Blob([html], { type: 'text/html' });
        const url = window.URL.createObjectURL(blob);
        window.open(url, '_blank');

        setTimeout(() => {
            alert('Reporte de asignaciones generado en la nueva pestaña. Usa el botón para imprimir o guardar como PDF.');
        }, 500);
    } catch (error) {
        console.error('Error generando reporte asignaciones:', error);
        alert('Error al generar el reporte: ' + error.message);
    }
}

function generarReporteAsignacionesHTML(asignaciones, desde, hasta) {
    const rows = asignaciones.map(a => `
        <tr>
            <td>${a.empleado_nombre || 'Sin asignar'}</td>
            <td>${a.patente}</td>
            <td>${a.fecha_asignacion}</td>
            <td>${a.km_salida}</td>
            <td>${a.fecha_devolucion || 'Activo'}</td>
            <td>${a.km_regreso || '-'}</td>
        </tr>
    `).join('');

    const titulo = desde || hasta
        ? `Historial de Asignaciones (${desde ? 'desde ' + desde : ''}${hasta ? ' hasta ' + hasta : ''})`
        : 'Historial Completo de Asignaciones';

    return `
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Reporte de Asignaciones</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { text-align: center; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                th { background: #f5f5f5; }
                .print-btn { position: fixed; top: 20px; right: 20px; padding: 10px 20px; background: #333; color: white; border: none; cursor: pointer; }
                @media print { .print-btn { display: none; } body { margin: 0; } }
            </style>
        </head>
        <body>
            <button class="print-btn" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
            <h1>🔧 ${titulo}</h1>
            <p>Fecha: ${new Date().toLocaleDateString('es-AR')}</p>
            <table>
                <tr><th>Empleado</th><th>Patente</th><th>Fecha Asignación</th><th>Km Salida</th><th>Fecha Regreso</th><th>Km Regreso</th></tr>
                ${rows}
            </table>
        </body>
        </html>
    `;
}

cargarVehiculosReportes();
