<?php
require_once __DIR__ . '/../bootstrap.php';

require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Método no permitido'], 405);
}

if (!isset($_POST['csrf_token']) || !verificar_csrf($_POST['csrf_token'])) {
    json_response(['success' => false, 'message' => 'Token CSRF inválido'], 403);
}

$username = sanitizar_input($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$captcha = $_POST['captcha'] ?? '';

if (empty($username) || empty($password) || empty($captcha)) {
    json_response(['success' => false, 'message' => 'Todos los campos son obligatorios']);
}

$resultado = loginUsuario($username, $password, $captcha, $pdo);

if ($resultado['success']) {
    json_response($resultado);
} else {
    json_response($resultado, 401);
}
