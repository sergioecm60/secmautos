<?php
date_default_timezone_set('America/Argentina/Buenos_Aires');

if (session_status() === PHP_SESSION_NONE) {
    session_save_path(__DIR__ . '/sessions');
    if (!file_exists(__DIR__ . '/sessions')) {
        mkdir(__DIR__ . '/sessions', 0755, true);
    }
    session_start();
}

session_save_path(__DIR__ . '/sessions');
if (!file_exists(__DIR__ . '/sessions')) {
    mkdir(__DIR__ . '/sessions', 0755, true);
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: /login.php');
    exit;
}
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/config/database.php';

function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function sanitizar_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function generar_captcha() {
    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $operadores = ['+', '-'];
    $operator = $operadores[array_rand($operadores)];
    
    if ($operator == '+') {
        $resultado = $num1 + $num2;
    } else {
        $resultado = $num1 - $num2;
    }
    
    $_SESSION['captcha'] = $resultado;
    
    return [
        'num1' => $num1,
        'num2' => $num2,
        'operator' => $operator
    ];
}

function verificar_autenticacion() {
    if (!isset($_SESSION['usuario_id'])) {
        return false;
    }
    return true;
}

function verificar_rol($roles_permitidos) {
    if (!verificar_autenticacion()) {
        return false;
    }
    $rol_usuario = $_SESSION['rol'] ?? 'user';
    return in_array($rol_usuario, $roles_permitidos);
}

function verificar_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function requiereAutenticacion($redirect = true) {
    if (!verificar_autenticacion()) {
        if ($redirect) {
            header('Location: /login.php');
            exit;
        }
        return false;
    }
    return true;
}
