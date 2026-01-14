-- ============================================================
-- SECMAUTOS - BASE DE DATOS CONSOLIDADA
-- Fecha: 2026-01-13
-- Descripción: Sistema de gestión de flota vehicular
-- Usuario: secmautos
-- ============================================================

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS secmautos CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;

USE secmautos;

-- ============================================================
-- TABLA: usuarios
-- ============================================================
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

-- Usuario administrador inicial (contraseña: admin123)
INSERT INTO usuarios (nombre, apellido, email, password_hash, rol, activo, primer_login) VALUES
('Admin', 'Sistema', 'admin@secmautos.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', TRUE, FALSE);

-- ============================================================
-- TABLA: intentos_login_ip
-- ============================================================
CREATE TABLE intentos_login_ip (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    intentos INT DEFAULT 1,
    bloqueado_hasta DATETIME NULL,
    ultimo_intento DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_ip (ip_address),
    INDEX idx_bloqueado (bloqueado_hasta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ============================================================
-- TABLA: vehiculos
-- ============================================================
CREATE TABLE vehiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patente VARCHAR(10) UNIQUE NOT NULL,
    marca VARCHAR(50),
    modelo VARCHAR(50),
    anio INT,
    motor VARCHAR(50),
    chasis VARCHAR(50),
    titulo_dnrpa VARCHAR(100) COMMENT 'Código del título digital DNRPA (Registro/Tramite/Control)',
    titularidad VARCHAR(100),
    kilometraje_actual INT DEFAULT 0,
    estado ENUM('disponible', 'asignado', 'mantenimiento', 'baja') DEFAULT 'disponible',
    fecha_vtv DATE,
    fecha_seguro DATE,
    fecha_patente DATE,
    fecha_revision_km DATE,
    km_proximo_service INT,
    imagen VARCHAR(255),
    observaciones TEXT,
    fecha_baja DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patente (patente),
    INDEX idx_estado (estado),
    INDEX idx_marca (marca),
    INDEX idx_titulo_dnrpa (titulo_dnrpa),
    FULLTEXT INDEX idx_busqueda (patente, marca, modelo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ============================================================
-- TABLA: empleados
-- ============================================================
CREATE TABLE empleados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    dni VARCHAR(20) UNIQUE,
    email VARCHAR(100),
    telefono VARCHAR(50),
    direccion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dni (dni),
    INDEX idx_nombre (nombre, apellido),
    FULLTEXT INDEX idx_busqueda (nombre, apellido, dni)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ============================================================
-- TABLA: asignaciones
-- ============================================================
CREATE TABLE asignaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT NOT NULL,
    empleado_id INT NOT NULL,
    fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_devolucion DATETIME NULL,
    km_salida INT,
    km_regreso INT,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
    INDEX idx_vehiculo (vehiculo_id),
    INDEX idx_empleado (empleado_id),
    INDEX idx_fecha (fecha_asignacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ============================================================
-- TABLA: multas
-- ============================================================
CREATE TABLE multas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT NOT NULL,
    empleado_id INT NOT NULL,
    fecha_multa DATE NOT NULL,
    monto DECIMAL(10,2),
    motivo TEXT,
    acta_numero VARCHAR(50),
    pagada BOOLEAN DEFAULT FALSE,
    fecha_pago DATE NULL,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
    INDEX idx_vehiculo (vehiculo_id),
    INDEX idx_empleado (empleado_id),
    INDEX idx_fecha (fecha_multa),
    INDEX idx_pagada (pagada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ============================================================
-- TABLA: pagos
-- ============================================================
CREATE TABLE pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT NOT NULL,
    tipo ENUM('patente', 'seguro', 'multa', 'servicios', 'otro') NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    fecha_pago DATE NULL,
    monto DECIMAL(10,2),
    comprobante VARCHAR(255),
    observaciones TEXT,
    pagado BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    INDEX idx_vehiculo (vehiculo_id),
    INDEX idx_tipo (tipo),
    INDEX idx_vencimiento (fecha_vencimiento),
    INDEX idx_pagado (pagado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ACTUALIZACIÓN DE PAGOS - Agregar soporte para seguros y multas
ALTER TABLE pagos
MODIFY COLUMN tipo ENUM(
    'patente',
    'seguro',
    'multa',
    'servicios',
    'otro'
);

-- Agregar columnas para datos de seguros
ALTER TABLE pagos
ADD COLUMN aseguradora VARCHAR(100) NULL AFTER tipo,
ADD COLUMN poliza_numero VARCHAR(50) NULL AFTER aseguradora,
ADD COLUMN fecha_inicio DATE NULL AFTER poliza_numero,
ADD COLUMN estado_poliza ENUM('vigente', 'vencida', 'cancelada') NULL AFTER fecha_inicio;

-- Agregar columnas para vincular con empleados y multas
ALTER TABLE pagos
ADD COLUMN empleado_id INT NULL AFTER vehiculo_id,
ADD COLUMN multa_id INT NULL AFTER tipo;

-- Agregar referencias
ALTER TABLE pagos
ADD CONSTRAINT fk_pagos_empleado FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_pagos_multa FOREIGN KEY (multa_id) REFERENCES multas(id) ON DELETE SET NULL;

-- Crear índices
ALTER TABLE pagos
ADD INDEX idx_empleado (empleado_id),
ADD INDEX idx_multa (multa_id),
ADD INDEX idx_aseguradora (aseguradora),
ADD INDEX idx_poliza (poliza_numero);

-- ============================================================
-- TABLA: compras
-- ============================================================
CREATE TABLE compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT NOT NULL,
    fecha DATE NOT NULL,
    factura_numero VARCHAR(50),
    proveedor VARCHAR(100),
    cuit VARCHAR(20),
    importe_neto DECIMAL(10,2),
    iva DECIMAL(10,2),
    total DECIMAL(10,2),
    comprobante VARCHAR(255),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    INDEX idx_vehiculo (vehiculo_id),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ============================================================
-- TABLA: ventas
-- ============================================================
CREATE TABLE ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT NOT NULL,
    fecha DATE NOT NULL,
    factura_numero VARCHAR(50),
    comprador VARCHAR(100),
    cuit VARCHAR(20),
    importe DECIMAL(10,2),
    comprobante VARCHAR(255),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    INDEX idx_vehiculo (vehiculo_id),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ============================================================
-- TABLA: ceta
-- ============================================================
CREATE TABLE ceta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT NOT NULL,
    cedula_azul_numero VARCHAR(50),
    fecha_vencimiento DATE NOT NULL,
    fecha_envio DATE,
    enviado BOOLEAN DEFAULT FALSE,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    INDEX idx_vehiculo (vehiculo_id),
    INDEX idx_vencimiento (fecha_vencimiento),
    INDEX idx_enviado (enviado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ============================================================
-- TABLA: transferencias
-- ============================================================
CREATE TABLE transferencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT NOT NULL,
    fecha DATE NOT NULL,
    registro VARCHAR(100),
    direccion_registro TEXT,
    numero_tramite VARCHAR(50),
    estado ENUM('en_proceso', 'completa', 'cancelada') DEFAULT 'en_proceso',
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    INDEX idx_vehiculo (vehiculo_id),
    INDEX idx_fecha (fecha),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ============================================================
-- TABLA: mantenimientos
-- ============================================================
CREATE TABLE mantenimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT NOT NULL,
    fecha DATE NOT NULL,
    tipo ENUM('preventivo', 'correctivo') NOT NULL,
    descripcion TEXT NOT NULL,
    costo DECIMAL(10,2),
    kilometraje INT,
    proveedor VARCHAR(100),
    comprobante VARCHAR(255),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    INDEX idx_vehiculo (vehiculo_id),
    INDEX idx_fecha (fecha),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ============================================================
-- TABLA: alertas
-- ============================================================
CREATE TABLE alertas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT,
    tipo_alerta ENUM('vtv', 'seguro', 'patente', 'ceta', 'km', 'multa') NOT NULL,
    mensaje TEXT NOT NULL,
    fecha_alerta DATE NOT NULL,
    vista BOOLEAN DEFAULT FALSE,
    fecha_resolucion DATE NULL,
    resuelta BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    INDEX idx_vehiculo (vehiculo_id),
    INDEX idx_tipo_alerta (tipo_alerta),
    INDEX idx_vista (vista),
    INDEX idx_resuelta (resuelta),
    INDEX idx_fecha (fecha_alerta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ============================================================
-- TABLA: logs
-- ============================================================
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    accion VARCHAR(100) NOT NULL,
    entidad VARCHAR(50),
    entidad_id INT,
    detalles TEXT,
    ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_accion (accion),
    INDEX idx_entidad (entidad),
    INDEX idx_fecha (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ============================================================
-- FUNCIONES Y TRIGGERS
-- ============================================================

DELIMITER $$

-- Función para calcular fecha de VTV según el cronograma
CREATE FUNCTION calcular_fecha_vtv(p_patente VARCHAR(10), p_anio INT) RETURNS DATE
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_ultimo_digito INT;
    DECLARE v_fecha_vtv DATE;

    -- Obtener el último dígito de la patente
    SET v_ultimo_digito = CAST(SUBSTRING(p_patente, LENGTH(p_patente), 1) AS UNSIGNED);

    -- Calcular fecha de VTV según el cronograma
    CASE v_ultimo_digito
        WHEN 0 THEN SET v_fecha_vtv = CONCAT(p_anio, '-10-31');
        WHEN 1 THEN SET v_fecha_vtv = CONCAT(p_anio, '-11-30');
        WHEN 2 THEN SET v_fecha_vtv = CONCAT(p_anio, '-02-28');
        WHEN 3 THEN SET v_fecha_vtv = CONCAT(p_anio, '-03-31');
        WHEN 4 THEN SET v_fecha_vtv = CONCAT(p_anio, '-04-30');
        WHEN 5 THEN SET v_fecha_vtv = CONCAT(p_anio, '-05-31');
        WHEN 6 THEN SET v_fecha_vtv = CONCAT(p_anio, '-06-30');
        WHEN 7 THEN SET v_fecha_vtv = CONCAT(p_anio, '-07-31');
        WHEN 8 THEN SET v_fecha_vtv = CONCAT(p_anio, '-08-31');
        WHEN 9 THEN SET v_fecha_vtv = CONCAT(p_anio, '-09-30');
        ELSE SET v_fecha_vtv = NULL;
    END CASE;

    RETURN v_fecha_vtv;
END$$

-- Función para calcular estado de documentación
CREATE FUNCTION calcular_estado_documentacion(p_fecha_vtv DATE, p_fecha_seguro DATE, p_fecha_patente DATE) RETURNS VARCHAR(20)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_fecha_vtv_calc DATE;
    DECLARE v_fecha_seguro_calc DATE;
    DECLARE v_fecha_patente_calc DATE;

    -- Usar funciones de VTV si las fechas están disponibles
    IF p_fecha_vtv IS NOT NULL THEN
        SET v_fecha_vtv_calc = calcular_fecha_vtv('AA12345', YEAR(p_fecha_vtv));
    ELSE
        SET v_fecha_vtv_calc = p_fecha_vtv;
    END IF;

    IF p_fecha_seguro IS NOT NULL THEN
        SET v_fecha_seguro_calc = DATE_ADD(p_fecha_seguro, INTERVAL 1 YEAR);
    ELSE
        SET v_fecha_seguro_calc = p_fecha_seguro;
    END IF;

    IF p_fecha_patente IS NOT NULL THEN
        SET v_fecha_patente_calc = DATE_ADD(p_fecha_patente, INTERVAL 1 YEAR);
    ELSE
        SET v_fecha_patente_calc = p_fecha_patente;
    END IF;

    -- Determinar estado
    IF v_fecha_vtv_calc IS NOT NULL AND v_fecha_vtv_calc < CURDATE() THEN
        RETURN 'vencida';
    ELSEIF v_fecha_seguro_calc IS NOT NULL AND v_fecha_seguro_calc < CURDATE() THEN
        RETURN 'vencida';
    ELSEIF v_fecha_patente_calc IS NOT NULL AND v_fecha_patente_calc < CURDATE() THEN
        RETURN 'vencida';
    ELSE
        RETURN 'al_dia';
    END IF;
END$$

-- Trigger de pagos antes de insertar
CREATE TRIGGER tr_pagos_before_insert
BEFORE INSERT ON pagos
FOR EACH ROW
BEGIN
    -- Si es seguro y no tiene fecha_inicio, usar fecha de vencimiento - 1 año
    IF NEW.tipo = 'seguro' AND NEW.fecha_inicio IS NULL AND NEW.fecha_vencimiento IS NOT NULL THEN
        SET NEW.fecha_inicio = DATE_SUB(NEW.fecha_vencimiento, INTERVAL 1 YEAR);
    END IF;

    -- Calcular estado de la póliza basado en la fecha de vencimiento
    IF NEW.tipo = 'seguro' THEN
        IF NEW.fecha_vencimiento < CURDATE() THEN
            SET NEW.estado_poliza = 'vencida';
        ELSE
            SET NEW.estado_poliza = 'vigente';
        END IF;
    END IF;

    -- Marcar como pagado si tiene fecha de pago
    IF NEW.fecha_pago IS NOT NULL THEN
        SET NEW.pagado = 1;
    END IF;
END$$

-- Trigger de pagos antes de actualizar
CREATE TRIGGER tr_pagos_before_update
BEFORE UPDATE ON pagos
FOR EACH ROW
BEGIN
    -- Recalcular estado de la póliza si cambió la fecha de vencimiento
    IF NEW.tipo = 'seguro' AND NEW.fecha_vencimiento <> OLD.fecha_vencimiento THEN
        IF NEW.fecha_vencimiento < CURDATE() THEN
            SET NEW.estado_poliza = 'vencida';
        ELSE
            SET NEW.estado_poliza = 'vigente';
        END IF;
    END IF;

    -- Marcar como pagado si tiene fecha de pago
    IF NEW.fecha_pago IS NOT NULL AND NEW.fecha_pago <> OLD.fecha_pago THEN
        SET NEW.pagado = 1;
    END IF;
END$$

-- Trigger de vehículos antes de insertar
CREATE TRIGGER tr_vehiculos_before_insert
BEFORE INSERT ON vehiculos
FOR EACH ROW
BEGIN
    -- Si no tiene fecha de VTV y tiene patente y año, calcularla automáticamente
    IF NEW.fecha_vtv IS NULL AND NEW.patente IS NOT NULL AND NEW.anio IS NOT NULL THEN
        SET NEW.fecha_vtv = calcular_fecha_vtv(NEW.patente, NEW.anio);
    END IF;
END$$

-- Trigger de vehículos antes de actualizar
CREATE TRIGGER tr_vehiculos_before_update
BEFORE UPDATE ON vehiculos
FOR EACH ROW
BEGIN
    -- Si cambió la fecha de VTV y tiene patente y año, recalcularla
    IF NEW.fecha_vtv <> OLD.fecha_vtv AND NEW.patente IS NOT NULL AND NEW.anio IS NOT NULL THEN
        SET NEW.fecha_vtv = calcular_fecha_vtv(NEW.patente, NEW.anio);
    END IF;
END$$

DELIMITER ;
