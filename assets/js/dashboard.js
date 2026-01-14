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
            document.getElementById('module-' + module).classList.add('active');

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
    .catch(error => {
        console.error('Error cargando dashboard:', error);
        // Intentar parsear el error para mostrar mensaje más claro
        if (error.message && error.message.includes('JSON')) {
            alert('❌ Error al cargar los datos del dashboard. Por favor recarga la página.');
        }
    });
}

function renderStats(data) {
    const container = document.getElementById('stats-grid');
    if (!data.success) return;

    const d = data.data;

    const stats = [
        { label: 'Total Vehículos', value: d.total_vehiculos, icon: '🚗' },
        { label: 'Disponibles', value: d.disponibles, icon: '✅' },
        { label: 'Asignados', value: d.asignados, icon: '🔄' },
        { label: 'En Mantenimiento', value: d.mantenimiento, icon: '🔧' },
        { label: 'Total Empleados', value: d.total_empleados, icon: '👥' },
        { label: 'Alertas Activas', value: d.alertas_activas, icon: '⚠️' },
        { label: 'Multas Pendientes', value: d.multas_pendientes, icon: '💰' },
        { label: 'Mantenimientos Programados', value: d.mantenimientos_programados, icon: '📅' }
    ];

    // Estadísticas de pagos por tipo
    const pagoStats = [
        { label: 'Patentes', cantidad: d.pagos_patente_pendientes || 0, monto: d.pagos_patente_monto || 0, icon: '🏛️' },
        { label: 'Seguros', cantidad: d.pagos_seguro_pendientes || 0, monto: d.pagos_seguro_monto || 0, icon: '🛡️' },
        { label: 'VTV', cantidad: d.pagos_vtv_pendientes || 0, monto: d.pagos_vtv_monto || 0, icon: '🔍' },
        { label: 'Multas', cantidad: d.pagos_multa_pendientes || 0, monto: d.pagos_multa_monto || 0, icon: '⚠️' },
        { label: 'Telepases', cantidad: d.telepases_pendientes || 0, monto: d.telepases_monto || 0, icon: '🎫' },
        { label: 'Servicios', cantidad: d.pagos_servicios_pendientes || 0, monto: d.pagos_servicios_monto || 0, icon: '🛠️' },
        { label: 'Otros', cantidad: d.pagos_otro_pendientes || 0, monto: d.pagos_otro_monto || 0, icon: '📦' }
    ];

    const formatMonto = (monto) => {
        return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(monto);
    };

    let html = `
        ${stats.map(stat => `
            <div class="stat-card">
                <div style="font-size: 2em;">${stat.icon}</div>
                <h4>${stat.value}</h4>
                <p>${stat.label}</p>
            </div>
        `).join('')}
    `;

    // Solo mostrar sección de pagos si hay pagos pendientes
    const totalPagos = d.total_pagos_pendientes || 0;
    if (totalPagos > 0) {
        html += `
            <div style="grid-column: 1 / -1; margin-top: 25px; margin-bottom: 15px; padding-top: 20px; border-top: 2px solid var(--border-color);">
                <h4 style="margin: 0;">💰 Pagos Pendientes por Tipo</h4>
            </div>
            ${pagoStats.filter(p => p.cantidad > 0).map(pago => `
                <div class="stat-card" style="background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%); border-left: 4px solid var(--primary-color);">
                    <div style="font-size: 2em;">${pago.icon}</div>
                    <h4>${pago.cantidad}</h4>
                    <p>${pago.label}</p>
                    <small style="color: var(--text-secondary); font-weight: 600;">${formatMonto(pago.monto)}</small>
                </div>
            `).join('')}
            <div class="stat-card" style="background: linear-gradient(135deg, #fff5f5 0%, #ffe6e6 100%); border: 2px solid #e74c3c;">
                <div style="font-size: 2em;">💸</div>
                <h4>${totalPagos}</h4>
                <p>Total Pendiente</p>
                <strong style="color: #e74c3c; font-size: 1.1em;">${formatMonto(d.total_monto_pendiente || 0)}</strong>
            </div>
        `;
    }

    container.innerHTML = html;
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
            <td>${alerta.estado}</td>
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

    const rows = data.data.map(venc => {
        const dangerClass = venc.dias_restantes <= 7 ? 'text-danger fw-bold' : '';
        return `
        <tr class="fila-pendiente">
            <td><strong>${venc.patente}</strong></td>
            <td>${venc.marca} ${venc.modelo}</td>
            <td>${venc.tipo_vencimiento}</td>
            <td>${venc.fecha_vencimiento}</td>
            <td>
                <span class="${dangerClass}">
                    ${venc.dias_restantes} días
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-success" onclick="pagarDesdeDashboard(${venc.pago_id}, '${venc.patente}', '${venc.tipo_vencimiento}', '${venc.fecha_vencimiento}')" title="Pagar ahora">
                    <i class="bi bi-cash"></i> Pagar
                </button>
            </td>
        </tr>
        `;
    }).join('');

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
                        <th>Acciones</th>
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
        case 'combustible':
            cargarCombustible();
            break;
        case 'talleres':
            cargarTalleres();
            break;
        case 'telepases':
            cargarTelepases();
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
        case 'logs':
            cargarLogsModule();
            break;
        case 'configuracion':
            cargarConfiguracion();
            break;
    }
}

function cargarVehiculos() {
    const container = document.getElementById('module-vehiculos');

    if (container.innerHTML.trim() !== '') {
        if (window.vehiculosView) {
            window.vehiculosView.cargar();
        }
        return;
    }

    fetch('modules/vehiculos.html')
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;

            if (!document.querySelector('script[src="assets/js/vehiculos.js"]')) {
                const script = document.createElement('script');
                script.src = 'assets/js/vehiculos.js';
                script.onload = () => {
                    if (window.vehiculosView) {
                        window.vehiculosView.init();
                    }
                };
                document.body.appendChild(script);
            }
        })
        .catch(error => console.error('Error cargando módulo vehículos:', error));
}

function cargarEmpleados() {
    const container = document.getElementById('module-empleados');

    if (container.innerHTML.trim() !== '' && !container.innerHTML.includes('en construcción')) {
        if (window.empleadosView) {
            window.empleadosView.init();
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
            }
        })
        .catch(error => console.error('Error cargando módulo empleados:', error));
}

function cargarAutorizaciones() {
    const container = document.getElementById('module-autorizaciones');

    if (container.innerHTML.trim() !== '' && !container.innerHTML.includes('en construcción')) {
        if (window.AutorizacionesView && !window.autorizacionesView) {
            window.autorizacionesView = new AutorizacionesView();
        }
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
                    if (window.AutorizacionesView && !window.autorizacionesView) {
                        window.autorizacionesView = new AutorizacionesView();
                    }
                };
                document.body.appendChild(script);
            }
        })
        .catch(error => console.error('Error cargando módulo autorizaciones:', error));
}

function cargarAsignaciones() {
    const container = document.getElementById('module-asignaciones');

    if (container.innerHTML.trim() !== '' && !container.innerHTML.includes('en construcción')) {
        if (window.asignacionesView) {
            window.asignacionesView.init();
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
                    if (window.asignacionesView) {
                        window.asignacionesView.init();
                    }
                };
                document.body.appendChild(script);
            }
        })
        .catch(error => console.error('Error cargando módulo asignaciones:', error));
}

function cargarMultas() {
    const container = document.getElementById('module-multas');

    if (container.innerHTML.trim() !== '' && !container.innerHTML.includes('en construcción')) {
        return;
    }

    fetch('modules/multas.html')
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;

            if (!document.querySelector('script[src="assets/js/multas.js"]')) {
                const script = document.createElement('script');
                script.src = 'assets/js/multas.js';
                document.body.appendChild(script);
            }
        })
        .catch(error => console.error('Error cargando módulo multas:', error));
}

function cargarComprasVentas() {
    const container = document.getElementById('module-compras_ventas');

    if (container.innerHTML.trim() !== '' && !container.innerHTML.includes('en construcción')) {
        if (window.ComprasVentasView && !window.comprasVentasView) {
            window.comprasVentasView = new ComprasVentasView();
        }
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
                    if (window.ComprasVentasView && !window.comprasVentasView) {
                        window.comprasVentasView = new ComprasVentasView();
                    }
                };
                document.body.appendChild(script);
            }
        })
        .catch(error => console.error('Error cargando módulo compras/ventas:', error));
}

function cargarTransferencias() {
    const container = document.getElementById('module-transferencias');

    if (container.innerHTML.trim() !== '' && !container.innerHTML.includes('en construcción')) {
        if (window.TransferenciasView && !window.transferenciasView) {
            window.transferenciasView = new TransferenciasView();
        }
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
                    if (window.TransferenciasView && !window.transferenciasView) {
                        window.transferenciasView = new TransferenciasView();
                    }
                };
                document.body.appendChild(script);
            }
        })
        .catch(error => console.error('Error cargando módulo transferencias:', error));
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
                    if (window.mantenimientosView) {
                        window.mantenimientosView.init();
                    }
                };
                document.body.appendChild(script);
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
                document.onload = () => {
                    if (window.pagosView) {
                        window.pagosView = new PagosView();
                    }
                };
                document.body.appendChild(script);
            }
        })
        .catch(error => console.error('Error cargando módulo pagos:', error));
}

function cargarTelepases() {
    const container = document.getElementById('module-telepases');

    if (container.innerHTML.trim() !== '' && !container.innerHTML.includes('en construcción')) {
        return;
    }

    fetch('modules/telepases.html')
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;

            if (!document.querySelector('script[src="assets/js/telepases.js"]')) {
                const script = document.createElement('script');
                script.src = 'assets/js/telepases.js';
                script.onload = () => {
                    if (window.telepasesView) {
                        window.telepasesView.init();
                    }
                };
                document.body.appendChild(script);
            }
        })
        .catch(error => console.error('Error cargando módulo telepases:', error));
}

function cargarFichaVehiculo() {
    const container = document.getElementById('module-ficha_vehiculo');

    if (container.innerHTML.trim() !== '' && !container.innerHTML.includes('en construcción')) {
        return;
    }

    fetch('modules/ficha_vehiculo.html')
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;

            if (!document.querySelector('script[src="assets/js/ficha_vehiculo.js"]')) {
                const script = document.createElement('script');
                script.src = 'assets/js/ficha_vehiculo.js';
                document.onload = () => {
                    cargarFichaVehiculo(id);
                };
                document.body.appendChild(script);
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

function cargarUsuarios() {
    const container = document.getElementById('module-usuarios');

    if (container.innerHTML.trim() !== '' && !container.innerHTML.includes('en construcción')) {
        if (window.UsuariosView && !window.usuariosView) {
            window.usuariosView = new UsuariosView();
        }
        return;
    }

    fetch('modules/usuarios.html')
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;

            if (!document.querySelector('script[src="assets/js/usuarios.js"]')) {
                const script = document.createElement('script');
                script.src = 'assets/js/usuarios.js';
                script.onload = () => {
                    if (window.UsuariosView && !window.usuariosView) {
                        window.usuariosView = new UsuariosView();
                    }
                };
                document.body.appendChild(script);
            }
        })
        .catch(error => console.error('Error cargando módulo usuarios:', error));
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

// Función para pagar un vencimiento directamente desde el dashboard
function pagarDesdeDashboard(pagoId, patente, tipo, fechaVencimiento) {
    const container = document.getElementById('module-pagos');

    // Si el módulo de pagos no está cargado, cargarlo primero
    if (container.innerHTML.trim() === '') {
        fetch('modules/pagos.html')
            .then(r => r.text())
            .then(html => {
                container.innerHTML = html;

                if (!document.querySelector('script[src="assets/js/pagos.js"]')) {
                    const script = document.createElement('script');
                    script.src = 'assets/js/pagos.js';
                    script.onload = () => {
                        prellenarPago(pagoId, patente, tipo, fechaVencimiento);
                    };
                    document.body.appendChild(script);
                }
            })
            .catch(error => console.error('Error cargando módulo pagos:', error));
    } else {
        prellenarPago(pagoId, patente, tipo, fechaVencimiento);
    }
}

function cargarCombustible() {
    const container = document.getElementById('module-combustible');

    if (container.innerHTML.trim() !== '' && !container.innerHTML.includes('en construcción')) {
        if (window.CombustibleView && !window.combustibleView) {
            window.combustibleView = new CombustibleView();
        }
        return;
    }

    fetch('modules/combustible.html')
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;

            if (!document.querySelector('script[src="assets/js/combustible.js"]')) {
                const script = document.createElement('script');
                script.src = 'assets/js/combustible.js';
                script.onload = () => {
                    if (window.CombustibleView && !window.combustibleView) {
                        window.combustibleView = new CombustibleView();
                    }
                };
                script.onerror = () => {
                    console.error('Error cargando script de combustible');
                    container.innerHTML = '<p class="text-danger">Error al cargar el módulo. Por favor recarga la página.</p>';
                };
                document.body.appendChild(script);
            }
        })
        .catch(error => console.error('Error cargando módulo combustible:', error));
}

function cargarTalleres() {
    const container = document.getElementById('module-talleres');

    if (container.innerHTML.trim() !== '' && !container.innerHTML.includes('en construcción')) {
        return;
    }

    fetch('modules/talleres.html')
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;

            if (!document.querySelector('script[src="assets/js/talleres.js"]')) {
                const script = document.createElement('script');
                script.src = 'assets/js/talleres.js';
                script.onload = () => {
                    if (window.talleresView) {
                        window.talleresView.init();
                    }
                };
                script.onerror = () => {
                    console.error('Error cargando script de talleres');
                    container.innerHTML = '<p class="text-danger">Error al cargar el módulo. Por favor recarga la página.</p>';
                };
                document.body.appendChild(script);
            }
        })
        .catch(error => {
            console.error('Error cargando módulo talleres:', error);
            container.innerHTML = '<p class="text-danger">Error de conexión al cargar el módulo. Por favor recarga la página.</p>';
        });
}

function prellenarPago(pagoId, patente, tipo, fechaVencimiento) {
    // Esperar un poco para que el módulo cargue
    setTimeout(() => {
        if (!window.pagosView) {
            console.error('View de pagos no disponible');
            return;
        }

        // Buscar el vehículo por patente
        const vehiculoSelect = document.querySelector('select[name="vehiculo_id"]');
        const tipoSelect = document.querySelector('select[name="tipo"]');
        const fechaVencimientoInput = document.querySelector('input[name="fecha_vencimiento"]');
        const montoInput = document.querySelector('input[name="monto"]');
        const observacionesInput = document.querySelector('textarea[name="observaciones"]');

        if (!vehiculoSelect || !tipoSelect || !montoInput) {
            console.error('Elementos del formulario no encontrados');
            return;
        }

        // Seleccionar el vehículo
        Array.from(vehiculoSelect.options).forEach(option => {
            if (option.text.includes(patente)) {
                vehiculoSelect.value = option.value;
            }
        });

        // Mapeo de tipos
        const tipoMapping = {
            'VTV': 'otro',
            'Seguro': 'seguro',
            'Patente': 'patente',
            'Servicios': 'servicios'
        };

        // Prellenar tipo
        tipoSelect.value = tipoMapping[tipo] || 'otro';

        // Prellenar fecha de vencimiento (para referencia, se puede cambiar)
        fechaVencimientoInput.value = fechaVencimiento;

        // Agregar observación
        observacionesInput.value = 'Pago de ' + tipo + ' - ' + patente + ' - Vencía: ' + fechaVencimiento;

        // Mostrar mensaje informativo
        alert('💰 Pago de ' + tipo + '\n\nVehículo: ' + patente + '\nVencimiento original: ' + fechaVencimiento + '\n\nCompleta el monto y la fecha de pago, luego guarda.');
    }, 500);
}

// Función para abrir el formulario de nuevo telepase desde cualquier lugar
function nuevoTelepase() {
    cargarTelepases();

    const container = document.getElementById('module-telepases');

    setTimeout(() => {
        if (window.telepasesView && window.telepasesView.modal) {
            window.telepasesView.nuevoTelepase();
        } else {
            console.error('La vista de telepases aún no está completamente inicializada');
            alert('❌ Error: El módulo de telepases aún no está completamente cargado. Por favor espera unos segundos e intenta nuevamente.');
        }
    }, 500);
}

// Función para cargar el módulo de logs
function cargarLogsModule() {
    const container = document.getElementById('module-logs');

    fetch('modules/logs.html')
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;

            if (!document.querySelector('script[src="assets/js/logs.js"]')) {
                const script = document.createElement('script');
                script.src = 'assets/js/logs.js';
                document.body.appendChild(script);
            }
        })
        .catch(error => console.error('Error al cargar logs:', error));
}
