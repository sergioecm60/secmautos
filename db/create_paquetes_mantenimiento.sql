CREATE TABLE paquetes_mantenimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO paquetes_mantenimiento (codigo, nombre, descripcion) VALUES
('Preventivo 01', 'Preventivo 01', 'Paquete de mantenimiento preventivo básico (0-5000 km)'),
('Preventivo 02', 'Preventivo 02', 'Paquete de mantenimiento preventivo intermedio (5000-10000 km)'),
('Preventivo 03', 'Preventivo 03', 'Paquete de mantenimiento preventivo avanzado (10000-15000 km)'),
('Preventivo 04', 'Preventivo 04', 'Paquete de mantenimiento preventivo completo (15000-20000 km)'),
('Preventivo 05', 'Preventivo 05', 'Paquete de mantenimiento preventivo integral (20000+ km)'),
('Correctivo 01', 'Correctivo 01', 'Reparaciones correctivas básicas'),
('Correctivo 02', 'Correctivo 02', 'Reparaciones correctivas intermedias'),
('Correctivo 03', 'Correctivo 03', 'Reparaciones correctivas avanzadas');
