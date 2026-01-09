-- Migración: Agregar tabla de autorizaciones de manejo
-- Fecha: 2026-01-09
-- Descripción: Autoriza qué empleados pueden manejar qué vehículos que tienen CETA

-- Primero eliminar la tabla incorrecta creada anteriormente
DROP TABLE IF EXISTS cedulas_azules_empleados;

CREATE TABLE IF NOT EXISTS autorizaciones_manejo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    vehiculo_id INT NOT NULL,
    fecha_otorgamiento DATE NOT NULL,
    observaciones TEXT,
    activa TINYINT(1) DEFAULT 1 COMMENT '1 = activa, 0 = revocada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_empleado_vehiculo (empleado_id, vehiculo_id),
    INDEX idx_empleado (empleado_id),
    INDEX idx_vehiculo (vehiculo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Autorizaciones de manejo de vehículos con CETA';
