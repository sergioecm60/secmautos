DELIMITER $$

CREATE FUNCTION calcular_estado_documentacion(
    p_fecha_vtv DATE,
    p_fecha_seguro DATE,
    p_fecha_patente DATE
) RETURNS ENUM('al_dia', 'deuda_una', 'deuda_varias')
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_deuda INT DEFAULT 0;
    DECLARE v_hoy DATE DEFAULT CURDATE();

    -- Contar documentos vencidos
    IF p_fecha_vtv IS NOT NULL AND p_fecha_vtv < v_hoy THEN
        SET v_deuda = v_deuda + 1;
    END IF;

    IF p_fecha_seguro IS NOT NULL AND p_fecha_seguro < v_hoy THEN
        SET v_deuda = v_deuda + 1;
    END IF;

    IF p_fecha_patente IS NOT NULL AND p_fecha_patente < v_hoy THEN
        SET v_deuda = v_deuda + 1;
    END IF;

    -- Determinar estado
    IF v_deuda = 0 THEN
        RETURN 'al_dia';
    ELSEIF v_deuda = 1 THEN
        RETURN 'deuda_una';
    ELSE
        RETURN 'deuda_varias';
    END IF;
END$$

DELIMITER ;
