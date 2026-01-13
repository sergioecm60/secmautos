DELIMITER $$

DROP FUNCTION IF EXISTS calcular_fecha_vtv$$

CREATE FUNCTION calcular_fecha_vtv(p_patente VARCHAR(10), p_anio INT) RETURNS DATE
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_tercer_numero INT;
    DECLARE v_fecha_vtv DATE;
    DECLARE v_i INT DEFAULT 1;
    DECLARE v_char CHAR(1);
    DECLARE v_numero_count INT DEFAULT 0;

    -- Extraer el tercer número de la patente
    WHILE v_i <= LENGTH(p_patente) AND v_numero_count < 3 DO
        SET v_char = SUBSTRING(p_patente, v_i, 1);
        
        IF v_char BETWEEN '0' AND '9' THEN
            SET v_numero_count = v_numero_count + 1;
            IF v_numero_count = 3 THEN
                SET v_tercer_numero = CAST(v_char AS UNSIGNED);
            END IF;
        END IF;
        
        SET v_i = v_i + 1;
    END WHILE;

    -- Calcular fecha de VTV según el cronograma del tercer número
    CASE v_tercer_numero
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
