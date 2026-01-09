# 🚗 SECM Autos - Hoja de Ruta de Desarrollo

**Proyecto:** Sistema de Gestión de Flota Automotor
**Fecha inicio:** 2026-01-09
**Última actualización:** 2026-01-09 (Sesión actual)
**Estado:** Base de datos completa ✅ | Backend API completo ✅ | Frontend 85% ⚠️

## 🎉 PROGRESO ACTUAL

### ✅ FASE 1 COMPLETADA - Backend API (100%)
- PUT/DELETE agregados a: vehiculos.php, empleados.php, multas.php, asignaciones.php
- Nuevos endpoints creados: compras.php, ventas.php, ceta.php, transferencias.php
- Script de alertas automáticas: scripts/generar_alertas.php
- **Commit:** `278793a` - 897 líneas agregadas

### ✅ FASE 2 COMPLETADA - Frontend Módulo Vehículos (100%)
- modules/vehiculos.html - Tabla + Modal formulario Bootstrap 5
- assets/js/vehiculos.js - Clase VehiculosView con CRUD completo
- dashboard.js modificado - Carga dinámica de módulos
- index.php actualizado - Meta CSRF + Bootstrap 5 CDN
- **Commit:** `6b1fd1e` - 388 líneas agregadas
- **Funcional:** Crear, editar, eliminar, listar, filtrar vehículos ✅

### ✅ FASE 3 COMPLETADA - Frontend Módulo Empleados (100%)
- modules/empleados.html - Tabla + Modal formulario (6 campos)
- assets/js/empleados.js - Clase EmpleadosView con CRUD completo
- dashboard.js modificado - Carga de módulo empleados
- **Commit:** `c2009d4` - 295 líneas agregadas
- **Funcional:** Crear, editar, eliminar, listar, filtrar empleados ✅

### ✅ FASE 4 COMPLETADA - Frontend Módulo Asignaciones (100%)
- modules/asignaciones.html - Tabla + Formulario de asignación
- assets/js/asignaciones.js - CRUD de asignaciones + devolución de vehículos
- **Funcional:** Asignar vehículos a empleados, devolver con km ✅

### ✅ FASE 5 COMPLETADA - Frontend Módulo Multas (100%)
- modules/multas.html - Tabla + Formulario de multas
- assets/js/multas.js - CRUD de multas + marcar como pagada
- **Funcional:** Registrar multas, asignar responsable, marcar pagadas ✅

### ✅ FASE 6 COMPLETADA - Frontend Módulo Compras/Ventas (100%)
- modules/compras_ventas.html - Tablas + Formularios de compra y venta
- assets/js/compras_ventas.js - CRUD de compras y ventas
- **Funcional:** Registrar compras, registrar ventas (auto-baja vehículo) ✅

### ✅ FASE 7 COMPLETADA - Frontend Módulo CETA (100%)
- modules/ceta.html - Tabla + Formulario CETA
- assets/js/ceta.js - CRUD de cédulas azules
- **Funcional:** Gestionar cédulas azules (CETA) por vehículo ✅

### ✅ FASE 8 COMPLETADA - Frontend Módulo Transferencias (100%)
- modules/transferencias.html - Tabla + Formulario transferencias
- assets/js/transferencias.js - CRUD de transferencias
- **Funcional:** Registrar trámites de transferencia de dominio ✅

### ✅ FASE 9 COMPLETADA - Frontend Módulo Mantenimientos (100%)
- modules/mantenimientos.html - Tabla + Formulario mantenimientos
- assets/js/mantenimientos.js - CRUD de mantenimientos
- **Funcional:** Registrar mantenimientos preventivos y correctivos ✅

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

#### 2. **API Backend - PHP 8.x** (17 endpoints COMPLETOS ✅)

| Endpoint | Métodos | Estado | Funcionalidad |
|----------|---------|--------|---------------|
| `api/auth.php` | - | ✅ | Login, logout, roles, logs |
| `api/login_handler.php` | POST | ✅ | Procesa login con CAPTCHA |
| `api/logout.php` | POST | ✅ | Cierra sesión |
| `api/vehiculos.php` | GET, POST, PUT, DELETE | ✅ | CRUD completo de vehículos |
| `api/empleados.php` | GET, POST, PUT, DELETE | ✅ | CRUD completo de empleados |
| `api/asignaciones.php` | GET, POST, PUT | ✅ | Asignar + devolver vehículos |
| `api/multas.php` | GET, POST, PUT | ✅ | Registrar + marcar pagada |
| `api/mantenimientos.php` | GET, POST | ✅ | Listar + crear mantenimientos |
| `api/pagos.php` | GET, POST | ✅ | Listar + crear pagos |
| `api/compras.php` | GET, POST, PUT | ✅ | Gestión de compras |
| `api/ventas.php` | GET, POST, PUT | ✅ | Gestión de ventas (auto-baja vehículo) |
| `api/ceta.php` | GET, POST, PUT | ✅ | Gestión de CETA |
| `api/transferencias.php` | GET, POST, PUT | ✅ | Gestión de transferencias |
| `api/stats.php` | GET | ✅ | Estadísticas dashboard |
| `api/alertas.php` | GET | ✅ | Alertas activas |
| `api/vencimientos.php` | GET | ✅ | Vencimientos próximos |
| `api/refresh_captcha.php` | GET | ✅ | Regenerar CAPTCHA |

**Scripts auxiliares:**
- ✅ `scripts/generar_alertas.php` - Cron job para alertas automáticas (VTV, seguro, patente, CETA, KM, multas)

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

**FALTA EN FRONTEND (15%):**
- ❌ Módulo Pagos (formulario + tabla)
- ❌ Módulo de reportes (exportar Excel, PDF)
- ❌ Ficha completa de vehículo (historial, documentos)
- ❌ Subida de comprobantes (PDF/imágenes)
- ❌ Mejoras UX/UI (notificaciones toast, loading spinners, paginación)
- ❌ Testing completo de todos los módulos
- ❌ Documentación y deployment

#### 4. **Cambios Pendientes de Git**

```bash
Modified:   assets/css/themes.css  (1 línea - cierre de comentario CSS)
Modified:   login.jpg              (cambio binario - imagen optimizada)
Untracked:  logout.php             (nuevo archivo funcional)
```

---

## 🎯 Plan de Implementación - Fase por Fase

### **FASE 1: Completar Backend API** ✅ COMPLETADO
- PUT/DELETE agregados a todos los endpoints
- Nuevos endpoints creados: compras.php, ventas.php, ceta.php, transferencias.php
- Script de alertas automáticas: scripts/generar_alertas.php
- **Commit:** `278793a` - 897 líneas agregadas

---

### **FASE 2: Frontend - Módulo Vehículos** ✅ COMPLETADO
- **Commit:** `6b1fd1e` - 388 líneas agregadas
- **Funcional:** Crear, editar, eliminar, listar, filtrar vehículos ✅

### **FASE 3: Frontend - Módulo Empleados** ✅ COMPLETADO
- **Commit:** `c2009d4` - 295 líneas agregadas
- **Funcional:** Crear, editar, eliminar, listar, filtrar empleados ✅

### **FASE 4: Frontend - Módulo Asignaciones** ✅ COMPLETADO
- **Archivos:** modules/asignaciones.html, assets/js/asignaciones.js
- **Funcional:** Asignar vehículos, devolver con km ✅

### **FASE 5: Frontend - Módulo Multas** ✅ COMPLETADO
- **Archivos:** modules/multas.html, assets/js/multas.js
- **Funcional:** Registrar multas, marcar como pagadas ✅

### **FASE 6: Frontend - Módulo Compras/Ventas** ✅ COMPLETADO
- **Archivos:** modules/compras_ventas.html, assets/js/compras_ventas.js
- **Funcional:** Registrar compras, ventas (auto-baja vehículo) ✅

### **FASE 7: Frontend - Módulo CETA** ✅ COMPLETADO
- **Archivos:** modules/ceta.html, assets/js/ceta.js
- **Funcional:** Gestionar cédulas azules ✅

### **FASE 8: Frontend - Módulo Transferencias** ✅ COMPLETADO
- **Archivos:** modules/transferencias.html, assets/js/transferencias.js
- **Funcional:** Registrar trámites de transferencia ✅

### **FASE 9: Frontend - Módulo Mantenimientos** ✅ COMPLETADO
- **Archivos:** modules/mantenimientos.html, assets/js/mantenimientos.js
- **Funcional:** Registrar mantenimientos preventivos y correctivos ✅

### **FASE 10: Frontend - Módulo Pagos** ⚠️ PENDIENTE

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

## 📁 Estructura de Archivos Actual

```
secmautos/
├── api/
│   ├── auth.php ✅
│   ├── login_handler.php ✅
│   ├── logout.php ✅
│   ├── vehiculos.php ✅
│   ├── empleados.php ✅
│   ├── asignaciones.php ✅
│   ├── multas.php ✅
│   ├── mantenimientos.php ✅
│   ├── pagos.php ✅
│   ├── compras.php ✅
│   ├── ventas.php ✅
│   ├── transferencias.php ✅
│   ├── ceta.php ✅
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
│   │   └── themes.css ✅
│   ├── js/
│   │   ├── dashboard.js ✅
│   │   ├── login.js ✅
│   │   ├── theme-switcher.js ✅
│   │   ├── vehiculos.js ✅
│   │   ├── empleados.js ✅
│   │   ├── asignaciones.js ✅
│   │   ├── multas.js ✅
│   │   ├── compras_ventas.js ✅
│   │   ├── ceta.js ✅
│   │   ├── transferencias.js ✅
│   │   ├── mantenimientos.js ✅
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
│   ├── vehiculos.html ✅
│   ├── empleados.html ✅
│   ├── asignaciones.html ✅
│   ├── multas.html ✅
│   ├── compras_ventas.html ✅
│   ├── ceta.html ✅
│   ├── transferencias.html ✅
│   ├── mantenimientos.html ✅
│   ├── pagos.html ❌
│   ├── ficha_vehiculo.html ❌
│   └── reportes.html ❌
├── scripts/
│   └── generar_alertas.php ✅
├── sessions/
├── uploads/
│   ├── compras/ ❌
│   ├── ventas/ ❌
│   ├── pagos/ ❌
│   └── mantenimientos/ ❌
├── .env ✅
├── .env.example ✅
├── .gitignore ✅
├── bootstrap.php ✅
├── diagnostico.php ✅
├── index.php ✅
├── login.php ✅
├── logout.php ✅
├── licence.php ✅
├── README.md ✅
└── HOJA_DE_RUTA.md ✅ (este archivo)
```

---

## ⏱️ Estimación de Tiempo Total

| Fase | Descripción | Horas | Estado | Prioridad |
|------|-------------|-------|--------|-----------|
| 1 | Completar Backend API | 3h | ✅ Completado | 🔴 Alta |
| 2 | Frontend - Vehículos | 4h | ✅ Completado | 🔴 Alta |
| 3 | Frontend - Empleados | 2h | ✅ Completado | 🔴 Alta |
| 4 | Frontend - Asignaciones | 3h | ✅ Completado | 🟠 Media |
| 5 | Frontend - Multas | 2h | ✅ Completado | 🟠 Media |
| 6 | Frontend - Compra/Venta | 3h | ✅ Completado | 🟠 Media |
| 7 | Frontend - CETA | 2h | ✅ Completado | 🟠 Media |
| 8 | Frontend - Transferencias | 2h | ✅ Completado | 🟢 Baja |
| 9 | Frontend - Mantenimientos | 2h | ✅ Completado | 🟠 Media |
| 10 | Frontend - Pagos | 2h | ⚠️ Pendiente | 🟠 Media |
| 11 | Ficha Completa Vehículo | 3h | ⚠️ Pendiente | 🟠 Media |
| 12 | Reportes y Exportación | 3h | ⚠️ Pendiente | 🟢 Baja |
| 13 | Subida de Archivos | 2h | ⚠️ Pendiente | 🟢 Baja |
| 14 | Mejoras UX/UI | 2h | ⚠️ Pendiente | 🟢 Baja |
| 15 | Testing y Ajustes | 3h | ⚠️ Pendiente | 🔴 Alta |
| 16 | Documentación y Deployment | 2h | ⚠️ Pendiente | 🟢 Baja |
| **TOTAL** | | **40h** | **40h completadas** | |

**⚡ Progreso actual: 25h / 40h (62.5% completado)**

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

**Para completar el sistema, seguir este orden:**

### Sprint 1 - Módulo Pagos (2 horas)
1. Crear `modules/pagos.html` - Tabla + Formulario
2. Crear `assets/js/pagos.js` - CRUD de pagos
3. Integrar en `dashboard.js`
4. Probar registro y marcado como pagado

### Sprint 2 - Ficha Completa de Vehículo (3 horas)
1. Crear `modules/ficha_vehiculo.html` - Vista completa del vehículo
2. Crear `assets/js/ficha_vehiculo.js` - Cargar historial y documentos
3. Integrar botón "Ver ficha" en módulo vehículos

### Sprint 3 - Reportes y Exportación (3 horas)
1. Crear `api/reportes/` directorio
2. Crear `api/reportes/excel_gcba.php` - Exportar listado para GCBA
3. Crear `api/reportes/pdf_dominio.php` - Exportar informe de dominio
4. Crear `modules/reportes.html` - Interfaz de reportes
5. Crear `assets/js/reportes.js` - Manejar exportaciones

### Sprint 4 - Subida de Archivos (2 horas)
1. Crear directorio `uploads/` con subcarpetas
2. Crear API para upload de archivos
3. Agregar campos de archivo en formularios de compras, ventas, pagos, mantenimientos
4. Implementar descarga de archivos

### Sprint 5 - Mejoras UX/UI (2 horas)
1. Reemplazar `alert()` con notificaciones toast
2. Agregar loading spinners
3. Implementar paginación en tablas grandes
4. Agregar confirmaciones antes de eliminar
5. Mejorar validación de formularios

### Sprint 6 - Testing y Ajustes (3 horas)
1. Probar todos los formularios exhaustivamente
2. Verificar alertas automáticas
3. Revisar permisos por rol
4. Ajustar queries SQL para performance
5. Validar cálculo de vencimientos
6. Probar en diferentes navegadores

### Sprint 7 - Documentación y Deployment (2 horas)
1. Actualizar `README.md` completo
2. Documentar API endpoints
3. Configurar `.htaccess` para producción
4. Configurar cron jobs
5. Generar datos de prueba
6. Backup de base de datos

---

**Última actualización:** 2026-01-09
**Autor:** Sergio Cabrera
**Estado actual:** 62.5% completado (25h / 40h)
