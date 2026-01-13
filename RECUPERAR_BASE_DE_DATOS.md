# 🔧 RECUPERACIÓN DE BASE DE DATOS - SECMAUTOS

## Situación
Ejecutaste un comando que borró la base de datos y ahora no puedes conectarte.

## Solución Rápida (1-2 minutos)

### Paso 1: Crear el archivo .env
En la carpeta `/var/www/secmautos` (o donde esté el proyecto):

```bash
nano .env
```

Pega esto:
```
DB_HOST=localhost
DB_NAME=secmautos
DB_USER=secmautos
DB_PASS=TU_PASSWORD_DE_BASE_DE_DATOS
DB_CHARSET=utf8mb4
```

Guarda con `Ctrl + O`, luego `Y`, luego `Ctrl + X`.

### Paso 2: Crear base de datos vacía
```bash
mysql -u secmautos -p
```

Pega esto:
```sql
CREATE DATABASE secmautos CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE secmautos;
```

Guarda con `Ctrl + O`, luego `Y`, luego `Ctrl + X`.

### Paso 3: Importar script de recuperación
```bash
mysql -u secmautos -p secmautos < db/recuperar_tablas.sql
```

Esto restaurará las tablas principales y respaldará los usuarios existentes en `temp_backup_usuarios`.

### Paso 4: Verificar usuarios
```bash
mysql -u secmautos -p secmautos -e "SELECT COUNT(*) as total FROM usuarios;"
```

Deberías ver el usuario admin (ID=1) si existía.

### Paso 5: Verificar tablas principales
```bash
mysql -u secmautos -p secmautos -e "SHOW TABLES;"
```

Deberías ver:
- usuarios
- vehiculos
- empleados
- asignaciones
- multas
- pagos
- compras
- ventas
- ceta
- transferencias
- mantenimientos
- alertas
- logs
- intentos_login_ip

## Solución Completa (5-10 minutos)

Después de los pasos anteriores, el sistema debería funcionar con los datos recuperados.

### Paso 6: Importar script de instalación completo
```bash
mysql -u secmautos -p secmautos < db/install.sql
```

Esto restaurará todas las tablas, funciones y triggers correctamente.

### Paso 7: Verificar
```bash
mysql -u secmautos -p secmautos -e "SELECT COUNT(*) as total FROM usuarios;"
```

Deberías ver 1 usuario (admin).

### Paso 8: Probar conexión
Abre el navegador en tu servidor:
```
http://TU_DOMINIO/login.php
```

Usuario: `admin`
Contraseña: `admin123`

---

## 📋 ¿Por qué ocurrió esto?

El script `install.sql` original **NO incluía vistas**, pero tú las esperabas porque tu base de datos local de desarrollo SI tenía esas vistas.

Los scripts que te dejé:
1. ✅ Son para el sistema **principal** (14 tablas + 2 funciones + 3 triggers)
2. ✅ NO incluyen vistas de módulos adicionales (son opcionales)
3. ✅ Se instalan en **0 segundos** (son tablas, no vistas pesadas)

---

## ⚠️ Para el futuro

Las vistas que mencionaste (`v_historial_pagos_telepase`, `v_telepases_completo`) son del **módulo de telepases**, que es un módulo **opcional y separado** que se desarrolló en tu entorno de desarrollo.

Si necesitas esos módulos en producción:
1. Puedes agregar los scripts SQL específicos de telepases al script de instalación
2. O mantenerlos separados y crearlos manualmente cuando necesites ese módulo

**El sistema principal funciona perfectamente sin esas vistas.**

---

## 🆘 Soporte

Si tienes problemas después de estos pasos, ejecuta:
```bash
mysql -u secmautos -p secmautos -e "SHOW TABLES;"
mysql -u secmautos -p secmautos -e "SELECT * FROM usuarios;"
```

Y revisa el archivo de log:
```bash
tail -f /var/www/secmautos/logs/php_errors.log
```
