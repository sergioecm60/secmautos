# 🚀 Guía de Actualización a Producción - Sistema de Importación de Vehículos

**Fecha:** 2026-01-14
**Versión:** Desde versión anterior de SECMAUTOS

---

## 📋 Resumen de Cambios

### Nuevos Archivos
- `api/importar_vehiculos.php` - API de importación masiva
- `assets/js/importar_vehiculos.js` - Lógica del importador
- `modules/importar_vehiculos.html` - Interfaz de importación
- `db/migracion_importacion_vehiculos.sql` - Migración de BD

### Archivos Modificados
- `api/vehiculos.php` - Soporte para tipo_vehiculo
- `assets/js/dashboard.js` - Función cargarModuloImportador()
- `assets/js/vehiculos.js` - Mostrar tipo_vehículo
- `modules/vehiculos.html` - Botón de importación

---

## ⚙️ Pasos para Actualización en Producción

### 1. 🔒 HACER BACKUP ANTES DE TODO

```bash
# Backup de la base de datos
mysqldump -h HOST -u USUARIO -p secmautos > backup_pre_importacion_$(date +%Y%m%d).sql

# Backup de los archivos
tar -czf backup_files_$(date +%Y%m%d).tar.gz /ruta/a/secmautos
```

### 2. 📥 Actualizar el código desde Git

```bash
cd /ruta/a/secmautos
git pull origin main
```

Verificar que se descarguen los nuevos archivos:
```bash
ls -la api/importar_vehiculos.php
ls -la assets/js/importar_vehiculos.js
ls -la modules/importar_vehiculos.html
```

### 3. 🗄️ Ejecutar Migración de Base de Datos

```bash
mysql -h HOST -u USUARIO -p secmautos < db/migracion_importacion_vehiculos.sql
```

O manualmente:

```sql
USE secmautos;

ALTER TABLE vehiculos
ADD COLUMN tipo_vehiculo VARCHAR(50) DEFAULT 'Auto' AFTER chasis,
ADD COLUMN color VARCHAR(50) NULL AFTER modelo,
ADD COLUMN titulo_automotor VARCHAR(100) NULL AFTER titulo_dnrpa,
ADD COLUMN cedula_verde VARCHAR(100) NULL AFTER titulo_automotor,
ADD COLUMN carga_maxima_kg INT NULL AFTER color,
ADD COLUMN km_odometro_inicial INT DEFAULT 0 AFTER anio,
ADD COLUMN ciclo_mantenimiento_preventivo_km INT NULL AFTER km_proximo_service;

ALTER TABLE vehiculos
ADD INDEX idx_tipo_vehiculo (tipo_vehiculo);
```

### 4. ✅ Verificar la Migración

```sql
-- Verificar columnas nuevas
SHOW COLUMNS FROM vehiculos LIKE 'tipo_vehiculo';
SHOW COLUMNS FROM vehiculos LIKE 'color';
SHOW COLUMNS FROM vehiculos LIKE 'km_odometro_inicial';

-- Verificar índice
SHOW INDEX FROM vehiculos WHERE Key_name = 'idx_tipo_vehiculo';
```

### 5. 🧪 Probar el Sistema

1. Acceder al sistema: `https://tu-dominio.com`
2. Ir al módulo **🚗 Vehículos**
3. Verificar que aparezca el botón **📥 Importar desde Excel**
4. Probar abrir el módulo de importación

### 6. 📄 Preparar Archivo de Importación (Opcional)

Si deseas importar vehículos:

```bash
# Copiar el archivo JSON generado localmente
scp vehiculos_importar.json usuario@servidor:/ruta/a/secmautos/docs\ y\ dbs/

# O subirlo por FTP/SFTP a la carpeta docs y dbs/
```

**Importante:** El archivo debe estar en la ruta:
```
/ruta/a/secmautos/docs y dbs/vehiculos_importar.json
```

---

## 🔍 Solución de Problemas

### Problema: No aparece el botón "Importar desde Excel"

**Solución:**
1. Limpiar caché del navegador (Ctrl+F5)
2. Verificar que los archivos se hayan descargado correctamente
3. Revisar consola del navegador (F12) por errores de JavaScript

### Problema: Error 403 al importar

**Causa:** El token CSRF no se está generando correctamente.

**Solución:**
Verificar que `index.php` tenga el meta tag:
```html
<meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">
```

### Problema: Error "Column not found: tipo_vehiculo"

**Causa:** La migración de base de datos no se ejecutó correctamente.

**Solución:**
```bash
mysql -h HOST -u USUARIO -p secmautos < db/migracion_importacion_vehiculos.sql
```

### Problema: Archivo de importación no encontrado

**Causa:** El JSON no está en la ubicación correcta.

**Solución:**
Asegurarse que el archivo esté en:
```
/ruta/a/secmautos/docs y dbs/vehiculos_importar.json
```

Y que el usuario de Apache/Nginx tenga permisos de lectura.

### Problema: Error de permisos

**Solución:**
```bash
# Asegurar permisos correctos
chown -R www-data:www-data /ruta/a/secmautos
chmod -R 755 /ruta/a/secmautos
chmod -R 644 /ruta/a/secmautos/*.php
```

---

## 📊 Qué se puede hacer después de la actualización

1. **Importar vehículos masivamente** desde Excel
2. **Actualizar tipo de vehículo** en registros existentes
3. **Registrar kilómetros iniciales** de odómetro
4. **Configurar ciclos de mantenimiento preventivo**

---

## 🔄 Rollback en caso de problemas

Si algo sale mal:

```bash
# Restaurar base de datos
mysql -h HOST -u USUARIO -p secmautos < backup_pre_importacion_YYYYMMDD.sql

# Restaurar archivos
tar -xzf backup_files_YYYYMMDD.tar.gz -C /ruta/a/

# O volver al commit anterior
git reset --hard HEAD~2
```

---

## 📞 Soporte

Para problemas o dudas:
- 📧 Email: sergiomiers@gmail.com
- 💬 WhatsApp: +54 11 6759-8452
- 🐛 GitHub Issues: https://github.com/sergioecm60/secmautos/issues

---

**¡Éxito con la actualización!** 🎉
