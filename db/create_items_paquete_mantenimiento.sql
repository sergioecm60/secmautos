CREATE TABLE items_paquete_mantenimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paquete_id INT NOT NULL,
    item VARCHAR(200) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paquete_id) REFERENCES paquetes_mantenimiento(id) ON DELETE CASCADE,
    INDEX idx_paquete (paquete_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO items_paquete_mantenimiento (paquete_id, item) VALUES
-- Preventivo 01
(1, 'Ajuste del Tiempo del Motor'),
(1, 'Cambio de Aceite y Filtro de Aceite'),
(1, 'Cambio de Filtro de Aire'),
(1, 'Inspección de la Correa de Distribución'),
(1, 'Inspección del Sistema de Frenos'),
-- Preventivo 02
(2, 'Inspección del Sistema de Suspensión'),
(2, 'Mantenimiento de la Batería'),
(2, 'Mantenimiento del Sistema de Combustible'),
(2, 'Mantenimiento del Sistema de Dirección'),
-- Preventivo 03
(3, 'Reemplazo de Bujías, Cables y Correas Serpentinas'),
(3, 'Revisión de Amortiguadores'),
(3, 'Revisión de Luces'),
(3, 'Revisión del Dispositivo de Acoplamiento'),
-- Preventivo 04
(4, 'Revisión del Sistema de Admisión, Mangueras y Tuberías'),
(4, 'Revisión del Sistema de Escape'),
(4, 'Servicio Completo de Mantenimiento Automotriz'),
-- Preventivo 05
(5, 'Verificación de Neumáticos'),
(5, 'Verificación de Niveles de Fluidos'),
(5, 'Verificación del Funcionamiento del Motor'),
(5, 'Verificación del Sistema de Transmisión'),
-- Correctivo 01
(6, 'Planchado y pintado'),
-- Correctivo 02
(7, 'Cambio de puertas'),
-- Correctivo 03
(8, 'Eliminación de imperfecciones de la pintura');
