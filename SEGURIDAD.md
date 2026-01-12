# 🛡️ Guía de Seguridad del Sistema SECM Autos

## 📋 Resumen de Implementación de Seguridad

### ✅ Medidas Implementadas

#### 1. **Autenticación Robusta** ✅
- **Login por username** (más seguro que email)
- **CAPTCHA matemático** anti-bots
- **Bloqueo de IP** después de 5 intentos fallidos (15 minutos)
- **Bloqueo de usuario** después de 5 intentos fallidos (15 minutos)
- **Sesión con timeout** de 30 minutos
- **Hash de contraseñas** usando `password_hash()` (bcrypt)
- **Usuario por defecto:** `admin` / `admin123` (cambiar en producción)

**Archivos:**
- `api/auth.php` - Lógica de login con bloqueos
- `api/login_handler.php` - Procesa login
- `login.php` - Formulario de login
- `assets/js/login.js` - Validación en frontend

#### 2. **Protección CSRF** ✅
- **Tokens CSRF** en todos los formularios
- **Verificación** en cada petición POST/PUT/DELETE
- **Función** `verificar_csrf()` y `generar_csrf()` en `bootstrap.php`
- **Tokens aleatorios** generados por sesión

**Archivos afectados:**
- Todos los archivos HTML con formularios
- Todos los archivos API (POST/PUT/DELETE)

#### 3. **Prevención de SQL Injection** ✅
- **Todos los queries** usan prepared statements
- **Función** `sanitizar_input()` para limpieza de datos
- **Función** `sanitizeId()` para IDs numéricos
- **Parámetros** vinculados correctamente

**Archivos afectados:**
- Todos los archivos en `api/`

#### 4. **Prevención de XSS** ✅
- **Uso de `textContent`** en lugar de `innerHTML` donde corresponda
- **Sanitización** de inputs
- **Headers de seguridad** configurados
- **Validación** de tipos de datos

#### 5. **Validación de Contraseñas** ✅
- **Mínimo 6 caracteres** en registro
- **Mínimo 6 caracteres** en cambio de contraseña
- **Hash automático** usando bcrypt
- **No validación de fortaleza** compleja (solo longitud mínima)

**Archivos:**
- `api/usuarios.php` - Validación en registro
- `api/cambiar_password.php` - Validación en cambio
- `modules/usuarios.html` - Validación en frontend

#### 6. **Control de Acceso** ✅
- **Roles de usuario:**
  - `superadmin` - Acceso total
  - `admin` - Acceso completo excepto gestión de usuarios
  - `user` - Acceso básico
- **Middleware** `requiereAutenticacion()` en páginas protegidas
- **Middleware** `requiereRol()` para control por rol
- **Logout** correcto con destrucción de sesión

#### 7. **Auditoría y Logging** ✅
- **Tabla `logs`** registra todas las acciones importantes:
  - Login/logout
  - CRUD en usuarios, vehículos, empleados, etc.
  - Asignaciones y devoluciones
  - Cambios de contraseña
- **Función** `registrarLog()` en `bootstrap.php`
- **IP del usuario** registrada en cada log

#### 8. **Protección de Archivos Sensibles** ✅
- **`.htaccess`** (si Apache) o **`nginx.conf`** (si Nginx)
- **Archivos protegidos:**
  - `.env` - Configuración sensible
  - `config/` - Directorio de configuración
  - `logs/` - Logs con errores y datos sensibles
  - `sessions/` - Datos de sesión
- **Permisos:**
  - Directorios: 755
  - Archivos sensibles: 600 (solo lectura para owner)

#### 9. **Headers de Seguridad HTTP** ✅
```apache
# En .htaccess (Apache)
Header set X-Frame-Options "SAMEORIGIN"
Header set X-Content-Type-Options "nosniff"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
```

```nginx
# En nginx.conf (Nginx)
add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";
add_header X-XSS-Protection "1; mode=block";
add_header Referrer-Policy "strict-origin-when-cross-origin";
```

#### 10. **Session Management** ✅
- **Session timeout** de 30 minutos de inactividad
- **Regeneración** de ID de sesión al login (`session_regenerate_id(true)`)
- **Ruta de sesiones** fuera de webroot: `sessions/`
- **Cookies** con flags de seguridad (HttpOnly, SameSite)

#### 11. **Validación de Inputs** ✅
- **Funciones** en `config/security.php`:
  - `sanitizar_input($string)` - Limpia strings
  - `sanitizeId($id)` - Valida y limpia IDs
  - `verificar_email($email)` - Valida formato de email
- **Validación** en backend y frontend

#### 12. **Rate Limiting** ✅
- **Por IP:** 5 intentos de login fallidos → bloqueo 15 min
- **Por usuario:** 5 intentos de login fallidos → bloqueo 15 min
- **Implementado en:** `api/auth.php`

---

## 📁 Archivos de Configuración de Seguridad

### 1. **`config/security.php`** (Si existe)
```php
<?php
/**
 * Funciones de seguridad
 */

/**
 * Sanitiza una entrada de texto
 */
function sanitizar_input($input) {
    if (is_null($input)) {
        return '';
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitiza un ID numérico
 */
function sanitizeId($id) {
    return filter_var($id, FILTER_VALIDATE_INT);
}

/**
 * Valida formato de email
 */
function verificar_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Verifica fortaleza de contraseña (básica)
 * Retorna true si la contraseña es aceptable
 */
function verificar_fortaleza_contrasena($password) {
    return strlen($password) >= 6;
}
?>
```

### 2. **`.htaccess`** (Para Apache)
```apache
# Protección de archivos sensibles
<FilesMatch "^\.env$">
    Require all denied
</FilesMatch>

<FilesMatch "^(config|logs|sessions)/">
    Require all denied
</FilesMatch>

# Headers de seguridad
<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Deshabilitar navegación de directorios
Options -Indexes

# Protección contra ataques
<Limit GET POST>
    LimitRequestBody 10485760
</Limit>
```

### 3. **`nginx.conf.example`** (Para Nginx)
```nginx
server {
    listen 80;
    server_name secmautos.test;
    root /ruta/a/secmautos;
    index index.php index.html;

    # Headers de seguridad
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    # Protección de archivos sensibles
    location ~ /\. {
        deny all;
    }

    location ~ ^/(config|logs|sessions)/ {
        deny all;
    }

    # PHP-FPM
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Rate limiting básico
    limit_req_zone $binary_remote_addr zone=one:10m rate=10r/m;
    limit_req_zone $binary_remote_addr zone=two:10m rate=100r/m;

    location /api/ {
        limit_req zone=one burst=5 nodelay;
    }
}
```

---

## 🚨 Medidas NO Implementadas (según requisitos del usuario)

| Medida | Estado | Nota |
|--------|--------|------|
| **2FA (Two-Factor Authentication)** | ❌ NO | El usuario no quiere autenticación por doble factor |
| **Rate limiting por usuario** | ⚠️ PARCIAL | Bloqueo por usuario implementado (5 intentos, 15 min) pero no configuración avanzada |
| **IP whitelisting para administradores** | ❌ NO | No hay configuración de whitelist |
| **Google reCAPTCHA** | ❌ NO | El usuario prefiere el CAPTCHA matemático actual |
| **Auditoría de permisos** | ❌ NO | No hay auditoría de permisos específica |
| **Rotación de secretos (API keys, JWT, etc.)** | ❌ NO | No hay secretos rotativos (no usa JWT ni API keys) |
| **WAF (Web Application Firewall)** | ❌ NO | El usuario no lo necesita |
| **Escaneos de seguridad automáticos** | ❌ NO | El usuario no quiere escaneos automáticos |
| **Backups encriptados** | ❌ NO | Los backups no están encriptados |

---

## ⚠️ Checklist de Seguridad para Producción

### Configuración de Servidor
- [ ] HTTPS habilitado y forzado
- [ ] Certificado SSL válido (Let's Encrypt o comercial)
- [ ] HTTP/2 habilitado
- [ ] Headers de seguridad configurados
- [ ] Firewall configurado (iptables, ufw, etc.)
- [ ] Fail2ban instalado y configurado
- [ ] Logs rotando correctamente (logrotate)

### Configuración de PHP
- [ ] `display_errors = Off` en producción
- [ ] `log_errors = On`
- [ ] `error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED`
- [ ] `expose_php = Off`
- [ ] `allow_url_fopen = Off`
- [ ] `allow_url_include = Off`
- [ ] `memory_limit` adecuado (256M o más)
- [ ] `max_execution_time` adecuado (30-60)
- [ ] `upload_max_filesize` y `post_max_size` configurados

### Configuración de Base de Datos
- [ ] Usuario de aplicación con permisos mínimos
- [ ] Sin acceso root en producción
- [ ] `bind-address = 127.0.0.1` si MySQL está en el mismo servidor
- [ ] Backups automáticos configurados (mysqldump)
- [ ] Backups almacenados en ubicación segura
- [ ] Test de restauración de backup realizado
- [ ] TLS habilitado para conexiones remotas

### Código de Aplicación
- [x] Todos los queries usan prepared statements
- [x] Todos los inputs están validados y sanitizados
- [x] CSRF tokens implementados en todas las operaciones
- [x] XSS prevention activa (textContent en lugar de innerHTML)
- [x] Autenticación robusta implementada
- [x] Logging de eventos de seguridad activo
- [x] Passwords hasheados correctamente (bcrypt)
- [x] Session timeout configurado (30 minutos)
- [x] Bloqueo por intentos fallidos (IP y usuario)
- [ ] Validación de fortaleza de contraseña mejorada (solo longitud mínima)

### Seguridad de Archivos
- [x] Permisos de archivos correctos
- [x] Archivos sensibles fuera de webroot (.env, config/, logs/, sessions/)
- [x] .gitignore configurado correctamente
- [x] Uploads en directorio separado con permisos restringidos
- [ ] Logs con permisos 600 (solo lectura para owner)
- [ ] Directorio sessions con permisos 700
- [ ] Scripts ejecutables solo con permisos necesarios

### Comunicación Segura
- [ ] HTTPS en todas las conexiones
- [ ] No enviar credenciales por email
- [ ] No mostrar información sensible en errores
- [ ] Error 500 genérico en producción
- [ ] No mostrar stack traces en producción

---

## 🔐 Gestión de Usuarios y Contraseñas

### Usuario por Defecto
```
Usuario: admin
Contraseña: admin123
Rol: superadmin
Email: (opcional, no definido por defecto)
```

**IMPORTANTE:** Cambiar la contraseña inmediatamente después del primer login.

### Política de Contraseñas Implementada
- **Mínimo:** 6 caracteres
- **Hash:** bcrypt usando `password_hash()`
- **No expiración** automática de contraseñas
- **No historial** de contraseñas
- **No validación** de fortaleza (solo longitud)

### Recomendaciones Adicionales
- Cambiar contraseña del usuario admin inmediatamente
- Usar contraseñas fuertes (12+ caracteres, mezcla de mayúsculas, minúsculas, números y símbolos)
- No reutilizar contraseñas de otros servicios
- Cambiar contraseña cada 90 días
- No compartir credenciales por email o chat
- Usar autenticación de dos factores si es posible (NO implementado según usuario)

---

## 🚨 Manejo de Incidentes de Seguridad

### Si se detecta una vulnerabilidad:

1. **Reportar inmediatamente**
   - Email: security@secmautos.com (configurar)
   - WhatsApp: +54 11 6759-8452
   - SLA de respuesta: 24 horas para incidentes críticos

2. **Acciones inmediatas**
   - Cambiar todas las contraseñas de administradores
   - Revisar logs de seguridad recientes
   - Verificar accesos sospechosos
   - Activar modo de mantenimiento si es necesario

3. **Análisis forense**
   - Identificar el alcance de la brecha
   - Determinar datos comprometidos
   - Documentar el incidente
   - Implementar correcciones

4. **Post-incidente**
   - Comunicar a usuarios afectados si correspondiera
   - Realizar penetration test adicional
   - Actualizar políticas de seguridad
   - Aprender del incidente para prevenir futuros

---

## 📞 Contacto de Seguridad

**Email de seguridad:** security@secmautos.com (pendiente de configurar)  
**Equipo de desarrollo:** sergiomiers@gmail.com  
**WhatsApp:** +54 11 6759-8452  
**SLA de respuesta:** 24 horas para incidentes críticos

---

## 🔧 Scripts de Seguridad Útiles

### Script para verificar permisos de archivos
```bash
#!/bin/bash
# Verificar permisos de archivos sensibles

echo "Verificando permisos de archivos sensibles..."

if [ -f ".env" ]; then
    ls -la .env
    echo "✓ .env existe"
else
    echo "✗ .env NO existe"
fi

if [ -d "logs/" ]; then
    ls -la logs/
    echo "✓ logs/ existe"
else
    echo "✗ logs/ NO existe"
fi

if [ -d "sessions/" ]; then
    ls -la sessions/
    echo "✓ sessions/ existe"
else
    echo "✗ sessions/ NO existe"
fi

echo "Permisos sugeridos:"
echo ".env: 600"
echo "logs/: 700"
echo "sessions/: 700"
echo "*.php: 644"
```

### Script para generar reporte de seguridad
```bash
#!/bin/bash
# Generar reporte de seguridad

echo "=== REPORTE DE SEGURIDAD ===" > reporte_seguridad.txt
echo "Fecha: $(date)" >> reporte_seguridad.txt
echo "" >> reporte_seguridad.txt

echo "=== ARCHIVOS SENSIBLES ===" >> reporte_seguridad.txt
ls -la .env >> reporte_seguridad.txt
ls -la config/ >> reporte_seguridad.txt 2>&1
echo "" >> reporte_seguridad.txt

echo "=== PERMISOS ===" >> reporte_seguridad.txt
stat -c '%a %n' .env >> reporte_seguridad.txt
stat -c '%a %n' config/ >> reporte_seguridad.txt
echo "" >> reporte_seguridad.txt

echo "=== USUARIOS EN BASE DE DATOS ===" >> reporte_seguridad.txt
mysql -u root -p"$(cat .env | grep DB_PASS | cut -d= -f2)" secmautos -e "SELECT id, username, rol, activo, ultimo_acceso FROM usuarios;" >> reporte_seguridad.txt
echo "" >> reporte_seguridad.txt

echo "=== INTENTOS DE LOGIN FALLIDOS (ÚLTIMOS 24H) ===" >> reporte_seguridad.txt
mysql -u root -p"$(cat .env | grep DB_PASS | cut -d= -f2)" secmautos -e "SELECT COUNT(*) as intentos_fallidos FROM intentos_login_ip WHERE ultimo_intento > DATE_SUB(NOW(), INTERVAL 1 DAY);" >> reporte_seguridad.txt
```

---

## 📚 Referencias de Seguridad

### OWASP Top 10
1. Broken Access Control - ✅ Mitigado
2. Cryptographic Failures - ✅ Mitigado
3. Injection - ✅ Mitigado
4. Insecure Design - ⚠️ Parcialmente mitigado
5. Security Misconfiguration - ✅ Mitigado
6. Vulnerable and Outdated Components - ⚠️ Requiere monitoreo
7. Identification and Authentication Failures - ✅ Mitigado
8. Software and Data Integrity Failures - ⚠️ No implementado
9. Security Logging and Monitoring Failures - ✅ Implementado
10. Server-Side Request Forgery (SSRF) - N/A

### Recursos útiles
- [OWASP Cheat Sheet Series](https://cheatsheetseries.owasp.org/)
- [PHP Security Best Practices](https://www.php.net/manual/es/security.php)
- [MySQL Security](https://dev.mysql.com/doc/refman/8.0/en/general-security-issues.html)
- [Apache Security](https://httpd.apache.org/docs/current/misc/security_tips.html)
- [Nginx Security](https://nginx.org/en/docs/http/ngx_http_core_module.html#example)

---

## 🎯 Conclusiones

El sistema **SECM Autos** cuenta con medidas de seguridad sólidas para un sistema de gestión de flota:

**Fortalezas:**
- ✅ Autenticación robusta con bloqueos
- ✅ Protección CSRF completa
- ✅ Prevención de SQL Injection
- ✅ Control de accesos por roles
- ✅ Auditoría de logs completa
- ✅ Hash de contraseñas seguro
- ✅ Headers de seguridad HTTP

**Limitaciones (según usuario):**
- ❌ Sin 2FA
- ❌ Sin WAF
- ❌ Sin escaneos automáticos
- ❌ Backups no encriptados

**Recomendaciones finales:**
1. Mantener PHP y MySQL actualizados
2. Realizar backups diarios
3. Monitorear logs regularmente
4. Capacitar usuarios en seguridad básica
5. Usar HTTPS en producción
6. Implementar fail2ban para protección adicional

---

**Última actualización:** 2026-01-12  
**Autor:** Sergio Cabrera  
**Versión de documento:** 2.0
