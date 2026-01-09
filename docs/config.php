<?php
// config.php - Archivo de configuración principal

// Cargar variables de entorno desde el archivo .env en la raíz del proyecto
$dotenv_path = __DIR__ . '/.env';
if (file_exists($dotenv_path)) {
    $lines = file($dotenv_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        // Quita las comillas si existen
        if (substr($value, 0, 1) == '"' && substr($value, -1) == '"') {
            $value = substr($value, 1, -1);
        }
        putenv(sprintf('%s=%s', $name, $value));
        $_ENV[$name] = $value;
    }
}

// --- Configuración de la Base de Datos ---
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'sistema_transportes';
$user = getenv('DB_USER') ?: 'secmagencia';
$pass = getenv('DB_PASS') ?: '';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

// Credenciales para el usuario de backups (con permisos limitados)
define('BACKUP_DB_USER', getenv('BACKUP_DB_USER') ?: 'backup_user');
define('BACKUP_DB_PASS', getenv('BACKUP_DB_PASS') ?: '');

// --- Token de seguridad para Cron Jobs ---
// Este token se define aquí para que esté disponible en los scripts que lo necesiten.
define('CRON_TOKEN', getenv('CRON_SECRET_TOKEN') ?: 'CAMBIAR_ESTE_TOKEN_SECRETO');

// --- Constantes de la aplicación ---
define('MAX_INTENTOS_USUARIO', 5);
define('BLOQUEO_USUARIO_MINUTOS', 1);
define('MAX_INTENTOS_IP', 10);
define('BLOQUEO_IP_MINUTOS', 30);

// --- Centralización de nombres de tablas ---
class Tablas {
    const PAISES = 'paises'; const PROVINCIAS = 'provincias'; const LOCALIDADES = 'localidades';
    const SUCURSALES = 'sucursales'; const CONFIGURACION_AGENCIA = 'configuracion_agencia';
    const CONCEPTOS_CAJA = 'conceptos_caja'; const EMPRESAS = 'empresas';
    const TALONARIOS_BOLETOS = 'talonarios_boletos'; const TALONARIOS_GUIAS = 'talonarios_guias';
    const VENTAS_BOLETOS = 'ventas_boletos'; const VENTAS_GUIAS = 'ventas_guias';
    const CAJA_DIARIA = 'caja_diaria'; const MOVIMIENTOS_CAJA = 'movimientos_caja';
    const LIQUIDACIONES = 'liquidaciones'; const DETALLE_LIQUIDACIONES = 'detalle_liquidaciones';
    const USUARIOS = 'usuarios'; const ROLES = 'roles'; const USUARIO_SUCURSALES = 'usuario_sucursales';
    const SESIONES = 'sesiones'; const INTENTOS_LOGIN_IP = 'intentos_login_ip';
    const LOGS_ACTIVIDAD = 'logs_actividad'; const BACKUPS_SISTEMA = 'backups_sistema';
}

// --- Configuración de la Conexión PDO ---
$dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Configurar la zona horaria de la conexión para consistencia
    $pdo->exec("SET time_zone = '-03:00'");
} catch (\PDOException $e) {
    // En un entorno de producción, no muestres detalles del error.
    // Registra el error en un archivo de log.
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    http_response_code(503); // Service Unavailable
    // Muestra un mensaje genérico al usuario.
    die("Error de conexión con la base de datos. Por favor, intente más tarde.");
}