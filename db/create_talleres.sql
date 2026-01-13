CREATE TABLE talleres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    direccion TEXT,
    telefono VARCHAR(50),
    email VARCHAR(100),
    contacto_principal VARCHAR(100),
    activo BOOLEAN DEFAULT TRUE,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activo (activo),
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO talleres (nombre, direccion, telefono, email, contacto_principal) VALUES
('Taller Los Mecánicos', 'Calle Los Tulipanes 324', '923456734', 'servicio@losmecanicos.com', 'Rosa Mercedes del Prado'),
('Mecánica Armando Autos', 'Calle Mineros del campo 872', '904356728', 'informes@armando.como', 'Jorge Revoredo'),
('Taller de autos Megalodón', 'Jr. Las Terrazas 673', '934526734', 'ventas@megalodon.com', 'María Mendez'),
('Autorepuestos el Gavilán', 'Av. Mariscal Perroni 1523', '900245637', 'mantenimiento@elgavilan.com', 'Ana Perez'),
('Remodelamiento Automotriz', 'Av. Las Camelias del Bosque 3215', '934526782', 'informes@remodautos.com', 'Luciana Pereira'),
('Automotriz El Grande', 'Jr. Feligreces Azules 287', '983462312', 'ventas@elgrande.com', 'Mariana Romero');
