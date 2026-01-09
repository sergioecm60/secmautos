<?php
/**
 * File de configuración de seguridad
 * Este archivo define constantes y funciones para mejorar la seguridad del sistema
 */

// Configuración de seguridad
define('MAX_INTENTOS_USUARIO', 5);
define('MAX_INTENTOS_IP', 10);
define('BLOQUEO_USUARIO_MINUTOS', 15);
define('BLOQUEO_IP_MINUTOS', 30);

// Headers de seguridad
function setSecurityHeaders() {
    // Prevenir clickjacking
    header('X-Frame-Options: DENY');

    // Prevenir MIME sniffing
    header('X-Content-Type-Options: nosniff');

    // XSS Protection
    header('X-XSS-Protection: 1; mode=block');

    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Content Security Policy (básico, ajustar según necesidades)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self';");

    // HSTS (opcional, descomentar en producción con HTTPS)
    // header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

    // No-cache para respuestas sensibles
    if (in_array($_SERVER['REQUEST_METHOD'] ?? '', ['POST', 'PUT', 'DELETE'])) {
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}

// Validación de tipos
function validateInt($value, $min = 0, $max = PHP_INT_MAX) {
    $value = (int)$value;
    if ($value < $min || $value > $max) {
        return false;
    }
    return $value;
}

function validateString($value, $maxLength = 255, $minLength = 0) {
    $value = trim($value);
    if (strlen($value) < $minLength || strlen($value) > $maxLength) {
        return false;
    }
    return $value;
}

function validateEmail($email) {
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    // Validar longitud máxima de email
    if (strlen($email) > 254) {
        return false;
    }
    return $email;
}

// Sanitización mejorada
function sanitizeInput($data, $type = 'string') {
    switch ($type) {
        case 'int':
            return (int)filter_var($data, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return (float)filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'email':
            return filter_var(trim($data), FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var($data, FILTER_SANITIZE_URL);
        case 'html':
            return htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8');
        case 'string':
        default:
            return trim(htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8'));
    }
}

// Validación de fortaleza de contraseña
function validatePasswordStrength($password) {
    if (strlen($password) < 8) {
        return false;
    }

    $errors = [];

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Debe contener al menos una mayúscula';
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Debe contener al menos una minúscula';
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Debe contener al menos un número';
    }

    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Debe contener al menos un carácter especial';
    }

    return empty($errors) ? true : $errors;
}

// Rate limiting simple
function checkRateLimit($identifier, $maxRequests = 100, $timeWindow = 60) {
    global $pdo;

    $key = sha1($identifier . $timeWindow);

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as request_count
        FROM rate_limits
        WHERE identifier = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $stmt->execute([$key, $timeWindow]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['request_count'] >= $maxRequests) {
        return false;
    }

    // Registrar la solicitud
    $stmt = $pdo->prepare("
        INSERT INTO rate_limits (identifier, created_at)
        VALUES (?, NOW())
        ON DUPLICATE KEY UPDATE request_count = request_count + 1
    ");
    $stmt->execute([$key]);

    return true;
}

// Prevenir inyección SQL mejorada
function safeQuery($query, $params = []) {
    global $pdo;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt;
}

// Validar y sanitizar ID
function sanitizeId($id) {
    $id = (int)$id;
    if ($id <= 0) {
        return null;
    }
    return $id;
}

// Validar referer (opcional, para prevenir CSRF)
function validateReferer($allowedDomains = []) {
    if (empty($_SERVER['HTTP_REFERER'])) {
        return false;
    }

    $referer = parse_url($_SERVER['HTTP_REFERER']);
    $host = $referer['host'] ?? '';

    if (empty($allowedDomains)) {
        return true;
    }

    return in_array($host, $allowedDomains);
}

// Sanitizar output JSON
function safeJsonEncode($data) {
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// Log de seguridad
function logSecurityEvent($event, $details, $severity = 'INFO') {
    global $pdo;

    $stmt = $pdo->prepare("
        INSERT INTO security_logs (event_type, details, severity, ip_address, user_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $event,
        $details,
        $severity,
        $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
        $_SESSION['usuario_id'] ?? null
    ]);
}

// Llamar al inicio de cada petición
setSecurityHeaders();
