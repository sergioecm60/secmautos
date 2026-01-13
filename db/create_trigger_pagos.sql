DELIMITER $$

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

DELIMITER ;
