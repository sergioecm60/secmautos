# 🚗 SECM Autos - Sistema de Gestión de Flota Automotor

**Versión:** 1.0.0
**Fecha:** Enero 2026
**Autor:** Sergio Cabrera
**Licencia:** GNU GPL v3

## 📋 Descripción

Sistema completo para la gestión de flota de vehículos automotores de la Secretaría de Educación, Cultura y Municipios (SECM).

**Funcionalidades principales:**
- Gestión de vehículos (altas, bajas, modificaciones)
- Control de asignaciones a conductores
- Registro de multas con responsables
- Gestión de pagos (patente, seguro, servicios)
- Control de mantenimientos
- Compras y ventas de vehículos
- Gestión de transferencias de dominio
- Cédulas azules (CETA)
- Sistema de usuarios con roles
- Reportes y exportaciones
- Alertas automáticas de vencimientos

## 🚀 Instalación

### Requisitos

- PHP 8.x o superior
- MySQL 8.x o MariaDB 10.4+
- Servidor web (Apache o Nginx)
- Composer (para dependencias de PHP)
- Navegador moderno (Chrome, Firefox, Edge, Safari)

### Pasos de Instalación

1. **Clonar o descargar el repositorio:**
   ```bash
   git clone https://github.com/usuario/secmautos.git
   cd secmautos
   ```

2. **Configurar base de datos:**
   ```bash
   # Crear la base de datos
   mysql -u root -p
   CREATE DATABASE secmautos CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
   
   # Importar la estructura
   mysql -u root -p secmautos < db/install.sql
   ```

3. **Configurar archivo de entorno:**
   ```bash
   cp .env.example .env
   # Editar .env con tus credenciales de base de datos
   ```

4. **Ejecutar migraciones:**
   ```bash
   # Ejecutar cada migration manualmente o crear un script
   php db/migrations/2026-01-12_add_username.sql
   php db/migrations/2026-01-12_nombre_apellido_opcional.sql
   ```

5. **Configurar permisos:**
   ```bash
   chmod 755 api/
   chmod 644 api/*.php
   chmod 777 logs/ sessions/
   ```

6. **Configurar servidor web:**
   ```apache
   # Apache
   <VirtualHost *:80>
       DocumentRoot /ruta/a/secmautos
       <Directory /ruta/a/secmautos>
           Options -Indexes +FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

   ```nginx
   # Nginx
   server {
       listen 80;
       server_name secmautos.test;
       root /ruta/a/secmautos;
       index index.php;
       
       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }
       
       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
           fastcgi_index index.php;
           include fastcgi_params;
       }
   }
   ```

## 📁 Estructura del Proyecto

```
secmautos/
├── api/                    # API REST
│   ├── auth.php             # Autenticación
│   ├── usuarios.php          # Gestión de usuarios
│   ├── vehiculos.php         # CRUD vehículos
│   ├── empleados.php         # CRUD empleados
│   ├── asignaciones.php     # Asignaciones de vehículos
│   ├── multas.php            # CRUD multas
│   ├── mantenimientos.php    # CRUD mantenimientos
│   ├── pagos.php             # CRUD pagos
│   ├── compras.php           # CRUD compras
│   ├── ventas.php            # CRUD ventas
│   ├── ceta.php              # CRUD cédulas azules
│   ├── transferencias.php     # Transferencias de dominio
│   ├── stats.php             # Estadísticas
│   ├── alertas.php           # Alertas activas
│   ├── vencimientos.php      # Próximos vencimientos
│   ├── cambiar_password.php   # Cambio de contraseña
│   ├── refresh_captcha.php    # Generar CAPTCHA
│   └── reportes/             # Reportes y exportaciones
│       ├── listado_gcba.php
│       └── pdf_dominio.php
├── assets/
│   ├── css/
│   │   ├── bootstrap.min.css
│   │   ├── style.css
│   │   ├── themes.css
│   │   └── reportes.css     # Estilos para reportes
│   ├── js/
│   │   ├── dashboard.js      # Navegación principal
│   │   ├── login.js
│   │   ├── theme-switcher.js
│   │   ├── usuarios.js
│   │   ├── vehiculos.js
│   │   ├── empleados.js
│   │   ├── asignaciones.js
│   │   ├── multas.js
│   │   ├── mantenimientos.js
│   │   ├── pagos.js
│   │   ├── compras_ventas.js
│   │   ├── transferencias.js
│   │   └── configuracion.js
│   └── img/
│       ├── logo.png
│       ├── favicon.svg
│       └── favicon.ico
├── config/
│   ├── database.php          # Conexión a base de datos
│   └── config.php            # Configuración general
├── db/
│   ├── install.sql           # Estructura inicial
│   └── migrations/          # Migraciones de base de datos
├── logs/
│   ├── php_errors.log
│   └── alertas.log
├── modules/
│   ├── dashboard.html        # Panel principal
│   ├── usuarios.html
│   ├── vehiculos.html
│   ├── empleados.html
│   ├── asignaciones.html
│   ├── multas.html
│   ├── mantenimientos.html
│   ├── pagos.html
│   ├── compras_ventas.html
│   ├── transferencias.html
│   ├── configuracion.html
│   └── ficha_vehiculo.html   # Vista completa de vehículo
├── scripts/
│   └── generar_alertas.php  # Cron job para alertas
├── sessions/
├── bootstrap.php            # Inicialización del sistema
├── index.php                # Dashboard principal
├── login.php                # Página de login
├── logout.php
├── diagnostico.php          # Diagnóstico del sistema
└── licence.php
```

## 🏗️ Arquitectura Técnica

### Backend
- **PHP 8.x** vanilla (sin frameworks)
- **MySQL 8.x** con collation utf8mb4_spanish_ci
- **API REST** JSON
- **Autenticación** por sesión PHP
- **CSRF protection** en todos los formularios
- **Prepared statements** para prevención de SQL Injection

### Frontend
- **HTML5** semántico
- **Bootstrap 5.3** (CDN)
- **Bootstrap Icons** (CDN)
- **Vanilla JavaScript** ES6+
- **SPA simple** con module switching
- **Sistema de temas** (claro, oscuro, automático)

### Base de Datos
- **14 tablas** relacionales
- **Índices** optimizados
- **Logs de auditoría** completos
- **Triggers** para actualización de timestamps

## 👤 Usuarios y Permisos

### Roles de Usuario

| Rol | Permisos |
|-----|-----------|
| **superadmin** | Acceso total a todas las funcionalidades, gestión de usuarios |
| **admin** | Gestión completa del sistema excepto gestión de usuarios |
| **user** | Acceso a todas las funcionalidades excepto configuración y gestión de usuarios |

### Usuario por Defecto

- **Usuario:** `admin` (el username, no email)
- **Contraseña:** `admin123`
- **Rol:** superadmin

**IMPORTANTE:** Cambiar la contraseña inmediatamente después del primer login.

## 🚢 Uso del Sistema

### Login
1. Ir a `http://localhost/secmautos/login.php`
2. Ingresar usuario y contraseña
3. Resolver el CAPTCHA matemático
4. Presionar "Iniciar Sesión"

### Dashboard
El panel principal muestra:
- Estadísticas generales
- Alertas activas (vencimientos, documentos, etc.)
- Vehículos por estado
- Asignaciones activas
- Próximos vencimientos

### Navegación
Menú lateral con acceso a:
- 🚗 Vehículos
- 👥 Empleados
- 🔄 Asignaciones
- ⚠️ Multas
- 🔧 Mantenimientos
- 💳 Pagos
- 🛒 Compras/Ventas
- 📄 Transferencias
- 🔷 CETA
- 👤 Usuarios
- ⚙️ Configuración
- 📊 Reportes

## 🔒 Seguridad

### Medidas de Seguridad Implementadas

1. **Autenticación robusta**
   - Sistema de login con CAPTCHA matemático
   - Bloqueo de IP después de 5 intentos fallidos (15 minutos)
   - Bloqueo de usuario después de 5 intentos fallidos (15 minutos)
   - Login por username (más seguro que email)
   - Sesión con timeout de 30 minutos

2. **Protección CSRF**
   - Tokens CSRF en todos los formularios
   - Verificación en cada petición POST/PUT/DELETE

3. **Protección SQL Injection**
   - Todos los queries usan prepared statements
   - Función `sanitizar_input()` para limpieza de datos

4. **Protección XSS**
   - Uso de `textContent` en lugar de `innerHTML`
   - Sanitización de inputs
   - Headers de seguridad HTTP

5. **Control de accesos**
   - Roles de usuario
   - Verificación de autenticación en cada página
   - Logs de auditoría completos

6. **Validación de contraseñas**
   - Mínimo 6 caracteres
   - Hash con `password_hash()` (bcrypt)
   - Historial de contraseñas no implementado

7. **Protección de archivos**
   - `.htaccess` para proteger directorios sensibles
   - Archivos `.env` y `config/` inaccesibles desde web

### Medidas NO Implementadas (según usuario)

- ❌ Autenticación de doble factor (2FA)
- ❌ Web Application Firewall (WAF)
- ❌ Escaneos de seguridad automáticos
- ❌ IP whitelisting para administradores
- ❌ Backups encriptados
- ❌ Rotación de secretos

### Recomendaciones de Seguridad

- Configurar HTTPS en producción
- Usar contraseñas fuertes (mínimo 12 caracteres)
- Rotar contraseñas periódicamente
- Mantener PHP y MySQL actualizados
- Configurar backups automáticos de la base de datos
- Usar fail2ban para bloqueo de IPs maliciosas
- Implementar firewall en servidor

## 📊 Reportes

### Reportes Disponibles

1. **Listado GCBA**
   - Todos los vehículos con estado de documentación
   - Filtros por estado (disponible, asignado, baja)
   - Exportable a HTML

2. **Informe de Dominio**
   - Historial completo de un vehículo
   - Asignaciones, multas, mantenimientos, pagos
   - Compra/venta si aplica
   - Resumen económico
   - Diseño estilo cristal para impresión

3. **Multas por Empleado**
   - Listado de multas agrupadas por responsable
   - Totales de monto
   - Estado de pago

4. **Vencimientos del Mes**
   - Documentos próximos a vencer
   - Pagos pendientes
   - Alertas programadas

5. **Asignaciones por Período**
   - Histórico de asignaciones
   - Filtros por fechas
   - Kilometrajes recorridos

## ⚙️ Configuración

### Variables de Entorno (.env)

```bash
# Base de datos
DB_HOST=localhost
DB_NAME=secmautos
DB_USER=secmautos_user
DB_PASS=tu_contraseña_segura

# Configuración general
SITE_URL=http://localhost/secmautos
SITE_NAME=SECM Flota
TIMEZONE=America/Argentina/Buenos_Aires

# Seguridad
MAX_INTENTOS_USUARIO=5
MAX_INTENTOS_IP=5
BLOQUEO_USUARIO_MINUTOS=15
BLOQUEO_IP_MINUTOS=15
```

### Configuración de Alertas Automáticas

```php
// En scripts/generar_alertas.php configurar:
- Días de antelación para VTV (por defecto 30)
- Días de antelación para seguro (por defecto 15)
- Días de antelación para patente (por defecto 10)
- Días de antelación para CETA (por defecto 45)
- Días de antelación para pagos (por defecto 7)
- Umbral de KM para alerta (por defecto 50000)
```

## 🐛 Solución de Problemas Comunes

### Errores de PHP

**Error:** "Class 'PDO' not found"
**Solución:** Instalar PDO para MySQL
```bash
# Debian/Ubuntu
sudo apt-get install php8.2-mysql

# Reiniciar servidor web
sudo systemctl restart apache2
```

### Errores de Base de Datos

**Error:** "Access denied for user"
**Solución:** Verificar credenciales en `.env`

### Errores de Sesión

**Error:** "Headers already sent"
**Solución:** No enviar HTML antes de `session_start()`

### Errores de Permisos

**Error:** "Permission denied"
**Solución:**
```bash
chmod 755 api/
chmod 777 logs/ sessions/
```

### Errores de CSRF

**Error:** "Token CSRF inválido"
**Solución:** Limpiar cookies y recargar página

## 📞 Soporte

**Autor:** Sergio Cabrera
**Email:** sergiomiers@gmail.com
**WhatsApp:** +54 11 6759-8452
**Licencia:** GNU GPL v3

## 📄 Licencia

Este proyecto está licenciado bajo la Licencia Pública General de GNU versión 3.
Consulte el archivo `licence.php` para más detalles.

## 🔄 Versionamiento

- **1.0.0** (Enero 2026) - Versión inicial estable
  - Todas las funcionalidades core implementadas
  - Sistema de usuarios con roles
  - Reportes y exportaciones
  - Seguridad completa implementada

## 🚀 Próximas Características (Roadmap)

- [ ] Subida de comprobantes (PDF/imágenes)
- [ ] Notificaciones por email/WhatsApp
- [ ] App móvil PWA
- [ ] Dashboard con gráficos (Chart.js)
- [ ] Exportación a Excel (PhpSpreadsheet)
- [ ] Geolocalización de vehículos
- [ ] Firma digital de documentos
- [ ] OCR para lectura de patentes

## 📝 Notas de Desarrollo

Para desarrolladores que quieran contribuir:

1. Fork del proyecto
2. Crear rama de feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commits claros y descriptivos
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Pull Request a main

## ⚠️ Advertencia

Este software se proporciona "tal cual", sin garantía de ningún tipo. El uso de este software es bajo su propia responsabilidad.

---

**Copyright © 2025 Sergio Cabrera - Copyleft GNU GPL v3**
