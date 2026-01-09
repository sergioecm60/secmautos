<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO DE SECM AUTOS ===\n\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Tiempo de Argentina: " . date_default_timezone_get() . "\n\n";

echo "=== RUTAS ===\n";
echo "Directorio actual: " . __DIR__ . "\n";
echo "Ruta absoluta: " . realpath(__DIR__) . "\n\n";

echo "=== ARCHIVOS EXISTENTES ===\n";
$archivos = [
    'login.php' => __DIR__ . '/login.php',
    'bootstrap.php' => __DIR__ . '/bootstrap.php',
    'api/auth.php' => __DIR__ . '/api/auth.php',
    'api/login_handler.php' => __DIR__ . '/api/login_handler.php',
    'api/refresh_captcha.php' => __DIR__ . '/api/refresh_captcha.php',
    'assets/css/style.css' => __DIR__ . '/assets/css/style.css',
    'assets/css/themes.css' => __DIR__ . '/assets/css/themes.css',
    'assets/js/login.js' => __DIR__ . '/assets/js/login.js',
    'assets/js/theme-switcher.js' => __DIR__ . '/assets/js/theme-switcher.js',
];

foreach ($archivos as $nombre => $ruta) {
    $existe = file_exists($ruta) ? '[OK]' : '[X]';
    $tamano = file_exists($ruta) ? filesize($ruta) . ' bytes' : 'N/A';
    echo "$existe $nombre ($tamano)\n";
}

echo "\n=== CONTENIDO DE LOGIN.PHP (líneas 15-25) ===\n";
$login_content = file_get_contents(__DIR__ . '/login.php');
$lineas = explode("\n", $login_content);
for ($i = 14; $i <= 25 && $i < count($lineas); $i++) {
    echo "Línea " . ($i + 1) . ": " . trim($lineas[$i]) . "\n";
}

echo "\n=== INSPECCIÓN DE ESTILOS (CSS) ===\n";
$css_files = ['assets/css/style.css', 'assets/css/themes.css'];
foreach ($css_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "--- Inicio de $file ---\n";
        echo substr(file_get_contents($path), 0, 300) . "\n...\n\n";
    }
}

echo "\n=== CONTENIDO DE API/AUTH.PHP ===\n";
if (file_exists(__DIR__ . '/api/auth.php')) {
    echo file_get_contents(__DIR__ . '/api/auth.php');
}

echo "\n=== CAPTCHA TEST ===\n";
// Mock de la función si no existe para evitar error fatal en el diagnóstico
if (!function_exists('generar_captcha')) {
    function generar_captcha() {
        return ['num1' => 5, 'num2' => 3, 'operator' => '+'];
    }
}

$captcha = generar_captcha();
echo "num1: " . $captcha['num1'] . "\n";
echo "num2: " . $captcha['num2'] . "\n";
echo "operator: " . $captcha['operator'] . "\n";
echo "Resultado esperado: " . (($captcha['operator'] == '+') ? ($captcha['num1'] + $captcha['num2']) : ($captcha['num1'] - $captcha['num2'])) . "\n";
