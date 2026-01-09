-- Migración: Agregar tabla de rate limiting y security logs
-- Fecha: 2026-01-09
-- Descripción: Tablas para mejorar la seguridad del sistema

CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL COMMENT 'IP address o user_id',
    request_count INT DEFAULT 1 COMMENT 'Número de solicitudes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_identifier_time (identifier, created_at),
    INDEX idx_identifier (identifier),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Rate limiting para prevenir ataques de fuerza bruta';

CREATE TABLE IF NOT EXISTS security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL COMMENT 'Tipo de evento: LOGIN_FAILED, XSS_ATTEMPT, SQL_INJECTION, etc.',
    details TEXT COMMENT 'Detalles del evento',
    severity ENUM('INFO', 'WARNING', 'ERROR', 'CRITICAL') DEFAULT 'INFO',
    ip_address VARCHAR(45) COMMENT 'Dirección IP',
    user_id INT NULL COMMENT 'ID del usuario si está autenticado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_severity (severity),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Logs de eventos de seguridad';

-- Procedimiento para limpiar registros antiguos de rate limits
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS limpiar_rate_limits_antiguos()
BEGIN
    DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY);
END //
DELIMITER ;

-- Procedimiento para limpiar logs de seguridad antiguos (mantener 90 días)
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS limpiar_security_logs_antiguos()
BEGIN
    DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
END //
DELIMITER ;

-- Evento para limpiar rate limits cada hora
CREATE EVENT IF NOT EXISTS limpiar_rate_limits_event
ON SCHEDULE EVERY 1 HOUR
DO CALL limpiar_rate_limits_antiguos();

-- Evento para limpiar logs de seguridad cada día
CREATE EVENT IF NOT EXISTS limpiar_security_logs_event
ON SCHEDULE EVERY 1 DAY
DO CALL limpiar_security_logs_antiguos();
