<?php
require_once '../auth.php'; // Usa el nuevo sistema de autenticación

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $captcha_attempt = $_POST['captcha'] ?? '';

    if (empty($username) || empty($password) || empty($captcha_attempt)) {
        $_SESSION['login_error'] = 'Todos los campos son obligatorios.';
    } else {
        $resultado = loginUsuario($username, $password, $captcha_attempt, $pdo);
        if ($resultado['success']) {
            if ($resultado['primer_login']) {
                header('Location: ../cambiar_password.php');
            // 🚀 NUEVO: Si no tiene sucursal_id, debe seleccionar una.
            } elseif (!isset($_SESSION['sucursal_id'])) {
                header('Location: ../seleccionar_sucursal.php');
            } else {
                header('Location: ../index.php');
            }
            exit();
        } else {
            $_SESSION['login_error'] = $resultado['message'];
        }
    }

    header('Location: ../login.php');
    exit();
}