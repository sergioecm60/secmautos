-- Migración: Agregar tabla de cédulas azules de empleados
-- Fecha: 2026-01-09
-- Descripción: Sistema para gestionar cédulas azules (licencias de conducir) de los empleados

CREATE TABLE IF NOT EXISTS cedulas_azules_empleados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    numero_cedula VARCHAR(50) NOT NULL COMMENT 'Número de cédula azul/licencia',
    clase VARCHAR(20) NOT NULL COMMENT 'Clase: A, B, C, profesional, etc',
    fecha_emision DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    foto_cedula VARCHAR(255) COMMENT 'Ruta a la foto de la cédula',
    observaciones TEXT,
    activa TINYINT(1) DEFAULT 1 COMMENT '1 = activa, 0 = renovada/caducada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
    INDEX idx_empleado (empleado_id),
    INDEX idx_vencimiento (fecha_vencimiento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Cédulas azules de empleados';
