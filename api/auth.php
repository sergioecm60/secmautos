<?php
require_once __DIR__ . '/../bootstrap.php';

function loginUsuario($email, $password, $captcha, $pdo) {
    if (!isset($_SESSION['captcha']) || strval($_SESSION['captcha']) !== strval($captcha)) {
        unset($_SESSION['captcha']);
        return ['success' => false, 'message' => 'Captcha incorrecto'];
    }
    unset($_SESSION['captcha']);
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'NA';
    $stmt_ip = $pdo->prepare("SELECT intentos, bloqueado_hasta FROM intentos_login_ip WHERE ip_address = ?");
    $stmt_ip->execute([$ip_address]);
    $ip_data = $stmt_ip->fetch(PDO::FETCH_ASSOC);

    if ($ip_data && $ip_data['bloqueado_hasta'] && new DateTime() < new DateTime($ip_data['bloqueado_hasta'])) {
        return ['success' => false, 'message' => 'Demasiados intentos fallidos desde esta IP. Intente más tarde.'];
    }

    $stmt = $pdo->prepare("DELETE FROM intentos_login_ip WHERE ultimo_intento < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $stmt->execute();

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND activo = 1");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        registrarLog(null, 'LOGIN_FALLIDO', 'AUTH', "Intento de login con email inexistente: $email", $pdo);
        return ['success' => false, 'message' => 'Usuario o contraseña incorrectos'];
    }
    
    if ($usuario['bloqueado_hasta'] && new DateTime() < new DateTime($usuario['bloqueado_hasta'])) {
        $tiempo_restante = (new DateTime($usuario['bloqueado_hasta']))->diff(new DateTime())->format('%i minutos');
        return ['success' => false, 'message' => "Usuario bloqueado. Tiempo restante: $tiempo_restante"];
    }
    
    if (!password_verify($password, $usuario['password_hash'])) {
        $intentos = $usuario['intentos_fallidos'] ?? 0 + 1;
        $bloqueado_hasta = null;

        if ($intentos >= MAX_INTENTOS_USUARIO) {
            $bloqueado_hasta = date('Y-m-d H:i:s', strtotime('+' . BLOQUEO_USUARIO_MINUTOS . ' minute'));
        }
        
        $stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = ?, bloqueado_hasta = ? WHERE id = ?");
        $stmt->execute([$intentos, $bloqueado_hasta, $usuario['id']]);
        
        registrarLog($usuario['id'], 'LOGIN_FALLIDO', 'AUTH', "Contraseña incorrecta", $pdo);

        if ($ip_data) {
            $nuevos_intentos_ip = $ip_data['intentos'] + 1;
            $bloqueo_ip = $nuevos_intentos_ip >= MAX_INTENTOS_IP ? date('Y-m-d H:i:s', strtotime('+' . BLOQUEO_IP_MINUTOS . ' minutes')) : null;
            $stmt_ip_update = $pdo->prepare("UPDATE intentos_login_ip SET intentos = ?, bloqueado_hasta = ?, ultimo_intento = NOW() WHERE ip_address = ?");
            $stmt_ip_update->execute([$nuevos_intentos_ip, $bloqueo_ip, $ip_address]);
        } else {
            $pdo->prepare("INSERT INTO intentos_login_ip (ip_address, intentos, ultimo_intento) VALUES (?, 1, NOW())")->execute([$ip_address]);
        }
        
        if ($intentos >= MAX_INTENTOS_USUARIO) {
            return ['success' => false, 'message' => 'Usuario bloqueado por ' . BLOQUEO_USUARIO_MINUTOS . ' minuto(s)'];
        }
        
        return ['success' => false, 'message' => "Usuario o contraseña incorrectos. Intentos restantes: " . (MAX_INTENTOS_USUARIO - $intentos)];
    }
    
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['email'] = $usuario['email'];
    $_SESSION['nombre'] = $usuario['nombre'];
    $_SESSION['apellido'] = $usuario['apellido'];
    $_SESSION['rol'] = $usuario['rol'];
    
    $stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW() WHERE id = ?");
    $stmt->execute([$usuario['id']]);
    
    registrarLog($usuario['id'], 'LOGIN_EXITOSO', 'AUTH', "Login exitoso", $pdo);
    
    return ['success' => true, 'message' => 'Login exitoso'];
}

function logout($pdo) {
    if (isset($_SESSION['usuario_id'])) {
        registrarLog($_SESSION['usuario_id'], 'LOGOUT', 'AUTH', "Logout", $pdo);
    }
    
    session_unset();
    session_destroy();
}

function requiereRol($roles_permitidos, $redirect = true) {
    if (!isset($_SESSION['usuario_id'])) {
        if ($redirect) {
            header('Location: /login.php');
            exit;
        }
        return false;
    }
    
    if (!in_array($_SESSION['rol'], $roles_permitidos)) {
        if ($redirect) {
            header('Location: /sin_permisos.php');
            exit;
        }
        return false;
    }
    
    return true;
}

function registrarLog($usuario_id, $accion, $modulo, $descripcion, $pdo, $datos_anteriores = null, $datos_nuevos = null) {
    try {
        $stmt = $pdo->prepare("INSERT INTO logs (usuario_id, accion, entidad, detalles, ip) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $usuario_id,
            $accion,
            $modulo,
            $descripcion,
            $_SERVER['REMOTE_ADDR'] ?? 'NA'
        ]);
    } catch (Exception $e) {
        error_log("Error al registrar log: " . $e->getMessage());
    }
}
