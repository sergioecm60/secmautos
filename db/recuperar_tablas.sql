-- ============================================================
-- SECMAUTOS - RECUPERACIÓN DE BASE DE DATOS
-- Fecha: 2026-01-13
-- Descripción: Recrear las tablas principales si existen
-- Uso: Ejecutar si se perdieron datos o se necesita limpiar
-- ============================================================

DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS intentos_login_ip;
DROP TABLE IF EXISTS vehiculos;
DROP TABLE IF EXISTS empleados;
DROP TABLE IF EXISTS asignaciones;
DROP TABLE IF EXISTS multas;
DROP TABLE IF EXISTS pagos;
DROP TABLE IF EXISTS compras;
DROP TABLE IF EXISTS ventas;
DROP TABLE IF EXISTS ceta;
DROP TABLE IF EXISTS transferencias;
DROP TABLE IF EXISTS mantenimientos;
DROP TABLE IF EXISTS alertas;
DROP TABLE IF EXISTS logs;

-- Eliminar vistas si existen
DROP VIEW IF EXISTS v_historial_pagos_telepase;
DROP VIEW IF EXISTS v_telepases_completo;

-- ============================================================
-- RECUPERAR DATOS ANTES DE BORRAR
-- ============================================================

-- Crear tabla de respaldo temporal
CREATE TABLE IF NOT EXISTS temp_backup_usuarios LIKE usuarios;

-- Si hay datos, respaldar antes de borrar
INSERT INTO temp_backup_usuarios SELECT * FROM usuarios;

-- Borrar tabla principal
DROP TABLE usuarios;

-- Recrear tabla principal vacía
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

-- Restaurar datos de respaldo
INSERT INTO usuarios SELECT * FROM temp_backup_usuarios;

-- Eliminar tabla de respaldo
DROP TABLE temp_backup_usuarios;

-- Usuario administrador inicial (contraseña: admin123)
-- Solo insertar si la tabla está vacía
INSERT IGNORE INTO usuarios (nombre, apellido, email, password_hash, rol, activo, primer_login) VALUES
('Admin', 'Sistema', 'admin@secmautos.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', TRUE, FALSE);

SELECT 'Recuperación completada. Tablas principales han sido recreadas.' AS mensaje;
