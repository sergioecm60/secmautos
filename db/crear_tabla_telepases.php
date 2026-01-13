<?php
require_once 'bootstrap.php';

$sql = "CREATE TABLE IF NOT EXISTS telepases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patente VARCHAR(10) NOT NULL,
    vehiculo_id INT,
    numero_dispositivo VARCHAR(50) NOT NULL,
    nombre_peaje VARCHAR(100) NOT NULL,
    ubicacion VARCHAR(200),
    fecha_hora_paso DATETIME NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    comprobante VARCHAR(255),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE SET NULL,
    INDEX idx_patente (patente),
    INDEX idx_vehiculo_id (vehiculo_id),
    INDEX idx_fecha_paso (fecha_hora_paso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $pdo->exec($sql);
    echo "✅ Tabla telepases creada exitosamente";
} catch (PDOException $e) {
    echo "❌ Error al crear tabla telepases: " . $e->getMessage();
}
