# 🚗 SECM Autos - Hoja de Ruta de Desarrollo

**Proyecto:** Sistema de Gestión de Flota Automotor
**Fecha inicio:** 2026-01-09
**Estado:** Base de datos completa ✅ | Backend parcial ✅ | Frontend 10% ⚠️

---

## 📊 Estado Actual del Proyecto

### ✅ **Completado (Base de Datos + Backend API)**

#### 1. **Base de Datos MySQL - 14 Tablas** (`db/install.sql`)
```
✅ usuarios              - Autenticación con roles (superadmin, admin, user)
✅ intentos_login_ip     - Bloqueo por intentos fallidos
✅ logs                  - Auditoría completa de acciones
✅ vehiculos             - Patente, marca, modelo, año, motor, chasis, titularidad, km, estado, VTV, seguro, patente, fecha_baja
✅ empleados             - Personal (nombre, apellido, DNI, email, teléfono, dirección)
✅ asignaciones          - Historial vehículo ↔ empleado (con km salida/regreso)
✅ compras               - Fecha, factura, proveedor, CUIT, neto, IVA, total
✅ ventas                - Fecha, factura, comprador, CUIT, importe
✅ ceta                  - Cédula Azul (número, vencimiento, envío)
✅ transferencias        - Registro, dirección, número trámite, estado
✅ mantenimientos        - Preventivo/correctivo, costo, km, proveedor
✅ multas                - Con asignación a empleado responsable, monto, pagada
✅ pagos                 - Patentes, seguros, otros (tipo, vencimiento, pago)
✅ alertas               - Sistema de notificaciones (VTV, seguro, patente, CETA, km, multas)
```

**Usuario por defecto:**
- Email: `admin@secmautos.com`
- Password: `password` (cambiar en producción)

#### 2. **API Backend - PHP 8.x** (13 endpoints funcionales)

| Endpoint | Métodos | Estado | Funcionalidad |
|----------|---------|--------|---------------|
| `api/auth.php` | - | ✅ | Login, logout, roles, logs |
| `api/login_handler.php` | POST | ✅ | Procesa login con CAPTCHA |
| `api/logout.php` | POST | ✅ | Cierra sesión |
| `api/vehiculos.php` | GET, POST | ✅ | Listar + crear vehículos |
| `api/empleados.php` | GET, POST | ✅ | Listar + crear empleados |
| `api/asignaciones.php` | GET, POST | ✅ | Listar + crear asignaciones |
| `api/multas.php` | GET, POST | ✅ | Listar + crear multas |
| `api/mantenimientos.php` | GET, POST | ✅ | Listar + crear mantenimientos |
| `api/pagos.php` | GET, POST | ✅ | Listar + crear pagos |
| `api/stats.php` | GET | ✅ | Estadísticas dashboard |
| `api/alertas.php` | GET | ✅ | Alertas activas |
| `api/vencimientos.php` | GET | ✅ | Vencimientos próximos |
| `api/refresh_captcha.php` | GET | ✅ | Regenerar CAPTCHA |

**FALTA EN BACKEND:**
- ❌ PUT/DELETE en todos los endpoints (editar, eliminar)
- ❌ Endpoint para CETA (crear/editar)
- ❌ Endpoint para compras/ventas (crear/editar)
- ❌ Endpoint para transferencias (crear/editar)
- ❌ Endpoint para devolución de asignación
- ❌ Cron job para generar alertas automáticas

#### 3. **Frontend - HTML/CSS/JS**

```
✅ login.php             - Página de login con CAPTCHA
✅ index.php             - Dashboard SPA con navegación
✅ assets/css/           - Bootstrap 5 + style.css + themes.css
✅ assets/js/login.js    - Manejo de login
✅ assets/js/dashboard.js - Carga stats/alertas/vencimientos
✅ Sistema de temas      - Multi-tema con CSS variables
✅ Diseño responsive     - Mobile-first
```

**FALTA EN FRONTEND (90%):**
- ❌ Formularios de alta/edición de vehículos
- ❌ Formularios de alta/edición de empleados
- ❌ Formulario de asignación de vehículos
- ❌ Formulario de devolución de vehículos
- ❌ Gestión de compras (formulario + tabla)
- ❌ Gestión de ventas (formulario + tabla)
- ❌ Gestión de CETA (formulario + tabla + alertas)
- ❌ Gestión de transferencias (formulario + tabla)
- ❌ Gestión de multas (formulario + tabla)
- ❌ Gestión de mantenimientos (formulario + tabla)
- ❌ Gestión de pagos (formulario + tabla)
- ❌ Módulo de reportes (exportar Excel, PDF)
- ❌ Ficha completa de vehículo (historial, documentos)
- ❌ Subida de comprobantes (PDF/imágenes)

#### 4. **Cambios Pendientes de Git**

```bash
Modified:   assets/css/themes.css  (1 línea - cierre de comentario CSS)
Modified:   login.jpg              (cambio binario - imagen optimizada)
Untracked:  logout.php             (nuevo archivo funcional)
```

---

## 🎯 Plan de Implementación - Fase por Fase

### **FASE 1: Completar Backend API (2-3 horas)** ⚠️ PRIORITARIO

#### Tarea 1.1: Extender APIs existentes con PUT/DELETE
**Archivos a modificar:**
- `api/vehiculos.php` - Agregar cases 'PUT' y 'DELETE'
- `api/empleados.php` - Agregar cases 'PUT' y 'DELETE'
- `api/multas.php` - Agregar case 'PUT' (marcar como pagada)
- `api/mantenimientos.php` - Agregar cases 'PUT' y 'DELETE'
- `api/pagos.php` - Agregar case 'PUT' (marcar como pagado)

**Ejemplo PUT en vehiculos.php:**
```php
case 'PUT':
    parse_str(file_get_contents('php://input'), $_PUT);
    if (!verificar_csrf($_PUT['csrf_token'] ?? '')) {
        json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
    }

    $id = (int)($_PUT['id'] ?? 0);
    $patente = strtoupper(trim($_PUT['patente'] ?? ''));
    // ... más campos

    $stmt = $pdo->prepare("UPDATE vehiculos SET patente = ?, marca = ?, ... WHERE id = ?");
    $stmt->execute([...]);
    json_response(['success' => true, 'message' => 'Vehículo actualizado']);
    break;
```

#### Tarea 1.2: Crear nuevos endpoints
**Crear archivos:**
- `api/compras.php` - GET (listar), POST (crear), PUT (editar)
- `api/ventas.php` - GET (listar), POST (crear), PUT (editar)
- `api/transferencias.php` - GET (listar), POST (crear), PUT (actualizar estado)
- `api/ceta.php` - GET (listar), POST (crear), PUT (editar)
- `api/asignaciones_devolucion.php` - PUT (devolver vehículo con km_regreso)

**Estructura base para compras.php:**
```php
<?php
require_once __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

if (!verificar_autenticacion()) {
    json_response(['success' => false, 'message' => 'No autenticado'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Listar compras con JOIN a vehiculos
        $stmt = $pdo->query("SELECT c.*, v.patente, v.marca, v.modelo FROM compras c JOIN vehiculos v ON c.vehiculo_id = v.id ORDER BY c.fecha DESC");
        $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);
        json_response(['success' => true, 'data' => $compras]);
        break;

    case 'POST':
        // Validar CSRF, sanitizar inputs, INSERT INTO compras
        break;

    case 'PUT':
        // Editar compra existente
        break;
}
```

#### Tarea 1.3: Script de alertas automáticas
**Crear archivo:** `scripts/generar_alertas.php`

```php
<?php
require_once __DIR__ . '/../bootstrap.php';

// Ejecutar diariamente vía cron: php scripts/generar_alertas.php

// 1. Limpiar alertas resueltas antiguas (más de 30 días)
$pdo->exec("DELETE FROM alertas WHERE resuelta = 1 AND fecha_resolucion < DATE_SUB(NOW(), INTERVAL 30 DAY)");

// 2. VTV próximas a vencer (15 días antes)
$stmt = $pdo->prepare("
    SELECT id, patente, fecha_vtv FROM vehiculos
    WHERE estado != 'baja'
    AND fecha_vtv IS NOT NULL
    AND fecha_vtv BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)
    AND id NOT IN (SELECT vehiculo_id FROM alertas WHERE tipo_alerta = 'vtv' AND resuelta = 0)
");
$stmt->execute();
foreach ($stmt->fetchAll() as $v) {
    $pdo->prepare("INSERT INTO alertas (vehiculo_id, tipo_alerta, mensaje, fecha_alerta) VALUES (?, 'vtv', ?, CURDATE())")
        ->execute([$v['id'], "VTV vence el {$v['fecha_vtv']} - Patente {$v['patente']}"]);
}

// 3. Seguro próximo a vencer (15 días antes)
// 4. Patente próxima a vencer
// 5. CETA próxima a vencer
// 6. Kilometraje próximo a service (1000 km antes)
// 7. Multas sin pagar (más de 30 días)

echo "Alertas generadas correctamente\n";
```

**Agregar cron job en Linux:**
```bash
0 6 * * * cd /var/www/secmautos && php scripts/generar_alertas.php >> logs/alertas.log 2>&1
```

---

### **FASE 2: Frontend - Módulo Vehículos (3-4 horas)**

#### Tarea 2.1: Crear vista de listado de vehículos
**Crear archivo:** `modules/vehiculos.html`

```html
<div class="card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>🚗 Gestión de Vehículos</h3>
        <button class="btn btn-primary" onclick="abrirModalVehiculo()">
            <i class="bi bi-plus-circle"></i> Nuevo Vehículo
        </button>
    </div>

    <!-- Filtros -->
    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <input type="text" class="form-control" id="filtro-patente" placeholder="Buscar por patente">
        </div>
        <div class="col-md-3">
            <select class="form-select" id="filtro-estado">
                <option value="">Todos los estados</option>
                <option value="disponible">Disponible</option>
                <option value="asignado">Asignado</option>
                <option value="mantenimiento">Mantenimiento</option>
                <option value="baja">Baja</option>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-secondary" onclick="filtrarVehiculos()">Filtrar</button>
        </div>
    </div>

    <!-- Tabla -->
    <div class="table-responsive">
        <table class="table table-hover" id="tabla-vehiculos">
            <thead>
                <tr>
                    <th>Patente</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Año</th>
                    <th>Km Actual</th>
                    <th>Estado</th>
                    <th>VTV</th>
                    <th>Seguro</th>
                    <th>Asignado a</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Modal Formulario -->
<div class="modal fade" id="modalVehiculo" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Vehículo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-vehiculo">
                    <input type="hidden" name="id" id="vehiculo-id">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Patente *</label>
                            <input type="text" class="form-control" name="patente" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Marca *</label>
                            <input type="text" class="form-control" name="marca" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Modelo *</label>
                            <input type="text" class="form-control" name="modelo" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Año</label>
                            <input type="number" class="form-control" name="anio">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Motor</label>
                            <input type="text" class="form-control" name="motor">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Chasis</label>
                            <input type="text" class="form-control" name="chasis">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Titularidad</label>
                            <input type="text" class="form-control" name="titularidad">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kilometraje Actual</label>
                            <input type="number" class="form-control" name="kilometraje_actual">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha VTV</label>
                            <input type="date" class="form-control" name="fecha_vtv">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha Seguro</label>
                            <input type="date" class="form-control" name="fecha_seguro">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha Patente</label>
                            <input type="date" class="form-control" name="fecha_patente">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" name="observaciones" rows="3"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarVehiculo()">Guardar</button>
            </div>
        </div>
    </div>
</div>
```

#### Tarea 2.2: JavaScript para módulo de vehículos
**Crear archivo:** `assets/js/vehiculos.js`

```javascript
let vehiculosData = [];

async function cargarVehiculos() {
    try {
        const res = await fetch('api/vehiculos.php');
        const data = await res.json();

        if (data.success) {
            vehiculosData = data.data;
            renderTablaVehiculos(vehiculosData);
        }
    } catch (error) {
        console.error('Error cargando vehículos:', error);
    }
}

function renderTablaVehiculos(vehiculos) {
    const tbody = document.querySelector('#tabla-vehiculos tbody');
    tbody.innerHTML = '';

    vehiculos.forEach(v => {
        const estadoBadge = getEstadoBadge(v.estado);
        const vtv = v.fecha_vtv ? formatDate(v.fecha_vtv) : 'N/A';
        const seguro = v.fecha_seguro ? formatDate(v.fecha_seguro) : 'N/A';

        tbody.innerHTML += `
            <tr>
                <td><strong>${v.patente}</strong></td>
                <td>${v.marca}</td>
                <td>${v.modelo}</td>
                <td>${v.anio || '-'}</td>
                <td>${v.kilometraje_actual.toLocaleString()} km</td>
                <td>${estadoBadge}</td>
                <td>${vtv}</td>
                <td>${seguro}</td>
                <td>${v.empleado_actual || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="verFicha(${v.id})">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="editarVehiculo(${v.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                </td>
            </tr>
        `;
    });
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

function abrirModalVehiculo() {
    document.getElementById('form-vehiculo').reset();
    document.getElementById('vehiculo-id').value = '';
    new bootstrap.Modal(document.getElementById('modalVehiculo')).show();
}

async function guardarVehiculo() {
    const form = document.getElementById('form-vehiculo');
    const formData = new FormData(form);
    const id = document.getElementById('vehiculo-id').value;

    const method = id ? 'PUT' : 'POST';
    const url = 'api/vehiculos.php';

    try {
        const res = await fetch(url, { method, body: formData });
        const data = await res.json();

        if (data.success) {
            alert(data.message);
            bootstrap.Modal.getInstance(document.getElementById('modalVehiculo')).hide();
            cargarVehiculos();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error guardando vehículo:', error);
        alert('Error al guardar el vehículo');
    }
}

function formatDate(dateString) {
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString('es-AR');
}
```

#### Tarea 2.3: Integrar módulo en dashboard
**Modificar:** `assets/js/dashboard.js`

```javascript
function cargarModulo(module) {
    const container = document.getElementById(`module-${module}`);

    if (container.innerHTML.trim() === '') {
        switch(module) {
            case 'vehiculos':
                fetch('modules/vehiculos.html')
                    .then(r => r.text())
                    .then(html => {
                        container.innerHTML = html;
                        loadScript('assets/js/vehiculos.js', cargarVehiculos);
                    });
                break;
            // ... otros módulos
        }
    }
}

function loadScript(src, callback) {
    if (document.querySelector(`script[src="${src}"]`)) {
        callback();
        return;
    }
    const script = document.createElement('script');
    script.src = src;
    script.onload = callback;
    document.body.appendChild(script);
}
```

---

### **FASE 3: Frontend - Módulo Empleados (2 horas)**

Similar a Fase 2, crear:
- `modules/empleados.html` - Tabla + Modal formulario
- `assets/js/empleados.js` - CRUD completo
- Integrar en dashboard.js

---

### **FASE 4: Frontend - Módulo Asignaciones (3 horas)**

#### Funcionalidades:
1. Listar asignaciones activas (tabla)
2. Formulario de asignación:
   - Seleccionar vehículo disponible
   - Seleccionar empleado
   - Ingresar km salida
   - Observaciones
3. Botón "Devolver" en cada asignación activa:
   - Modal con campo km regreso
   - Calcular km recorridos
   - Marcar fecha_devolucion
   - Cambiar estado vehículo a 'disponible'

**Archivos:**
- `modules/asignaciones.html`
- `assets/js/asignaciones.js`

---

### **FASE 5: Frontend - Módulo Multas (2 horas)**

#### Funcionalidades:
1. Listar multas (tabla filtrable por pagada/pendiente)
2. Formulario de alta:
   - Seleccionar vehículo
   - Auto-completar empleado asignado en fecha de multa
   - Monto, motivo, acta número
3. Botón "Marcar como pagada" (PUT)

**Archivos:**
- `modules/multas.html`
- `assets/js/multas.js`

---

### **FASE 6: Frontend - Módulo Compra/Venta (3 horas)**

#### Compras:
- Tabla con historial de compras
- Formulario: fecha, proveedor, CUIT, neto, IVA, total
- Subida de comprobante (PDF)

#### Ventas:
- Tabla con historial de ventas
- Formulario: fecha, comprador, CUIT, importe
- Al guardar, cambiar estado vehículo a 'baja'

**Archivos:**
- `modules/compras_ventas.html`
- `assets/js/compras_ventas.js`

---

### **FASE 7: Frontend - Módulo CETA (2 horas)**

- Tabla con CETA por vehículo
- Formulario: número cédula, fecha vencimiento
- Alertas automáticas 15 días antes

**Archivos:**
- `modules/ceta.html`
- `assets/js/ceta.js`

---

### **FASE 8: Frontend - Módulo Transferencias (2 horas)**

- Tabla con historial de transferencias
- Formulario: fecha, registro, dirección, número trámite, estado
- Estados: en_proceso, completa, cancelada

**Archivos:**
- `modules/transferencias.html`
- `assets/js/transferencias.js`

---

### **FASE 9: Frontend - Módulo Mantenimientos (2 horas)**

- Tabla con historial por vehículo
- Formulario: fecha, tipo (preventivo/correctivo), descripción, costo, km, proveedor
- Subida de comprobante

**Archivos:**
- `modules/mantenimientos.html`
- `assets/js/mantenimientos.js`

---

### **FASE 10: Frontend - Módulo Pagos (2 horas)**

- Tabla con pagos por vehículo
- Filtros: tipo (patente/seguro/otro), pagado/pendiente
- Formulario: tipo, fecha vencimiento, monto
- Marcar como pagado (fecha pago)

**Archivos:**
- `modules/pagos.html`
- `assets/js/pagos.js`

---

### **FASE 11: Ficha Completa de Vehículo (3 horas)**

Modal o página aparte que muestre:
- Datos generales (marca, modelo, año, motor, chasis)
- Historial de asignaciones (tabla)
- Multas asociadas (tabla)
- Mantenimientos (tabla)
- Documentos (VTV, seguro, patente, CETA)
- Compra/venta si aplica
- Botones de acción: Editar, Asignar, Mantenimiento

**Archivos:**
- `modules/ficha_vehiculo.html`
- `assets/js/ficha_vehiculo.js`

---

### **FASE 12: Reportes y Exportación (3 horas)**

#### Reportes a implementar:
1. **Listado para GCBA/Rentas** (Excel)
   - Todos los vehículos con fecha de patente, seguro, VTV
2. **Informe de dominio completo** (PDF)
   - Ficha de vehículo con historial
3. **Multas pendientes por empleado** (Excel)
4. **Vencimientos del mes** (PDF/Excel)
5. **Historial de asignaciones por período** (Excel)

**Librerías sugeridas:**
- `PhpSpreadsheet` para Excel (ya en Composer)
- `mPDF` o `TCPDF` para PDF

**Archivos:**
- `modules/reportes.html`
- `assets/js/reportes.js`
- `api/reportes/excel_gcba.php`
- `api/reportes/pdf_dominio.php`

---

### **FASE 13: Subida de Archivos (2 horas)**

Implementar upload de:
- Facturas de compra
- Facturas de venta
- Comprobantes de pago
- Documentos de mantenimiento

**Crear:**
- Carpeta `uploads/` con subcarpetas (compras/, ventas/, pagos/, mantenimientos/)
- Script PHP para upload con validación
- Tabla en DB para relacionar archivos con entidades

```sql
CREATE TABLE documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entidad VARCHAR(50) NOT NULL, -- 'compra', 'venta', 'pago', 'mantenimiento'
    entidad_id INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta VARCHAR(255) NOT NULL,
    tipo_mime VARCHAR(100),
    tamanio INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

### **FASE 14: Mejoras UX/UI (2 horas)**

- Implementar notificaciones toast (en lugar de alert())
- Agregar loading spinners en llamadas AJAX
- Implementar paginación en tablas grandes
- Agregar confirmación antes de eliminar
- Mejorar validación de formularios (HTML5 + JS)
- Agregar tooltips informativos

---

### **FASE 15: Testing y Ajustes (3 horas)**

- Probar todos los formularios
- Verificar alertas automáticas
- Revisar permisos por rol
- Ajustar queries SQL para performance
- Agregar índices faltantes en DB
- Validar cálculo de vencimientos
- Probar en diferentes navegadores

---

### **FASE 16: Documentación y Deployment (2 horas)**

- Crear `README.md` completo
- Documentar API endpoints
- Configurar `.htaccess` para producción
- Configurar cron jobs
- Generar usuarios de prueba
- Poblar datos de ejemplo
- Backup de base de datos

---

## 📁 Estructura de Archivos Final Esperada

```
secmautos/
├── api/
│   ├── auth.php ✅
│   ├── login_handler.php ✅
│   ├── logout.php ✅
│   ├── vehiculos.php ✅ (agregar PUT/DELETE)
│   ├── empleados.php ✅ (agregar PUT/DELETE)
│   ├── asignaciones.php ✅ (agregar PUT para devolución)
│   ├── multas.php ✅ (agregar PUT)
│   ├── mantenimientos.php ✅ (agregar PUT/DELETE)
│   ├── pagos.php ✅ (agregar PUT)
│   ├── compras.php ❌ CREAR
│   ├── ventas.php ❌ CREAR
│   ├── transferencias.php ❌ CREAR
│   ├── ceta.php ❌ CREAR
│   ├── stats.php ✅
│   ├── alertas.php ✅
│   ├── vencimientos.php ✅
│   ├── refresh_captcha.php ✅
│   └── reportes/
│       ├── excel_gcba.php ❌
│       └── pdf_dominio.php ❌
├── assets/
│   ├── css/
│   │   ├── bootstrap.min.css ✅
│   │   ├── style.css ✅
│   │   └── themes.css ✅ (fix comment)
│   ├── js/
│   │   ├── dashboard.js ✅ (modificar para cargar módulos)
│   │   ├── login.js ✅
│   │   ├── theme-switcher.js ✅
│   │   ├── vehiculos.js ❌
│   │   ├── empleados.js ❌
│   │   ├── asignaciones.js ❌
│   │   ├── multas.js ❌
│   │   ├── compras_ventas.js ❌
│   │   ├── ceta.js ❌
│   │   ├── transferencias.js ❌
│   │   ├── mantenimientos.js ❌
│   │   ├── pagos.js ❌
│   │   ├── ficha_vehiculo.js ❌
│   │   └── reportes.js ❌
│   └── img/
│       ├── logo.png ✅
│       ├── favicon.svg ✅
│       └── favicon.ico ✅
├── config/
│   ├── database.php ✅
│   └── config.php ✅
├── db/
│   ├── install.sql ✅
│   └── migrations/
├── docs/
│   └── (eliminados) ✅
├── logs/
│   ├── php_errors.log
│   └── alertas.log
├── modules/
│   ├── vehiculos.html ❌
│   ├── empleados.html ❌
│   ├── asignaciones.html ❌
│   ├── multas.html ❌
│   ├── compras_ventas.html ❌
│   ├── ceta.html ❌
│   ├── transferencias.html ❌
│   ├── mantenimientos.html ❌
│   ├── pagos.html ❌
│   ├── ficha_vehiculo.html ❌
│   └── reportes.html ❌
├── scripts/
│   └── generar_alertas.php ❌
├── sessions/
├── uploads/
│   ├── compras/
│   ├── ventas/
│   ├── pagos/
│   └── mantenimientos/
├── .env ✅
├── .env.example ✅
├── .gitignore ✅
├── bootstrap.php ✅
├── diagnostico.php ✅
├── index.php ✅
├── login.php ✅
├── logout.php ❌ (commitear)
├── licence.php ✅
├── README.md ✅ (actualizar)
└── HOJA_DE_RUTA.md ✅ (este archivo)
```

---

## ⏱️ Estimación de Tiempo Total

| Fase | Descripción | Horas | Prioridad |
|------|-------------|-------|-----------|
| 1 | Completar Backend API | 3h | 🔴 Alta |
| 2 | Frontend - Vehículos | 4h | 🔴 Alta |
| 3 | Frontend - Empleados | 2h | 🔴 Alta |
| 4 | Frontend - Asignaciones | 3h | 🟠 Media |
| 5 | Frontend - Multas | 2h | 🟠 Media |
| 6 | Frontend - Compra/Venta | 3h | 🟠 Media |
| 7 | Frontend - CETA | 2h | 🟠 Media |
| 8 | Frontend - Transferencias | 2h | 🟢 Baja |
| 9 | Frontend - Mantenimientos | 2h | 🟠 Media |
| 10 | Frontend - Pagos | 2h | 🟠 Media |
| 11 | Ficha Completa Vehículo | 3h | 🟠 Media |
| 12 | Reportes y Exportación | 3h | 🟢 Baja |
| 13 | Subida de Archivos | 2h | 🟢 Baja |
| 14 | Mejoras UX/UI | 2h | 🟢 Baja |
| 15 | Testing y Ajustes | 3h | 🔴 Alta |
| 16 | Documentación y Deployment | 2h | 🟢 Baja |
| **TOTAL** | | **40h** | |

---

## 🚀 Orden de Implementación Recomendado

### Sprint 1 (8-10 horas) - MVP Funcional
1. ✅ Commitear cambios pendientes (`logout.php`, `themes.css`, `login.jpg`)
2. ✅ Completar Backend API (FASE 1)
3. ✅ Frontend Vehículos (FASE 2)
4. ✅ Frontend Empleados (FASE 3)
5. ✅ Frontend Asignaciones básico (FASE 4)

**Resultado:** Sistema funcional para gestionar vehículos, empleados y asignaciones.

### Sprint 2 (8-10 horas) - Funcionalidades Core
6. ✅ Frontend Multas (FASE 5)
7. ✅ Frontend Mantenimientos (FASE 9)
8. ✅ Frontend Pagos (FASE 10)
9. ✅ Script de Alertas Automáticas (FASE 1.3)

**Resultado:** Sistema completo de control operativo diario.

### Sprint 3 (6-8 horas) - Gestión Patrimonial
10. ✅ Frontend Compra/Venta (FASE 6)
11. ✅ Frontend CETA (FASE 7)
12. ✅ Frontend Transferencias (FASE 8)

**Resultado:** Registro patrimonial y legal completo.

### Sprint 4 (6-8 horas) - Mejoras y Reportes
13. ✅ Ficha Completa de Vehículo (FASE 11)
14. ✅ Reportes (FASE 12)
15. ✅ Subida de Archivos (FASE 13)

**Resultado:** Sistema con reportes y documentación digital.

### Sprint 5 (4-6 horas) - Pulido Final
16. ✅ Mejoras UX/UI (FASE 14)
17. ✅ Testing Completo (FASE 15)
18. ✅ Documentación y Deploy (FASE 16)

**Resultado:** Sistema production-ready.

---

## 📋 Checklist Pre-Inicio

Antes de continuar el desarrollo, verificar:

- [ ] Base de datos `secmautos` creada y poblada con `db/install.sql`
- [ ] Usuario admin puede hacer login (admin@secmautos.com / password)
- [ ] `.env` configurado correctamente (DB_HOST, DB_NAME, DB_USER, DB_PASS)
- [ ] PHP 8.x funcionando en Laragon
- [ ] MySQL 8.x funcionando
- [ ] Bootstrap 5 cargando correctamente
- [ ] Dashboard muestra estadísticas (aunque sean 0)
- [ ] Git configurado y cambios pendientes commiteados

---

## 🔧 Comandos Útiles

### Iniciar sesión de desarrollo
```bash
cd C:/laragon/www/secmautos
php -S localhost:8081  # Si no usás Nginx
# O simplemente abrir http://secmautos.test:8081 en Laragon
```

### Revisar logs de errores
```bash
tail -f C:/laragon/tmp/php_errors.log
tail -f logs/php_errors.log
```

### Importar base de datos
```bash
mysql -u root secmautos < db/install.sql
```

### Ejecutar script de alertas manualmente
```bash
php scripts/generar_alertas.php
```

### Git workflow
```bash
git status
git add .
git commit -m "Descripción del cambio"
git push origin main
```

---

## 📞 Notas y Observaciones

### Arquitectura Técnica
- **Backend:** PHP 8.x vanilla (sin framework)
- **Base de datos:** MySQL 8.x con utf8mb4_spanish_ci
- **Frontend:** HTML5 + Bootstrap 5 + Vanilla JS (ES6+)
- **Patrón:** SPA simple con module switching
- **API:** REST JSON con autenticación por sesión PHP
- **Seguridad:** CSRF tokens, prepared statements, password_hash()

### Datos de Prueba Sugeridos
Crear script `db/datos_prueba.sql` con:
- 5-10 vehículos de ejemplo
- 10 empleados de ejemplo
- Algunas asignaciones históricas
- Multas de prueba
- Fechas de vencimiento variadas (algunas próximas para probar alertas)

### Mejoras Futuras (Post-MVP)
- Integración con API de GCBA para consulta de multas
- Notificaciones por email/WhatsApp
- App móvil (PWA)
- Firma digital de documentos
- OCR para leer patentes de comprobantes
- Dashboard con gráficos (Chart.js)
- Exportar timeline de vehículo
- Geolocalización de vehículos (si tienen GPS)

---

## 🎯 Próximo Paso Inmediato

**Cuando retomes el proyecto:**

1. Commitear cambios pendientes:
```bash
cd C:/laragon/www/secmautos
git add assets/css/themes.css login.jpg logout.php
git commit -m "Fix: CSS comment, optimize login image, add logout page"
git push origin main
```

2. Empezar por **FASE 1: Completar Backend API**
   - Abrir `api/vehiculos.php`
   - Agregar case 'PUT' para editar
   - Agregar case 'DELETE' para eliminar (cambiar estado a 'baja')
   - Probar con Postman o cURL

3. Continuar con **FASE 2: Frontend Vehículos**
   - Crear `modules/vehiculos.html`
   - Crear `assets/js/vehiculos.js`
   - Integrar en `dashboard.js`

---

**Última actualización:** 2026-01-09
**Autor:** Claude Sonnet 4.5 + Sergio Cabrera
