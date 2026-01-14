-- ============================================================
-- MIGRACIÓN DE BASE DE DATOS - SISTEMA DE IMPORTACIÓN
-- Fecha: 2026-01-14
-- Descripción: Agregar soporte para importación masiva de vehículos
-- Versión: Desde versión anterior de SECMAUTOS
-- ============================================================

USE secmautos;

-- ============================================================
-- AGREGAR COLUMNAS PARA IMPORTACIÓN DE VEHÍCULOS
-- ============================================================

-- Verificar si ya existen las columnas para evitar errores
SET @column_exists = (SELECT COUNT(*)
                     FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = 'secmautos'
                     AND TABLE_NAME = 'vehiculos'
                     AND COLUMN_NAME = 'tipo_vehiculo');

-- Agregar columna tipo_vehiculo si no existe
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE vehiculos ADD COLUMN tipo_vehiculo VARCHAR(50) DEFAULT ''Auto'' AFTER chasis',
    'SELECT ''Columna tipo_vehiculo ya existe'' AS mensaje');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar y agregar otras columnas necesarias
SET @column_color = (SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = 'secmautos'
                    AND TABLE_NAME = 'vehiculos'
                    AND COLUMN_NAME = 'color');

SET @sql = IF(@column_color = 0,
    'ALTER TABLE vehiculos ADD COLUMN color VARCHAR(50) NULL AFTER modelo',
    'SELECT ''Columna color ya existe'' AS mensaje');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_titulo_auto = (SELECT COUNT(*)
                         FROM INFORMATION_SCHEMA.COLUMNS
                         WHERE TABLE_SCHEMA = 'secmautos'
                         AND TABLE_NAME = 'vehiculos'
                         AND COLUMN_NAME = 'titulo_automotor');

SET @sql = IF(@column_titulo_auto = 0,
    'ALTER TABLE vehiculos ADD COLUMN titulo_automotor VARCHAR(100) NULL AFTER titulo_dnrpa',
    'SELECT ''Columna titulo_automotor ya existe'' AS mensaje');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_cedula_verde = (SELECT COUNT(*)
                          FROM INFORMATION_SCHEMA.COLUMNS
                          WHERE TABLE_SCHEMA = 'secmautos'
                          AND TABLE_NAME = 'vehiculos'
                          AND COLUMN_NAME = 'cedula_verde');

SET @sql = IF(@column_cedula_verde = 0,
    'ALTER TABLE vehiculos ADD COLUMN cedula_verde VARCHAR(100) NULL AFTER titulo_automotor',
    'SELECT ''Columna cedula_verde ya existe'' AS mensaje');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_carga = (SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = 'secmautos'
                    AND TABLE_NAME = 'vehiculos'
                    AND COLUMN_NAME = 'carga_maxima_kg');

SET @sql = IF(@column_carga = 0,
    'ALTER TABLE vehiculos ADD COLUMN carga_maxima_kg INT NULL AFTER color',
    'SELECT ''Columna carga_maxima_kg ya existe'' AS mensaje');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_km_odometro = (SELECT COUNT(*)
                          FROM INFORMATION_SCHEMA.COLUMNS
                          WHERE TABLE_SCHEMA = 'secmautos'
                          AND TABLE_NAME = 'vehiculos'
                          AND COLUMN_NAME = 'km_odometro_inicial');

SET @sql = IF(@column_km_odometro = 0,
    'ALTER TABLE vehiculos ADD COLUMN km_odometro_inicial INT DEFAULT 0 AFTER anio',
    'SELECT ''Columna km_odometro_inicial ya existe'' AS mensaje');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_ciclo = (SELECT COUNT(*)
                     FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = 'secmautos'
                     AND TABLE_NAME = 'vehiculos'
                     AND COLUMN_NAME = 'ciclo_mantenimiento_preventivo_km');

SET @sql = IF(@column_ciclo = 0,
    'ALTER TABLE vehiculos ADD COLUMN ciclo_mantenimiento_preventivo_km INT NULL AFTER km_proximo_service',
    'SELECT ''Columna ciclo_mantenimiento_preventivo_km ya existe'' AS mensaje');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- AGREGAR ÍNDICES PARA MEJORAR RENDIMIENTO
-- ============================================================

-- Verificar y crear índice para tipo_vehiculo
SET @index_exists = (SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.STATISTICS
                    WHERE TABLE_SCHEMA = 'secmautos'
                    AND TABLE_NAME = 'vehiculos'
                    AND INDEX_NAME = 'idx_tipo_vehiculo');

SET @sql = IF(@index_exists = 0,
    'ALTER TABLE vehiculos ADD INDEX idx_tipo_vehiculo (tipo_vehiculo)',
    'SELECT ''Indice idx_tipo_vehiculo ya existe'' AS mensaje');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- RESULTADO DE LA MIGRACIÓN
-- ============================================================

SELECT '✅ Migración completada exitosamente' AS estado;
SELECT 'Verificar que se hayan creado las nuevas columnas en la tabla vehiculos' AS nota;
SELECT 'Proceder con git pull en producción' AS siguiente_paso;
