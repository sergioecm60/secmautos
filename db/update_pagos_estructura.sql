-- Actualizar estructura de la tabla pagos
-- Fecha: 2026-01-13
-- Descripción: Agregar columnas faltantes para soportar seguros, multas y empleados

-- Modificar el tipo ENUM para incluir más opciones
ALTER TABLE pagos
MODIFY COLUMN tipo ENUM('patente', 'seguro', 'multa', 'servicios', 'otro') NOT NULL;

-- Agregar columnas para datos de seguros
ALTER TABLE pagos
ADD COLUMN aseguradora VARCHAR(100) NULL AFTER tipo,
ADD COLUMN poliza_numero VARCHAR(50) NULL AFTER aseguradora,
ADD COLUMN fecha_inicio DATE NULL AFTER poliza_numero,
ADD COLUMN estado_poliza ENUM('vigente', 'vencida', 'cancelada') NULL AFTER fecha_inicio;

-- Agregar referencias a empleado y multa
ALTER TABLE pagos
ADD COLUMN empleado_id INT NULL AFTER vehiculo_id,
ADD COLUMN multa_id INT NULL AFTER empleado_id;

-- Agregar índices para las nuevas columnas
ALTER TABLE pagos
ADD INDEX idx_empleado (empleado_id),
ADD INDEX idx_multa (multa_id),
ADD INDEX idx_aseguradora (aseguradora),
ADD INDEX idx_poliza (poliza_numero);

-- Agregar claves foráneas
ALTER TABLE pagos
ADD CONSTRAINT fk_pagos_empleado FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE SET NULL;

-- Nota: No agregamos FK para multa_id porque la tabla multas puede tener una estructura diferente
-- Si existe la tabla multas con columna id, descomentar la siguiente línea:
-- ALTER TABLE pagos
-- ADD CONSTRAINT fk_pagos_multa FOREIGN KEY (multa_id) REFERENCES multas(id) ON DELETE SET NULL;
