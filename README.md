# 🚗 SECM Autos - Sistema de Gestión de Flota Automotor

**Versión:** 1.0.0  
**Fecha:** Enero 2026  
**Autor:** Sergio Cabrera  
**Licencia:** GNU GPL v3

---

## 📋 Descripción

Sistema completo para la gestión de flota de vehículos automotores de la Secretaría de Educación, Cultura y Municipios (SECM).

---

## 🚀 Instalación

### Requisitos

- **PHP 8.x** o superior
- **MySQL 8.x** o MariaDB 10.4+
- **Servidor web:** Apache o Nginx
- **Composer** (para dependencias de PHP)
- **Navegador moderno:** Chrome, Firefox, Edge, Safari

### Pasos de Instalación

1. **Clonar o descargar el repositorio:**
    ```bash
    git clone https://github.com/usuario/secmautos.git
    cd secmautos
    ```

2. **Configurar base de datos:**
    ```bash
    # Crear base de datos
    mysql -u root -p
    CREATE DATABASE secmautos CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
    
    # Importar estructura
    mysql -u root -p secmautos < db/install.sql
    ```

3. **Configurar archivo de entorno (.env):**
    ```bash
    cp .env.example .env
    nano .env
    ```
    
    Editar con tus credenciales:
    ```bash
    # Base de datos
    DB_HOST=localhost
    DB_NAME=secmautos
    DB_USER=secmautos_user
    DB_PASS=tu_contraseña_segura
    DB_CHARSET=utf8mb4
    
    # Configuración general
    SITE_URL=http://localhost/secmautos
    SITE_NAME=SECM Autos
    TIMEZONE=America/Argentina/Buenos_Aires
    ```

4. **Configurar permisos:**
    ```bash
    chmod 755 api/
    chmod 755 assets/js/
    chmod 755 assets/css/
    chmod 777 logs/
    chmod 777 sessions/
    ```

5. **Configurar servidor web:**

    **Apache:**
    ```apache
    <VirtualHost *:80>
        ServerName secmautos.local
        DocumentRoot /ruta/a/secmautos
        <Directory /ruta/a/secmautos>
            Options -Indexes +FollowSymLinks
            AllowOverride All
            Require all granted
        </Directory>
    </VirtualHost>
    ```
    
    **Nginx:**
    ```nginx
    server {
        listen 80;
        server_name secmautos.local;
        root /ruta/a/secmautos;
        index index.php;
        
        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }
        
        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
            fastcgi_index index.php;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        }
    }
    ```

6. **Reiniciar servicios:**
    ```bash
    # Apache
    sudo systemctl restart apache2
    
    # Nginx + PHP-FPM
    sudo systemctl restart nginx
    sudo systemctl restart php8.2-fpm
    ```

---

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
│   └── refresh_captcha.php    # Generar CAPTCHA
├── assets/
│   ├── css/
│   │   ├── bootstrap.min.css
│   │   ├── style.css
│   │   ├── themes.css
│   │   └── reportes.css
│   └── js/
│       ├── dashboard.js      # Navegación principal
│       ├── login.js
│       ├── theme-switcher.js
│       ├── usuarios.js
│       ├── vehiculos.js
│       ├── empleados.js
│       ├── asignaciones.js
│       ├── multas.js
│       ├── mantenimientos.js
│       ├── pagos.js
│       ├── compras_ventas.js
│       ├── transferencias.js
│       └── configuracion.js
├── config/
│   ├── database.php          # Conexión a base de datos
│   └── config.php            # Configuración general
├── db/
│   ├── install.sql           # Estructura inicial de la BD
│   └── migrations/          # Migraciones de la BD
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
└── templates/
    └── index.php              # Dashboard principal
        ├── login.php
        ├── logout.php
        ├── licence.php
        └── diagnostico.php
```

---

## 👤 Usuarios y Permisos

### Roles de Usuario

| Rol | Permisos |
|-----|-----------|
| **superadmin** | Acceso total a todas las funcionalidades, gestión de usuarios |
| **admin** | Gestión completa del sistema excepto gestión de usuarios |
| **user** | Acceso a todas las funcionalidades excepto configuración y gestión de usuarios |

### Usuario por Defecto

- **Usuario:** `admin`
- **Email:** `admin@secmautos.com`
- **Contraseña:** `admin123`
- **Rol:** `superadmin`
- **IMPORTANTE:** Cambiar la contraseña inmediatamente después del primer login

---

## 🔒 Seguridad

### Medidas de Seguridad Implementadas

1. **Autenticación robusta:**
   - Sistema de login con CAPTCHA matemático
   - Bloqueo de IP después de 5 intentos fallidos
   - Bloqueo de usuario después de 5 intentos fallidos
   - Timeout de sesión de 30 minutos
   - Protección CSRF en todos los formularios

2. **Protección SQL Injection:**
   - Todos los queries usan prepared statements
   - Función `sanitizar_input()` para limpieza de datos
   - Validación de tipos de datos

3. **Control de accesos:**
   - Roles y permisos por usuario
   - Verificación de autenticación en cada página
   - Logs de auditoría completos en tabla `logs`

4. **Protección de archivos:**
   - Archivos `.env` y `config/` protegidos del acceso web
   - Headers de seguridad HTTP
   - Protección XSS mediante `textContent` en lugar de `innerHTML`

---

## ⚠️ Errores Comunes y Soluciones

### Errores de PHP

**Error:** `Class 'PDO' not found`
**Solución:** Instalar PDO para MySQL
```bash
# Debian/Ubuntu
sudo apt-get install php8.2-mysql

# CentOS/RHEL
sudo yum install php82-mysqlnd

# Reiniciar servicios
sudo systemctl restart php8.2-fpm
```

**Error:** `Headers already sent`
**Solución:** No enviar HTML antes de `session_start()`

**Error:** `Permission denied` (Acceso denegado)
**Solución:**
```bash
# Verificar permisos
chmod 755 api/
chmod 777 logs/
chmod 777 sessions/

# Configurar propietario
sudo chown -R www-data:www-data /ruta/a/secmautos
```

### Errores de Base de Datos

**Problema:** Se ejecutó accidentalmente `DROP DATABASE` y se perdieron datos
**Opción 1 - Recrear la base de datos desde cero:**
```bash
mysql -u secmautos_user -p -e "DROP DATABASE IF EXISTS secmautos; CREATE DATABASE secmautos CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;"
mysql -u secmautos_user -p secmautos < db/install.sql
```
**NOTA:** Esto eliminará TODOS los datos existentes (usuarios, vehículos, asignaciones, etc.)

**Opción 2 - Restaurar desde un respaldo:**
```bash
# Buscar dumps de backup disponibles
ls -lth /ruta/a/backups/secmautos/

# Restaurar desde el más reciente
mysql -u secmautos_user -p secmautos < /ruta/a/backups/secmautos/secmautos_ultimo.sql
```
**NOTA:** Solo usar esta opción si tienes un respaldo completo (`.sql` o `.sql.gz`) de la base de datos.

**Opción 3 - Restaurar solo tablas afectadas:**
```bash
# Obtener lista de tablas actuales
mysql -u secmautos_user -p -e "SHOW TABLES FROM secmautos;" | grep -v "Tables_in_secmautos"

# Para cada tabla que se borró, recrear solo esa tabla
# Ejemplo: solo si se borraron usuarios
mysql -u secmautos_user -p secmautos -e "
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('superadmin', 'admin', 'user') DEFAULT 'user',
    activo BOOLEAN DEFAULT TRUE,
    intentos_fallidos INT DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    ultimo_acceso DATETIME,
    primer_login BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

# Usuario admin inicial
INSERT IGNORE INTO usuarios (nombre, apellido, email, password_hash, rol, activo, primer_login) VALUES
('Admin', 'Sistema', 'admin@secmautos.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', TRUE, FALSE);
"
```

**Opción 4 - Verificar datos existentes antes de borrar:**
```bash
# Contar usuarios
mysql -u secmautos_user -p secmautos -e "SELECT COUNT(*) as total FROM usuarios;"

# Verificar si hay datos antes de borrar
if [ $total -eq 0 ]; then
    echo "⚠️  ADVERTENCIA: No hay usuarios. Al borrar la base de datos, perderás todos los datos."
    echo "¿Deseas continuar? (s/n)"
    read respuesta
    if [ "$respuesta" != "s" ]; then
        echo "Operación cancelada."
        exit 1
    fi
fi
```

### Errores de Sesión

**Error:** `Headers already sent`
**Solución:** Limpiar cookies y recargar página
```javascript
// En el navegador
Ctrl + Shift + Delete // Limpiar cookies
F5 // Recargar página
```

**Error:** `Token CSRF inválido`
**Solución:** Limpiar cookies y recargar página

---

## 🚀 Instalación

### Errores de Base de Datos

**Problema:** Se ejecutó accidentalmente `DROP DATABASE` o se perdieron datos

**Solución Opción 1 - Recrear la base de datos desde cero:**
```bash
# Crear base de datos nueva
mysql -u secmautos_user -p -e "DROP DATABASE IF EXISTS secmautos; CREATE DATABASE secmautos CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;"
    
# Importar la estructura
mysql -u secmautos_user -p secmautos < db/install.sql
```

**Opción 2 - Restaurar desde un respaldo:**
```bash
# Buscar dumps de backup disponibles
ls -lth /ruta/a/backups/secmautos/

# Restaurar desde el más reciente
mysql -u secmautos_user -p secmautos < /ruta/a/backups/secmautos/secmautos_ultimo.sql
```

**NOTA:** Solo usar esta opción si tienes un respaldo completo (`.sql` o `.sql.gz`) de la base de datos.

**Opción 3 - Restaurar solo tablas afectadas:**
```bash
# Obtener lista de tablas actuales
mysql -u secmautos_user -p -e "SHOW TABLES FROM secmautos;" | grep -v "Tables_in_secmautos"

# Para cada tabla que se borró, recrear solo esa tabla
# Ejemplo: solo si se borraron usuarios
mysql -u secmautos_user -p secmautos -e "
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('superadmin', 'admin', 'user') DEFAULT 'user',
    activo BOOLEAN DEFAULT TRUE,
    intentos_fallidos INT DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    ultimo_acceso DATETIME,
    primer_login BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

# Usuario admin inicial
INSERT IGNORE INTO usuarios (nombre, apellido, email, password_hash, rol, activo, primer_login) VALUES
('Admin', 'Sistema', 'admin@secmautos.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', TRUE, FALSE);
"
```

**Opción 4 - Verificar datos existentes antes de borrar:**
```bash
# Contar usuarios
mysql -u secmautos_user -p -e "SELECT COUNT(*) as total FROM usuarios;"

# Verificar si hay datos antes de borrar
if [ $total -eq 0 ]; then
    echo "⚠️  ADVERTENCIA: No hay usuarios. Al borrar la base de datos, perderás todos los datos."
    echo "¿Deseas continuar? (s/n)"
    read respuesta
    if [ "$respuesta" != "s" ]; then
        echo "Operación cancelada."
        exit 1
    fi
fi
```

### Errores de Sesión

**Error:** `Headers already sent`
**Solución:** Limpiar cookies y recargar página
```javascript
// En el navegador
Ctrl + Shift + Delete // Limpiar cookies
F5 // Recargar página
```

**Error:** `Token CSRF inválido`
**Solución:** Limpiar cookies y recargar página

---

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
│   └── refresh_captcha.php    # Generar CAPTCHA
├── assets/
│   ├── css/
│   │   ├── bootstrap.min.css
│   │   ├── style.css
│   │   ├── themes.css
│   │   └── reportes.css
│   └── js/
│       ├── dashboard.js      # Navegación principal
│       ├── login.js
│       ├── theme-switcher.js
│       ├── usuarios.js
│       ├── vehiculos.js
│       ├── empleados.js
│       ├── asignaciones.js
│       ├── multas.js
│       ├── mantenimientos.js
│       ├── pagos.js
│       ├── compras_ventas.js
│       ├── transferencias.js
│       └── configuracion.js
├── config/
│   ├── database.php          # Conexión a base de datos
│   └── config.php            # Configuración general
├── db/
│   ├── install.sql           # Estructura inicial de la BD
│   └── migrations/          # Migraciones de la BD
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
└── templates/
    └── index.php              # Dashboard principal
        ├── login.php
        ├── logout.php
        ├── licence.php
        └── diagnostico.php
```

---

## 👤 Usuarios y Permisos

### Roles de Usuario

| Rol | Permisos |
|-----|-----------|
| **superadmin** | Acceso total a todas las funcionalidades, gestión de usuarios |
| **admin** | Gestión completa del sistema excepto gestión de usuarios |
| **user** | Acceso a todas las funcionalidades excepto configuración y gestión de usuarios |

### Usuario por Defecto

- **Usuario:** `admin`
- **Email:** `admin@secmautos.com`
- **Contraseña:** `admin123`
- **Rol:** `superadmin`
- **IMPORTANTE:** Cambiar la contraseña inmediatamente después del primer login

---

## 🔒 Seguridad

### Medidas de Seguridad Implementadas

1. **Autenticación robusta:**
   - Sistema de login con CAPTCHA matemático
   - Bloqueo de IP después de 5 intentos fallidos
   - Bloqueo de usuario después de 5 intentos fallidos
   - Timeout de sesión de 30 minutos
   - Protección CSRF en todos los formularios

2. **Protección SQL Injection:**
   - Todos los queries usan prepared statements
   - Función `sanitizar_input()` para limpieza de datos
   - Validación de tipos de datos

3. **Control de accesos:**
   - Roles y permisos por usuario
   - Verificación de autenticación en cada página
   - Logs de auditoría completos en tabla `logs`

4. **Protección de archivos:**
   - Archivos `.env` y `config/` protegidos del acceso web
   - Headers de seguridad HTTP
   - Protección XSS mediante `textContent` en lugar de `innerHTML`

---

## ⚠️ Errores Comunes y Soluciones

### Errores de PHP

**Error:** `Class 'PDO' not found`
**Solución:** Instalar PDO para MySQL
```bash
# Debian/Ubuntu
sudo apt-get install php8.2-mysql

# CentOS/RHEL
sudo yum install php82-mysqlnd

# Reiniciar servicios
sudo systemctl restart php8.2-fpm
```

**Error:** `Headers already sent`
**Solución:** No enviar HTML antes de `session_start()`

**Error:** `Permission denied` (Acceso denegado)
**Solución:**
```bash
# Verificar permisos
chmod 755 api/
chmod 777 logs/
chmod 777 sessions/

# Configurar propietario
sudo chown -R www-data:www-data /ruta/a/secmautos
```

### Errores de Base de Datos

**Problema:** Se ejecutó accidentalmente `DROP DATABASE` y se perdieron datos

**Opción 1 - Recrear la base de datos desde cero:**
```bash
# Crear base de datos nueva
mysql -u secmautos_user -p -e "DROP DATABASE IF EXISTS secmautos; CREATE DATABASE secmautos CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;"
    
# Importar la estructura
mysql -u secmautos_user -p secmautos < db/install.sql
```
**NOTA:** Esto eliminará TODOS los datos existentes (usuarios, vehículos, asignaciones, etc.)

**Opción 2 - Restaurar desde un respaldo:**
```bash
# Buscar dumps de backup disponibles
ls -lth /ruta/a/backups/secmautos/

# Restaurar desde el más reciente
mysql -u secmautos_user -p secmautos < /ruta/a/backups/secmautos/secmautos_ultimo.sql
```

**NOTA:** Solo usar esta opción si tienes un respaldo completo (`.sql` o `.sql.gz`) de la base de datos.

**Opción 3 - Restaurar solo tablas afectadas:**
```bash
# Obtener lista de tablas actuales
mysql -u secmautos_user -p -e "SHOW TABLES FROM secmautos;" | grep -v "Tables_in_secmautos"

# Para cada tabla que se borró, recrear solo esa tabla
# Ejemplo: solo si se borraron usuarios
mysql -u secmautos_user -p secmautos -e "
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('superadmin', 'admin', 'user') DEFAULT 'user',
    activo BOOLEAN DEFAULT TRUE,
    intentos_fallidos INT DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    ultimo_acceso DATETIME,
    primer_login BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

# Usuario admin inicial
INSERT IGNORE INTO usuarios (nombre, apellido, email, password_hash, rol, activo, primer_login) VALUES
('Admin', 'Sistema', 'admin@secmautos.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', TRUE, FALSE);
"
```

**Opción 4 - Verificar datos existentes antes de borrar:**
```bash
# Contar usuarios
mysql -u secmautos_user -p -e "SELECT COUNT(*) as total FROM usuarios;"

# Verificar si hay datos antes de borrar
if [ $total -eq 0 ]; then
    echo "⚠️  ADVERTENCIA: No hay usuarios. Al borrar la base de datos, perderás todos los datos."
    echo "¿Deseas continuar? (s/n)"
    read respuesta
    if [ "$respuesta" != "s" ]; then
        echo "Operación cancelada."
        exit 1
    fi
fi
```

### Errores de Sesión

**Error:** `Headers already sent`
**Solución:** Limpiar cookies y recargar página
```javascript
// En el navegador
Ctrl + Shift + Delete // Limpiar cookies
F5 // Recargar página
```

**Error:** `Token CSRF inválido`
**Solución:** Limpiar cookies y recargar página

---

## 🚀 Instalación

### Errores de Base de Datos

**Problema:** Se ejecutó accidentalmente `DROP DATABASE` y se perdieron datos

**Opción 1 - Recrear la base de datos desde cero:**
```bash
# Crear base de datos nueva
mysql -u secmautos_user -p -e "DROP DATABASE IF EXISTS secmautos; CREATE DATABASE secmautos CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;"
    
# Importar la estructura
mysql -u secmautos_user -p secmautos < db/install.sql
```
**NOTA:** Esto eliminará TODOS los datos existentes (usuarios, vehículos, asignaciones, etc.)

**Opción 2 - Restaurar desde un respaldo:**
```bash
# Buscar dumps de backup disponibles
ls -lth /ruta/a/backups/secmautos/

# Restaurar desde el más reciente
mysql -u secmautos_user -p secmautos < /ruta/a/backups/secmautos/secmautos_ultimo.sql
```

**NOTA:** Solo usar esta opción si tienes un respaldo completo (`.sql` o `.sql.gz`) de la base de datos.

**Opción 3 - Restaurar solo tablas afectadas:**
```bash
# Obtener lista de tablas actuales
mysql -u secmautos_user -p -e "SHOW TABLES FROM secmautos;" | grep -v "Tables_in_secmautos"

# Para cada tabla que se borró, recrear solo esa tabla
# Ejemplo: solo si se borraron usuarios
mysql -u secmautos_user -p secmautos -e "
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('superadmin', 'admin', 'user') DEFAULT 'user',
    activo BOOLEAN DEFAULT TRUE,
    intentos_fallidos INT DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    ultimo_acceso DATETIME,
    primer_login BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

# Usuario admin inicial
INSERT IGNORE INTO usuarios (nombre, apellido, email, password_hash, rol, activo, primer_login) VALUES
('Admin', 'Sistema', 'admin@secmautos.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', TRUE, FALSE);
"
```

**Opción 4 - Verificar datos existentes antes de borrar:**
```bash
# Contar usuarios
mysql -u secmautos_user -p -e "SELECT COUNT(*) as total FROM usuarios;"

# Verificar si hay datos antes de borrar
if [ $total -eq 0 ]; then
    echo "⚠️  ADVERTENCIA: No hay usuarios. Al borrar la base de datos, perderás todos los datos."
    echo "¿Deseas continuar? (s/n)"
    read respuesta
    if [ "$respuesta" != "s" ]; then
        echo "Operación cancelada."
        exit 1
    fi
fi
```

---

## 📞 Soporte

### Documentación

- Ver el archivo `licence.php` para más detalles sobre la licencia GNU GPL v3.

### Contacto

- **Autor:** Sergio Cabrera
- **Email:** sergiomiers@gmail.com
- **WhatsApp:** +54 11 6759-8452

---

## 📄 Licencia

Copyright © 2025 Sergio Cabrera - Copyleft GNU GPL v3

Este programa es software libre: puedes redistribuirlo y/o modificarlo bajo los términos de la Licencia Pública General de GNU versión 3.

Este programa se distribuye con la esperanza de que sea útil, pero SIN NINGUNA GARANTÍA; sin siquiera la garantía implícita de COMERCIABILIDAD o APTITUD PARA UN PROPÓSITO PARTICULAR. Para más detalles ver la Licencia Pública General de GNU.

---

**Última actualización:** 13 de enero de 2026