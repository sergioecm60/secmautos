<?php
require_once 'auth.php';

// 🧠 1. Lógica de Negocio: Esta página es solo para usuarios en su primer login.
// Si el usuario no está logueado, o si ya cambió su contraseña inicial,
// se le redirige a la página principal para evitar que acceda a esta pantalla de nuevo.
// Si no está logueado, o si no es su primer login, no debería estar aquí.
if (!isset($_SESSION['usuario_id']) || (isset($_SESSION['primer_login']) && !$_SESSION['primer_login'])) {
    header('Location: index.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// 🎨 2. UX: Mostrar un mensaje de bienvenida/instrucción si el sistema forzó esta página.
// Mostrar mensaje si viene de una redirección forzada
if (isset($_SESSION['mensaje_cambio_pass'])) {
    $mensaje = $_SESSION['mensaje_cambio_pass'];
    unset($_SESSION['mensaje_cambio_pass']);
}

// 🔐 3. Seguridad: Generar un token CSRF para proteger el formulario.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- PROCESAMIENTO DEL FORMULARIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva_password = $_POST['nueva_password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';

    try {
        // 🔐 3.1. Validar el token CSRF.
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            throw new Exception("Error de seguridad. Por favor, intente de nuevo.");
        }

        if (empty($nueva_password) || empty($confirmar_password)) {
            throw new Exception("Todos los campos son obligatorios.");
        }

        if ($nueva_password !== $confirmar_password) {
            throw new Exception("Las nuevas contraseñas no coinciden.");
        }

        //  3.2. Seguridad: Fortalecer la política de contraseñas.
        if (strlen($nueva_password) < 8) {
            throw new Exception("La contraseña debe tener al menos 8 caracteres.");
        }
        if (!preg_match('/[A-Z]/', $nueva_password)) {
            throw new Exception("La contraseña debe contener al menos una letra mayúscula.");
        }
        if (!preg_match('/[a-z]/', $nueva_password)) {
            throw new Exception("La contraseña debe contener al menos una letra minúscula.");
        }
        if (!preg_match('/[0-9]/', $nueva_password)) {
            throw new Exception("La contraseña debe contener al menos un número.");
        }

        // 🔐 3.3. Seguridad: Evitar la reutilización de la contraseña anterior.
        $stmt_old_pass = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
        $stmt_old_pass->execute([$_SESSION['usuario_id']]);
        $usuario = $stmt_old_pass->fetch();

        if ($usuario && password_verify($nueva_password, $usuario['password'])) {
            throw new Exception("La nueva contraseña no puede ser igual a la anterior.");
        }

        // Hashear la nueva contraseña
        $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);

        // Actualizar la contraseña y el flag de primer_login
        $stmt = $pdo->prepare("UPDATE usuarios SET password = ?, primer_login = 0 WHERE id = ?");
        $stmt->execute([$password_hash, $_SESSION['usuario_id']]);

        // Actualizar la sesión y registrar el log
        $_SESSION['primer_login'] = false;
        registrarLog($_SESSION['usuario_id'], 'CAMBIO_PASSWORD_INICIAL', 'AUTH', 'Cambio de contraseña exitoso (primer login).', $pdo);

        // Redirigir al dashboard
        $_SESSION['mensaje'] = "Contraseña cambiada exitosamente. ¡Bienvenido!";
        header('Location: index.php');
        exit;

    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_mensaje = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cambiar Contraseña - SECM Gestión de Agencias de Viajes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/themes.css">
</head>
<body class="modern-login-body">
    <div class="theme-selector no-print">
        <button class="theme-btn light" title="Tema Claro">☀️</button>
        <button class="theme-btn dark" title="Tema Oscuro">🌙</button>
        <button class="theme-btn auto" title="Automático (según sistema)">🔄</button>
    </div>
    <div class="login-container">
        <div class="login-header">
            <p>🔒 Cambio de Contraseña</p>
            <small>Por seguridad, debes establecer una nueva contraseña.</small>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipo_mensaje == 'error' ? 'error' : 'info' ?>">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="form-group">
                <label for="nueva_password">Nueva Contraseña</label>
                <input type="password" id="nueva_password" name="nueva_password" required autocomplete="new-password" placeholder="Mínimo 8 caracteres">
                <small class="form-text">Mínimo 8 caracteres, con mayúsculas, minúsculas y números.</small>
            </div>

            <div class="form-group">
                <label for="confirmar_password">Confirmar Nueva Contraseña</label>
                <input type="password" id="confirmar_password" name="confirmar_password" required autocomplete="new-password" placeholder="Repita la contraseña">
            </div>
            
            <button type="submit" class="btn btn-login">Cambiar Contraseña</button>
        </form>
    </div>
    
    <?php include_once 'templates/footer.php'; ?>
    <script src="assets/js/theme-switcher.js"></script>
</body>
</html>