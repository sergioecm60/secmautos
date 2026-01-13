-- Base de datos para control de consumo de combustible
CREATE DATABASE IF NOT EXISTS flota CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE flota;

-- Tabla de consumo de combustible
CREATE TABLE IF NOT EXISTS consumo_combustible (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patente VARCHAR(10) NOT NULL,
    marca VARCHAR(30),
    modelo VARCHAR(30),
    version VARCHAR(30),
    conductor VARCHAR(60),
    fecha_carga DATE NOT NULL,
    hora_carga TIME NOT NULL,
    lugar_carga VARCHAR(60),
    tipo_comb ENUM('Nafta', 'Diesel', 'Otro') DEFAULT 'Nafta',
    litros DECIMAL(8,2) NOT NULL,
    precio_litro DECIMAL(8,2) NOT NULL,
    costo_total DECIMAL(10,2) GENERATED ALWAYS AS (litros * precio_litro) STORED,
    odometro INT NOT NULL,
    km_anterior INT,
    km_recorridos INT GENERATED ALWAYS AS (odometro - km_anterior) STORED,
    rendimiento DECIMAL(6,2) GENERATED ALWAYS AS (CASE WHEN km_anterior IS NULL THEN NULL WHEN km_recorridos = 0 THEN NULL ELSE ROUND((km_recorridos / litros), 2) END) STORED,
    observaciones TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patente (patente),
    INDEX idx_fecha (fecha_carga),
    INDEX idx_odometro (odometro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
