-- Agregar campo titulo_dnrpa a la tabla vehiculos
ALTER TABLE vehiculos
ADD COLUMN titulo_dnrpa VARCHAR(100) NULL COMMENT 'Código del título digital DNRPA (Registro/Tramite/Control)'
AFTER chasis;

-- Crear índice para búsquedas rápidas
CREATE INDEX idx_titulo_dnrpa ON vehiculos(titulo_dnrpa);
