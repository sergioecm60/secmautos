DELIMITER $$

DROP TRIGGER IF EXISTS tr_vehiculos_before_insert$$

CREATE TRIGGER tr_vehiculos_before_insert
BEFORE INSERT ON vehiculos
FOR EACH ROW
BEGIN
    -- Si no tiene fecha de VTV y tiene patente y año, calcularla automáticamente
    IF NEW.fecha_vtv IS NULL AND NEW.patente IS NOT NULL AND NEW.anio IS NOT NULL THEN
        SET NEW.fecha_vtv = calcular_fecha_vtv(NEW.patente, NEW.anio);
    END IF;

    -- Calcular estado de documentación
    IF NEW.estado_documentacion IS NULL OR NEW.fecha_vtv IS NOT NULL OR NEW.fecha_seguro IS NOT NULL OR NEW.fecha_patente IS NOT NULL THEN
        SET NEW.estado_documentacion = calcular_estado_documentacion(NEW.fecha_vtv, NEW.fecha_seguro, NEW.fecha_patente);
    END IF;
END$$

DROP TRIGGER IF EXISTS tr_vehiculos_before_update$$

CREATE TRIGGER tr_vehiculos_before_update
BEFORE UPDATE ON vehiculos
FOR EACH ROW
BEGIN
    -- Recalcular estado de documentación si cambió alguna fecha
    IF NOT (NEW.fecha_vtv <=> OLD.fecha_vtv AND NEW.fecha_seguro <=> OLD.fecha_seguro AND NEW.fecha_patente <=> OLD.fecha_patente) THEN
        SET NEW.estado_documentacion = calcular_estado_documentacion(NEW.fecha_vtv, NEW.fecha_seguro, NEW.fecha_patente);
    END IF;
END$$

DELIMITER ;
