DELIMITER $$

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

DELIMITER ;
