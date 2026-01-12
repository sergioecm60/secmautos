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
        case 'autorizaciones':
            cargarAutorizaciones();
            break;
        case 'asignaciones':
            cargarAsignaciones();
            break;
        case 'multas':
            cargarMultas();
            break;
        case 'compras_ventas':
            cargarComprasVentas();
            break;
        case 'transferencias':
            cargarTransferencias();
            break;
        case 'mantenimientos':
            cargarMantenimientos();
            break;
        case 'pagos':
            cargarPagos();
            break;
        case 'ficha_vehiculo':
            cargarFichaVehiculo();
            break;
        case 'reportes':
            cargarReportes();
            break;
        case 'usuarios':
            cargarUsuarios();
            break;
        case 'configuracion':
            cargarConfiguracion();
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
    const container = document.getElementById('module-empleados');

    if (container.innerHTML.trim() !== '') {
        if (window.empleadosView) {
            window.empleadosView.cargar();
        }
        return;
    }

    fetch('modules/empleados.html')
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;

            if (!document.querySelector('script[src="assets/js/empleados.js"]')) {
                const script = document.createElement('script');
                script.src = 'assets/js/empleados.js';
                script.onload = () => {
                    if (window.empleadosView) {
                        window.empleadosView.init();
                    }
                };
                document.body.appendChild(script);
            } else {
                if (window.empleadosView) {
                    window.empleadosView.init();
                }
            }
        })
        .catch(error => console.error('Error cargando módulo empleados:', error));
}

function cargarAsignaciones() {
    const container = document.getElementById('module-asignaciones');

    if (container.innerHTML.trim() !== '' && !container.innerHTML.includes('en construcción')) {
        if (window.asignacionesView) {
            window.asignacionesView.cargarDatos();
        }
        return;
    }

    fetch('modules/asignaciones.html')
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;

            if (!document.querySelector('script[src="assets/js/asignaciones.js"]')) {
                const script = document.createElement('script');
                script.src = 'assets/js/asignaciones.js';
                script.onload = () => {
                    window.asignacionesView = new AsignacionesView();
                };
                document.body.appendChild(script);
            } else {
                window.asignacionesView = new AsignacionesView();
            }
        })
        .catch(error => console.error('Error cargando módulo asignaciones:', error));
}

function cargarMultas() {
    const container = document.getElementById('module-multas');

    if (container.innerHTML.trim() !== '' && !container.innerHTML.includes('en construcción')) {
        // Module already loaded, maybe just refresh data if needed
        return;
    }

    fetch('modules/multas.html')
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;

            if (!document.querySelector('script[src="assets/js/multas.js"]')) {
                const script = document.createElement('script');
                script.src = 'assets/js/multas.js';
                script.onload = () => {
                    if (window.MultasView) {
                        window.multasView = new MultasView();
                    }
                };
                document.body.appendChild(script);
            } else {
                if (window.MultasView) {
                    window.multasView = new MultasView();
                }
            }
        })
        .catch(error => console.error('Error cargando módulo multas:', error));
}

function cargarMantenimientos() {
    const container = document.getElementById('module-mantenimientos');
    if (container.innerHTML.trim() !== '' && !container.innerHTML.includes('en construcción')) {
        return;
    }

    fetch('modules/mantenimientos.html')
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;

            if (!document.querySelector('script[src="assets/js/mantenimientos.js"]')) {
                const script = document.createElement('script');
                script.src = 'assets/js/mantenimientos.js';
                script.onload = () => {
                    if (window.MantenimientosView) {
                        window.mantenimientosView = new MantenimientosView();
                    }
                };
                document.body.appendChild(script);
            } else {
                if (window.MantenimientosView) {
                    window.mantenimientosView = new MantenimientosView();
                }
            }
        })
        .catch(error => console.error('Error cargando módulo mantenimientos:', error));
}

function cargarPagos() {
    const container = document.getElementById('module-pagos');
    if (container.innerHTML.trim() !== '' && !container.innerHTML.includes('en construcción')) {
        return;
    }

    fetch('modules/pagos.html')
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;

            if (!document.querySelector('script[src="assets/js/pagos.js"]')) {
                const script = document.createElement('script');
                script.src = 'assets/js/pagos.js';
                document.body.appendChild(script);
            }
        })
        .catch(error => console.error('Error cargando módulo pagos:', error));
}

function cargarFichaVehiculo(id) {
    const container = document.getElementById('module-ficha_vehiculo');
    container.innerHTML = '';

    fetch('modules/ficha_vehiculo.html')
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;

            if (!document.querySelector('script[src="assets/js/ficha_vehiculo.js"]')) {
                const script = document.createElement('script');
                script.src = 'assets/js/ficha_vehiculo.js';
                script.onload = () => {
                    if (id) {
                        cargarFichaVehiculo(id);
                    }
                };
                document.body.appendChild(script);
            } else {
                if (id) {
                    cargarFichaVehiculo(id);
                }
            }
        })
        .catch(error => console.error('Error cargando ficha vehículo:', error));
}

function cargarReportes() {
    const container = document.getElementById('module-reportes');
    if (container.innerHTML.trim() !== '' && !container.innerHTML.includes('en construcción')) {
        return;
    }

    fetch('modules/reportes.html')
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;

            if (!document.querySelector('script[src="assets/js/reportes.js"]')) {
                const script = document.createElement('script');
                script.src = 'assets/js/reportes.js';
                document.body.appendChild(script);
            }
        })
        .catch(error => console.error('Error cargando módulo reportes:', error));
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

function cargarComprasVentas() {
    const container = document.getElementById('module-compras_ventas');
    if (container.innerHTML.trim() !== '' && !container.innerHTML.includes('en construcción')) {
        return;
    }

    fetch('modules/compras_ventas.html')
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;

            if (!document.querySelector('script[src="assets/js/compras_ventas.js"]')) {
                const script = document.createElement('script');
                script.src = 'assets/js/compras_ventas.js';
                script.onload = () => {
                    if (window.ComprasVentasView) {
                        window.comprasVentasView = new ComprasVentasView();
                    }
                };
                document.body.appendChild(script);
            } else {
                if (window.ComprasVentasView) {
                    window.comprasVentasView = new ComprasVentasView();
                }
            }
        })
        .catch(error => console.error('Error cargando módulo compras/ventas:', error));
}

function cargarTransferencias() {
    const container = document.getElementById('module-transferencias');
    if (container.innerHTML.trim() !== '' && !container.innerHTML.includes('en construcción')) {
        return;
    }

    fetch('modules/transferencias.html')
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;

            if (!document.querySelector('script[src="assets/js/transferencias.js"]')) {
                const script = document.createElement('script');
                script.src = 'assets/js/transferencias.js';
                script.onload = () => {
                    if (window.TransferenciasView) {
                        window.transferenciasView = new TransferenciasView();
                    }
                };
                document.body.appendChild(script);
            } else {
                if (window.TransferenciasView) {
                    window.transferenciasView = new TransferenciasView();
                }
            }
        })
        .catch(error => console.error('Error cargando módulo transferencias:', error));
}

function cargarUsuarios() {
    const container = document.getElementById('module-usuarios');
    if (container.innerHTML.trim() !== '' && !container.innerHTML.includes('en construcción')) {
        if (window.usuariosView) {
            window.usuariosView.loadInitialData();
        }
        return;
    }

    console.log('Cargando módulo usuarios...');
    fetch('modules/usuarios.html')
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;
            console.log('HTML de usuarios cargado');

            if (!document.querySelector('script[src="assets/js/usuarios.js"]')) {
                console.log('Creando script de usuarios...');
                const script = document.createElement('script');
                script.src = 'assets/js/usuarios.js';
                script.onload = () => {
                    console.log('Script de usuarios cargado');
                    console.log('window.UsuariosView:', window.UsuariosView);
                    if (window.UsuariosView) {
                        window.usuariosView = new UsuariosView();
                        console.log('Instancia de usuariosView creada:', window.usuariosView);
                    }
                };
                script.onerror = (e) => console.error('Error cargando script:', e);
                document.body.appendChild(script);
            } else {
                console.log('Script de usuarios ya existe, creando instancia...');
                if (window.UsuariosView) {
                    window.usuariosView = new UsuariosView();
                    console.log('Instancia de usuariosView creada:', window.usuariosView);
                }
            }
        })
        .catch(error => console.error('Error cargando módulo usuarios:', error));
}

function cargarAutorizaciones() {
    const container = document.getElementById('module-autorizaciones');
    if (container.innerHTML.trim() !== '' && !container.innerHTML.includes('en construcción')) {
        return;
    }

    fetch('modules/autorizaciones.html')
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;

            if (!document.querySelector('script[src="assets/js/autorizaciones.js"]')) {
                const script = document.createElement('script');
                script.src = 'assets/js/autorizaciones.js';
                script.onload = () => {
                    if (window.AutorizacionesView) {
                        window.autorizacionesView = new AutorizacionesView();
                    }
                };
                document.body.appendChild(script);
            } else {
                if (window.AutorizacionesView) {
                    window.autorizacionesView = new AutorizacionesView();
                }
            }
        })
        .catch(error => console.error('Error cargando módulo autorizaciones:', error));
}

function cargarConfiguracion() {
    const container = document.getElementById('module-configuracion');
    if (container.innerHTML.trim() !== '' && !container.innerHTML.includes('en construcción')) {
        return;
    }

    fetch('modules/configuracion.html')
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;

            if (!document.querySelector('script[src="assets/js/configuracion.js"]')) {
                const script = document.createElement('script');
                script.src = 'assets/js/configuracion.js';
                document.body.appendChild(script);
            }
        })
        .catch(error => console.error('Error cargando módulo configuración:', error));
}
