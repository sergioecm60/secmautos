CREATE DATABASE IF NOT EXISTS secmautos CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE secmautos;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('superadmin', 'admin', 'user') DEFAULT 'user',
    activo BOOLEAN DEFAULT TRUE,
    intentos_fallidos INT DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    ultimo_acceso DATETIME,
    primer_login BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE intentos_login_ip (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    intentos INT DEFAULT 1,
    bloqueado_hasta DATETIME NULL,
    ultimo_intento DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_ip (ip_address),
    INDEX idx_bloqueado (bloqueado_hasta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE vehiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patente VARCHAR(10) UNIQUE NOT NULL,
    marca VARCHAR(50),
    modelo VARCHAR(50),
    anio INT,
    motor VARCHAR(50),
    chasis VARCHAR(50),
    titularidad VARCHAR(100),
    kilometraje_actual INT DEFAULT 0,
    estado ENUM('disponible', 'asignado', 'mantenimiento', 'baja') DEFAULT 'disponible',
    fecha_vtv DATE,
    fecha_seguro DATE,
    fecha_patente DATE,
    fecha_revision_km DATE,
    km_proximo_service INT,
    imagen VARCHAR(255),
    observaciones TEXT,
    fecha_baja DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patente (patente),
    INDEX idx_estado (estado),
    INDEX idx_marca (marca),
    FULLTEXT INDEX idx_busqueda (patente, marca, modelo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE empleados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    dni VARCHAR(20) UNIQUE,
    email VARCHAR(100),
    telefono VARCHAR(50),
    direccion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dni (dni),
    INDEX idx_nombre (nombre, apellido),
    FULLTEXT INDEX idx_busqueda (nombre, apellido, dni)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE asignaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT NOT NULL,
    empleado_id INT NOT NULL,
    fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_devolucion DATETIME NULL,
    km_salida INT,
    km_regreso INT,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
    INDEX idx_vehiculo (vehiculo_id),
    INDEX idx_empleado (empleado_id),
    INDEX idx_fecha (fecha_asignacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE multas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT NOT NULL,
    empleado_id INT NOT NULL,
    fecha_multa DATE NOT NULL,
    monto DECIMAL(10,2),
    motivo TEXT,
    acta_numero VARCHAR(50),
    pagada BOOLEAN DEFAULT FALSE,
    fecha_pago DATE NULL,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
    INDEX idx_vehiculo (vehiculo_id),
    INDEX idx_empleado (empleado_id),
    INDEX idx_fecha (fecha_multa),
    INDEX idx_pagada (pagada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT NOT NULL,
    tipo ENUM('patente', 'seguro', 'otro') NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    fecha_pago DATE NULL,
    monto DECIMAL(10,2),
    comprobante VARCHAR(255),
    observaciones TEXT,
    pagado BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    INDEX idx_vehiculo (vehiculo_id),
    INDEX idx_tipo (tipo),
    INDEX idx_vencimiento (fecha_vencimiento),
    INDEX idx_pagado (pagado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT NOT NULL,
    fecha DATE NOT NULL,
    factura_numero VARCHAR(50),
    proveedor VARCHAR(100),
    cuit VARCHAR(20),
    importe_neto DECIMAL(10,2),
    iva DECIMAL(10,2),
    total DECIMAL(10,2),
    comprobante VARCHAR(255),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    INDEX idx_vehiculo (vehiculo_id),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT NOT NULL,
    fecha DATE NOT NULL,
    factura_numero VARCHAR(50),
    comprador VARCHAR(100),
    cuit VARCHAR(20),
    importe DECIMAL(10,2),
    comprobante VARCHAR(255),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    INDEX idx_vehiculo (vehiculo_id),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE ceta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT NOT NULL,
    cedula_azul_numero VARCHAR(50),
    fecha_vencimiento DATE NOT NULL,
    fecha_envio DATE,
    enviado BOOLEAN DEFAULT FALSE,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    INDEX idx_vehiculo (vehiculo_id),
    INDEX idx_vencimiento (fecha_vencimiento),
    INDEX idx_enviado (enviado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE transferencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT NOT NULL,
    fecha DATE NOT NULL,
    registro VARCHAR(100),
    direccion_registro TEXT,
    numero_tramite VARCHAR(50),
    estado ENUM('en_proceso', 'completa', 'cancelada') DEFAULT 'en_proceso',
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    INDEX idx_vehiculo (vehiculo_id),
    INDEX idx_fecha (fecha),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE mantenimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT NOT NULL,
    fecha DATE NOT NULL,
    tipo ENUM('preventivo', 'correctivo') NOT NULL,
    descripcion TEXT NOT NULL,
    costo DECIMAL(10,2),
    kilometraje INT,
    proveedor VARCHAR(100),
    comprobante VARCHAR(255),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    INDEX idx_vehiculo (vehiculo_id),
    INDEX idx_fecha (fecha),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE alertas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT,
    tipo_alerta ENUM('vtv', 'seguro', 'patente', 'ceta', 'km', 'multa') NOT NULL,
    mensaje TEXT NOT NULL,
    fecha_alerta DATE NOT NULL,
    vista BOOLEAN DEFAULT FALSE,
    fecha_resolucion DATE NULL,
    resuelta BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    INDEX idx_vehiculo (vehiculo_id),
    INDEX idx_tipo (tipo_alerta),
    INDEX idx_vista (vista),
    INDEX idx_resuelta (resuelta),
    INDEX idx_fecha (fecha_alerta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    accion VARCHAR(100) NOT NULL,
    entidad VARCHAR(50),
    entidad_id INT,
    detalles TEXT,
    ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_accion (accion),
    INDEX idx_entidad (entidad),
    INDEX idx_fecha (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO usuarios (nombre, apellido, email, password_hash, rol, activo, primer_login) VALUES
('Admin', 'Sistema', 'admin@secmautos.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', TRUE, FALSE);
