<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!verificar_autenticacion()) {
    json_response(['success' => false, 'message' => 'No autenticado'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verificar_csrf($token)) {
        json_response(['success' => false, 'message' => 'Error de CSRF'], 403);
        return;
    }

    $password_actual = sanitizar_input($_POST['password_actual'] ?? '');
    $password_nueva = sanitizar_input($_POST['password_nueva'] ?? '');

    if (empty($password_actual) || empty($password_nueva)) {
        json_response(['success' => false, 'message' => 'Complete todos los campos'], 400);
        return;
    }

    if (strlen($password_nueva) < 6) {
        json_response(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'], 400);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT password_hash FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            json_response(['success' => false, 'message' => 'Usuario no encontrado'], 404);
            return;
        }

        if (!password_verify($password_actual, $usuario['password_hash'])) {
            json_response(['success' => false, 'message' => 'La contraseña actual es incorrecta'], 401);
            return;
        }

        $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?");
        $stmt->execute([$password_hash, $_SESSION['usuario_id']]);

        registrarLog($_SESSION['usuario_id'], 'CAMBIAR_PASSWORD', 'usuarios', "Usuario cambió su contraseña", $pdo);

        json_response(['success' => true, 'message' => 'Contraseña cambiada correctamente']);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => 'Error al cambiar contraseña: ' . $e->getMessage()], 500);
    }
} else {
    json_response(['success' => false, 'message' => 'Método no permitido'], 405);
}
