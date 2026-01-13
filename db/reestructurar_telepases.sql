-- Reestructurar módulo Telepases
-- Fecha: 2026-01-13
-- Separar dispositivos físicos de los cobros/usos

-- Paso 1: Respaldar datos actuales (si existen)
CREATE TABLE IF NOT EXISTS telepases_backup AS SELECT * FROM telepases;

-- Paso 2: Eliminar tabla actual
DROP TABLE IF EXISTS telepases;

-- Paso 3: Crear nueva tabla de dispositivos telepase
CREATE TABLE telepases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT NOT NULL,
    numero_dispositivo VARCHAR(50) NOT NULL UNIQUE,
    fecha_activacion DATE NOT NULL,
    fecha_baja DATE NULL,
    estado ENUM('habilitado', 'deshabilitado', 'baja') DEFAULT 'habilitado',
    observaciones TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE RESTRICT,
    INDEX idx_vehiculo (vehiculo_id),
    INDEX idx_dispositivo (numero_dispositivo),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Paso 4: Crear tabla de pagos/cobros de telepase
CREATE TABLE IF NOT EXISTS pagos_telepase (
    id INT AUTO_INCREMENT PRIMARY KEY,
    telepase_id INT NOT NULL,
    vehiculo_id INT NOT NULL,
    periodo DATE NOT NULL COMMENT 'Período de facturación (YYYY-MM-DD)',
    concesionario VARCHAR(100) NOT NULL COMMENT 'Ej: ACCESO OESTE',
    numero_comprobante VARCHAR(50) NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    fecha_vencimiento_recargo DATE NULL COMMENT 'Fecha de vencimiento con recargo',
    monto DECIMAL(10,2) NOT NULL,
    monto_recargo DECIMAL(10,2) NULL COMMENT 'Monto con recargo si aplica',
    estado ENUM('pendiente', 'pagado', 'vencido') DEFAULT 'pendiente',
    fecha_pago DATE NULL,
    observaciones TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (telepase_id) REFERENCES telepases(id) ON DELETE RESTRICT,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE RESTRICT,
    INDEX idx_telepase (telepase_id),
    INDEX idx_vehiculo (vehiculo_id),
    INDEX idx_periodo (periodo),
    INDEX idx_estado (estado),
    INDEX idx_vencimiento (fecha_vencimiento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Paso 5: Crear vista para facilitar consultas
CREATE OR REPLACE VIEW v_telepases_completo AS
SELECT
    t.id,
    t.numero_dispositivo,
    t.fecha_activacion,
    t.fecha_baja,
    t.estado,
    t.observaciones,
    v.id as vehiculo_id,
    v.patente,
    v.marca,
    v.modelo,
    v.anio,
    COUNT(pt.id) as total_pagos,
    SUM(CASE WHEN pt.estado = 'pendiente' THEN 1 ELSE 0 END) as pagos_pendientes,
    SUM(CASE WHEN pt.estado = 'pendiente' THEN pt.monto ELSE 0 END) as monto_pendiente
FROM telepases t
INNER JOIN vehiculos v ON t.vehiculo_id = v.id
LEFT JOIN pagos_telepase pt ON t.id = pt.telepase_id
GROUP BY t.id, v.id;

-- Paso 6: Crear vista para historial de pagos
CREATE OR REPLACE VIEW v_historial_pagos_telepase AS
SELECT
    pt.id,
    pt.periodo,
    pt.concesionario,
    pt.numero_comprobante,
    pt.fecha_vencimiento,
    pt.fecha_vencimiento_recargo,
    pt.monto,
    pt.monto_recargo,
    pt.estado,
    pt.fecha_pago,
    t.numero_dispositivo,
    v.patente,
    v.marca,
    v.modelo
FROM pagos_telepase pt
INNER JOIN telepases t ON pt.telepase_id = t.id
INNER JOIN vehiculos v ON pt.vehiculo_id = v.id
ORDER BY pt.periodo DESC, pt.fecha_vencimiento DESC;
