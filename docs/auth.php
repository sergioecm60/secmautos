<?php
// auth.php - Sistema de Autenticación y Permisos
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/config.php';

function loginUsuario($username, $password, $captcha, $pdo) {
    // Verificación de captcha más estricta
    if (!isset($_SESSION['captcha']) || strval($_SESSION['captcha']) !== strval($captcha)) {
        unset($_SESSION['captcha']);
        return ['success' => false, 'message' => 'Captcha incorrecto'];
    }
    unset($_SESSION['captcha']);
    
    // 🛡️ 1. Seguridad: Agregar Rate Limiting por IP
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'NA';
    $stmt_ip = $pdo->prepare("SELECT intentos, bloqueado_hasta FROM intentos_login_ip WHERE ip_address = ?");
    $stmt_ip->execute([$ip_address]);
    $ip_data = $stmt_ip->fetch(PDO::FETCH_ASSOC);

    if ($ip_data && $ip_data['bloqueado_hasta'] && new DateTime() < new DateTime($ip_data['bloqueado_hasta'])) {
        return ['success' => false, 'message' => 'Demasiados intentos fallidos desde esta IP. Intente más tarde.'];
    }

    // Limpiar IPs antiguas que no han tenido intentos en 24 horas
    $pdo->exec("DELETE FROM intentos_login_ip WHERE ultimo_intento < DATE_SUB(NOW(), INTERVAL 1 DAY)");

    $stmt = $pdo->prepare("SELECT u.*, r.nombre as rol_nombre, r.nivel as rol_nivel, r.permisos
                          FROM usuarios u 
                          JOIN roles r ON u.rol_id = r.id 
                          WHERE u.username = ? AND u.activo = 1");
    $stmt->execute([$username]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        registrarLog(null, 'LOGIN_FALLIDO', 'AUTH', "Intento de login con usuario inexistente: $username", $pdo);
        return ['success' => false, 'message' => 'Usuario o contraseña incorrectos'];
    }
    
    if ($usuario['bloqueado_hasta'] && new DateTime() < new DateTime($usuario['bloqueado_hasta'])) {
        $tiempo_restante = (new DateTime($usuario['bloqueado_hasta']))->diff(new DateTime())->format('%i minutos');
        return ['success' => false, 'message' => "Usuario bloqueado. Tiempo restante: $tiempo_restante"];
    }
    
    if (!password_verify($password, $usuario['password'])) {
        $intentos = $usuario['intentos_fallidos'] + 1;
        $bloqueado_hasta = null;

        // Usar constantes para la configuración de seguridad
        if ($intentos >= MAX_INTENTOS_USUARIO) {
            $bloqueado_hasta = date('Y-m-d H:i:s', strtotime('+' . BLOQUEO_USUARIO_MINUTOS . ' minute'));
        }
        
        $stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = ?, bloqueado_hasta = ? WHERE id = ?");
        $stmt->execute([$intentos, $bloqueado_hasta, $usuario['id']]);
        
        registrarLog($usuario['id'], 'LOGIN_FALLIDO', 'AUTH', "Contraseña incorrecta", $pdo);

        // Actualizar contador de intentos por IP
        if ($ip_data) {
            $nuevos_intentos_ip = $ip_data['intentos'] + 1;
            $bloqueo_ip = $nuevos_intentos_ip >= MAX_INTENTOS_IP ? date('Y-m-d H:i:s', strtotime('+' . BLOQUEO_IP_MINUTOS . ' minutes')) : null;
            $stmt_ip_update = $pdo->prepare("UPDATE intentos_login_ip SET intentos = ?, bloqueado_hasta = ? WHERE ip_address = ?");
            $stmt_ip_update->execute([$nuevos_intentos_ip, $bloqueo_ip, $ip_address]);
        } else {
            $pdo->prepare("INSERT INTO intentos_login_ip (ip_address, intentos) VALUES (?, 1)")->execute([$ip_address]);
        }
        
        if ($intentos >= MAX_INTENTOS_USUARIO) {
            return ['success' => false, 'message' => 'Usuario bloqueado por ' . BLOQUEO_USUARIO_MINUTOS . ' minuto(s) debido a múltiples intentos fallidos'];
        }
        
        return ['success' => false, 'message' => "Usuario o contraseña incorrectos. Intentos restantes: " . (MAX_INTENTOS_USUARIO - $intentos)];
    }
    
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['username'] = $usuario['username'];
    $_SESSION['nombre_completo'] = $usuario['nombre'] . ' ' . $usuario['apellido'];
    $_SESSION['rol'] = $usuario['rol_nombre'];
    $_SESSION['rol_nivel'] = $usuario['rol_nivel'];
    $_SESSION['permisos'] = json_decode($usuario['permisos'], true);
    $_SESSION['primer_login'] = (bool)$usuario['primer_login'];
    
    $stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_login = NOW() WHERE id = ?");
    $stmt->execute([$usuario['id']]);
    
    $session_id = session_id();
    $stmt = $pdo->prepare("INSERT INTO sesiones (id, usuario_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 8 HOUR))");
    $stmt->execute([$session_id, $usuario['id'], $ip_address, $_SERVER['HTTP_USER_AGENT'] ?? 'NA']);
    
    registrarLog($usuario['id'], 'LOGIN_EXITOSO', 'AUTH', "Login exitoso", $pdo);

    // 🚀 NUEVO: Lógica para múltiples sucursales
    $stmt_sucursales = $pdo->prepare("
        SELECT s.id, s.nombre 
        FROM usuario_sucursales us
        JOIN sucursales s ON us.sucursal_id = s.id
        WHERE us.usuario_id = ? AND s.activo = 1
    ");
    $stmt_sucursales->execute([$usuario['id']]);
    $sucursales_asignadas = $stmt_sucursales->fetchAll(PDO::FETCH_ASSOC);

    if (count($sucursales_asignadas) === 1) {
        $_SESSION['sucursal_id'] = $sucursales_asignadas[0]['id'];
        $_SESSION['sucursal_nombre'] = $sucursales_asignadas[0]['nombre'];
    } elseif (count($sucursales_asignadas) > 1) {
        $_SESSION['sucursales_disponibles'] = $sucursales_asignadas;
    } else {
        // No tiene sucursales activas asignadas, no puede operar.
    }
    
    return ['success' => true, 'message' => 'Login exitoso', 'primer_login' => (bool)$usuario['primer_login']];
}

function logout($pdo) {
    if (isset($_SESSION['usuario_id'])) {
        $stmt = $pdo->prepare("UPDATE sesiones SET activa = 0 WHERE id = ?");
        $stmt->execute([session_id()]);
        
        registrarLog($_SESSION['usuario_id'], 'LOGOUT', 'AUTH', "Logout", $pdo);
    }
    
    session_unset();
    session_destroy();
}

function verificarPermiso($modulo, $accion) {
    if (!isset($_SESSION['permisos'], $_SESSION['permisos'][$modulo])) {
        return false;
    }
    return in_array($accion, $_SESSION['permisos'][$modulo]) || in_array('administrar', $_SESSION['permisos'][$modulo]);
}

function requierePermiso($modulo, $accion, $redirect = true) {
    if (!isset($_SESSION['usuario_id'])) {
        if ($redirect) {
            header('Location: login.php');
            exit;
        }
        return false;
    }
    
    // 1. Seguridad: Validar que la sesión actual siga activa en la base de datos.
    // Esto previene que un usuario continúe navegando si su sesión fue cerrada remotamente.
    global $pdo; // Hacer la conexión a la BD disponible dentro de la función.
    $stmt_session = $pdo->prepare("SELECT activa FROM sesiones WHERE id = ? AND usuario_id = ?");
    $stmt_session->execute([session_id(), $_SESSION['usuario_id']]);
    $sesion_db = $stmt_session->fetch(PDO::FETCH_ASSOC);

    if (!$sesion_db || $sesion_db['activa'] == 0) {
        if ($redirect) {
            logout($pdo); // Usar la función logout para una limpieza completa.
            header('Location: login.php?error=session_terminated');
            exit;
        }
        return false;
    }

    // 🚀 NUEVO: Forzar selección de sucursal si el usuario tiene múltiples y no ha elegido.
    $script_actual = basename($_SERVER['PHP_SELF']);
    if (!isset($_SESSION['sucursal_id']) && isset($_SESSION['sucursales_disponibles']) && $script_actual !== 'seleccionar_sucursal.php' && $script_actual !== 'logout.php') {
        if ($redirect) {
            header('Location: seleccionar_sucursal.php');
            exit;
        }
        return false;
    }

    // 🎨 3. UX: Forzar Cambio de Contraseña en Primer Login
    $script_actual = basename($_SERVER['PHP_SELF']);
    if (isset($_SESSION['primer_login']) && $_SESSION['primer_login'] && $script_actual !== 'cambiar_password.php' && $script_actual !== 'logout.php') {
        if ($redirect) {
            // Guardar un mensaje para la página de cambio de contraseña
            $_SESSION['mensaje_cambio_pass'] = "Por tu seguridad, debes cambiar tu contraseña antes de continuar.";
            header('Location: cambiar_password.php');
            exit;
        }
        return false;
    }

    if (!verificarPermiso($modulo, $accion)) {
        if ($redirect) {
            header('Location: sin_permisos.php');
            exit;
        }
        return false;
    }
    
    return true;
}

function registrarLog($usuario_id, $accion, $modulo, $descripcion, $pdo, $datos_anteriores = null, $datos_nuevos = null) {
    try {
        $stmt = $pdo->prepare("INSERT INTO logs_actividad (usuario_id, accion, modulo, descripcion, datos_anteriores, datos_nuevos, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $usuario_id, $accion, $modulo, $descripcion,
            $datos_anteriores ? json_encode($datos_anteriores, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            $datos_nuevos ? json_encode($datos_nuevos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            $_SERVER['REMOTE_ADDR'] ?? 'NA',
            $_SERVER['HTTP_USER_AGENT'] ?? 'NA'
        ]);
    } catch (Exception $e) {
        error_log("Error al registrar log: " . $e->getMessage());
        // ✍️ MEJORA: Registrar errores críticos en un archivo físico.
        // Esto es útil si la base de datos no está disponible.
        $log_dir = __DIR__ . '/logs';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        $log_file = $log_dir . '/app_errors.log';
        $error_message = "[" . date('Y-m-d H:i:s') . "] Error al registrar log en BD: " . $e->getMessage() . "\n";
        $error_message .= "Log original: [Usuario: $usuario_id, Acción: $accion, Módulo: $modulo, Descripción: $descripcion]\n\n";
        file_put_contents($log_file, $error_message, FILE_APPEND);
    }
}
?>