CREATE TABLE consumo_combustible (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patente VARCHAR(10) NOT NULL,
    marca VARCHAR(50),
    modelo VARCHAR(50),
    version VARCHAR(50),
    conductor VARCHAR(100) NOT NULL,
    fecha_carga DATE NOT NULL,
    hora_carga TIME NOT NULL,
    lugar_carga VARCHAR(100),
    tipo_comb ENUM('Nafta', 'Diesel', 'Otro') DEFAULT 'Nafta',
    litros DECIMAL(8,2) NOT NULL,
    precio_litro DECIMAL(8,2) NOT NULL,
    odometro INT NOT NULL,
    km_anterior INT NULL,
    km_recorridos INT NULL,
    rendimiento DECIMAL(6,2) NULL,
    costo_total DECIMAL(12,2) NULL,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patente (patente),
    INDEX idx_fecha (fecha_carga),
    INDEX idx_conductor (conductor),
    FOREIGN KEY (patente) REFERENCES vehiculos(patente) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DELIMITER $$

CREATE TRIGGER tr_consumo_before_insert
BEFORE INSERT ON consumo_combustible
FOR EACH ROW
BEGIN
    SET NEW.costo_total = NEW.litros * NEW.precio_litro;
    
    IF NEW.km_anterior IS NOT NULL THEN
        SET NEW.km_recorridos = NEW.odometro - NEW.km_anterior;
        IF NEW.litros > 0 AND NEW.km_recorridos > 0 THEN
            SET NEW.rendimiento = NEW.km_recorridos / NEW.litros;
        END IF;
    END IF;
END$$

CREATE TRIGGER tr_consumo_before_update
BEFORE UPDATE ON consumo_combustible
FOR EACH ROW
BEGIN
    SET NEW.costo_total = NEW.litros * NEW.precio_litro;
    
    IF NEW.km_anterior IS NOT NULL THEN
        SET NEW.km_recorridos = NEW.odometro - NEW.km_anterior;
        IF NEW.litros > 0 AND NEW.km_recorridos > 0 THEN
            SET NEW.rendimiento = NEW.km_recorridos / NEW.litros;
        END IF;
    ELSE
        SET NEW.km_recorridos = NULL;
        SET NEW.rendimiento = NULL;
    END IF;
END$$

DELIMITER ;
