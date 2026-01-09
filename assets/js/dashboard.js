// Variable global para CSRF token
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

document.addEventListener('DOMContentLoaded', function() {
    const navBtns = document.querySelectorAll('.nav-btn');
    const modules = document.querySelectorAll('.module');

    navBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            navBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const module = this.dataset.module;
            
            modules.forEach(m => m.classList.remove('active'));
            document.getElementById(`module-${module}`).classList.add('active');
            
            cargarModulo(module);
        });
    });

    cargarDashboard();
});

function cargarDashboard() {
    Promise.all([
        fetch('api/stats.php').then(r => r.json()),
        fetch('api/alertas.php').then(r => r.json()),
        fetch('api/vencimientos.php').then(r => r.json())
    ])
    .then(([stats, alertas, vencimientos]) => {
        renderStats(stats);
        renderAlertas(alertas);
        renderVencimientos(vencimientos);
    })
    .catch(error => console.error('Error cargando dashboard:', error));
}

function renderStats(data) {
    const container = document.getElementById('stats-grid');
    if (!data.success) return;
    
    const stats = [
        { label: 'Total Vehículos', value: data.data.total_vehiculos, icon: '🚗' },
        { label: 'Disponibles', value: data.data.disponibles, icon: '✅' },
        { label: 'Asignados', value: data.data.asignados, icon: '🔄' },
        { label: 'En Mantenimiento', value: data.data.mantenimiento, icon: '🔧' },
        { label: 'Total Empleados', value: data.data.total_empleados, icon: '👥' },
        { label: 'Alertas Activas', value: data.data.alertas_activas, icon: '⚠️' },
        { label: 'Multas Pendientes', value: data.data.multas_pendientes, icon: '💰' },
        { label: 'Mantenimientos Programados', value: data.data.mantenimientos_programados, icon: '📅' }
    ];
    
    container.innerHTML = stats.map(stat => `
        <div class="stat-card">
            <div style="font-size: 2em;">${stat.icon}</div>
            <h4>${stat.value}</h4>
            <p>${stat.label}</p>
        </div>
    `).join('');
}

function renderAlertas(data) {
    const container = document.getElementById('alertas-lista');
    if (!data.success || data.data.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">No hay alertas activas</p>';
        return;
    }
    
    const rows = data.data.map(alerta => `
        <tr>
            <td>${alerta.patente || 'N/A'}</td>
            <td>${alerta.tipo_alerta}</td>
            <td>${alerta.mensaje}</td>
            <td>${alerta.fecha_alerta}</td>
            <td><span class="badge badge-warning">Pendiente</span></td>
        </tr>
    `).join('');
    
    container.innerHTML = `
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Patente</th>
                        <th>Tipo</th>
                        <th>Mensaje</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    `;
}

function renderVencimientos(data) {
    const container = document.getElementById('vencimientos-lista');
    if (!data.success || data.data.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">No hay vencimientos próximos</p>';
        return;
    }
    
    const rows = data.data.map(venc => `
        <tr class="fila-pendiente">
            <td>${venc.patente}</td>
            <td>${venc.marca} ${venc.modelo}</td>
            <td>${venc.tipo_vencimiento}</td>
            <td>${venc.fecha_vencimiento}</td>
            <td>${venc.dias_restantes} días</td>
        </tr>
    `).join('');
    
    container.innerHTML = `
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Patente</th>
                        <th>Vehículo</th>
                        <th>Tipo</th>
                        <th>Vence</th>
                        <th>Restantes</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    `;
}

function cargarModulo(modulo) {
    switch(modulo) {
        case 'vehiculos':
            cargarVehiculos();
            break;
        case 'empleados':
            cargarEmpleados();
            break;
        case 'asignaciones':
            cargarAsignaciones();
            break;
        case 'multas':
            cargarMultas();
            break;
        case 'mantenimientos':
            cargarMantenimientos();
            break;
        case 'pagos':
            cargarPagos();
            break;
        case 'reportes':
            cargarReportes();
            break;
    }
}

function cargarVehiculos() {
    const container = document.getElementById('module-vehiculos');

    // Verificar si ya está cargado
    if (container.innerHTML.trim() !== '') {
        if (window.vehiculosView) {
            window.vehiculosView.cargar();
        }
        return;
    }

    // Cargar el HTML del módulo
    fetch('modules/vehiculos.html')
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;

            // Cargar el script JS del módulo
            if (!document.querySelector('script[src="assets/js/vehiculos.js"]')) {
                const script = document.createElement('script');
                script.src = 'assets/js/vehiculos.js';
                script.onload = () => {
                    if (window.vehiculosView) {
                        window.vehiculosView.init();
                    }
                };
                document.body.appendChild(script);
            } else {
                if (window.vehiculosView) {
                    window.vehiculosView.init();
                }
            }
        })
        .catch(error => console.error('Error cargando módulo vehículos:', error));
}

function cargarEmpleados() {
    fetch('api/empleados.php')
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('module-empleados');
            container.innerHTML = `
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3>👥 Listado de Empleados</h3>
                        <button class="btn btn-primary" onclick="nuevoEmpleado()">+ Nuevo Empleado</button>
                    </div>
                    <p>Módulo en construcción...</p>
                </div>
            `;
        });
}

function cargarAsignaciones() {
    const container = document.getElementById('module-asignaciones');
    container.innerHTML = `
        <div class="card">
            <h3>🔄 Gestión de Asignaciones</h3>
            <p>Módulo en construcción...</p>
        </div>
    `;
}

function cargarMultas() {
    const container = document.getElementById('module-multas');
    container.innerHTML = `
        <div class="card">
            <h3>⚠️ Registro de Multas</h3>
            <p>Módulo en construcción...</p>
        </div>
    `;
}

function cargarMantenimientos() {
    const container = document.getElementById('module-mantenimientos');
    container.innerHTML = `
        <div class="card">
            <h3>🔧 Control de Mantenimiento</h3>
            <p>Módulo en construcción...</p>
        </div>
    `;
}

function cargarPagos() {
    const container = document.getElementById('module-pagos');
    container.innerHTML = `
        <div class="card">
            <h3>💰 Control de Pagos</h3>
            <p>Módulo en construcción...</p>
        </div>
    `;
}

function cargarReportes() {
    const container = document.getElementById('module-reportes');
    container.innerHTML = `
        <div class="card">
            <h3>📈 Reportes y Estadísticas</h3>
            <p>Módulo en construcción...</p>
        </div>
    `;
}

function nuevoVehiculo() {
    alert('Formulario de nuevo vehículo en construcción...');
}

function verVehiculo(id) {
    alert('Ver detalle del vehículo ' + id);
}

function editarVehiculo(id) {
    alert('Editar vehículo ' + id);
}

function nuevoEmpleado() {
    alert('Formulario de nuevo empleado en construcción...');
}
