<?php
session_start();

// Si ya está logueado, redirigir al index
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/config.php';
require_once 'services/ConfiguracionPersonalizadaService.php';
$config_personalizada = ConfiguracionPersonalizadaService::obtenerConfiguracion($pdo);
$logo_url = $config_personalizada['logo_url'] ?? 'assets/images/logo.png';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SECM Gestión de Agencias de Viajes</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/themes.css">
</head>
<body class="modern-login-body">
    <div class="theme-selector no-print">
        <button class="theme-btn light" title="Tema Claro">☀️</button>
        <button class="theme-btn dark" title="Tema Oscuro">🌙</button>
        <button class="theme-btn auto" title="Automático (según sistema)">🔄</button>
    </div>

    <main class="login-main-content">
        <div class="login-container">
            <div class="login-header">
                <img src="<?= htmlspecialchars($logo_url) ?>" alt="Logo SECM" class="login-logo" onerror="this.style.display='none'">
                <p>SECM Agencias</p>
                <small>Gestión de Boleterias</small>
            </div>
            
            <?php if (isset($_SESSION['mensaje_logout'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['mensaje_logout']) ?>
                    <?php unset($_SESSION['mensaje_logout']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($_SESSION['login_error']) ?>
                    <?php unset($_SESSION['login_error']); ?>
                </div>
            <?php endif; ?>
            
            <form id="loginForm" method="POST" action="api/login_handler.php">
                <div class="form-group">
                    <label for="username">👤 Usuario</label>
                    <input type="text" id="username" name="username" required placeholder="Ingrese su usuario" autocomplete="username" autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">🔒 Contraseña</label>
                    <input type="password" id="password" name="password" required placeholder="Ingrese su contraseña" autocomplete="current-password">
                </div>
                
                <div class="captcha-container">
                    <!-- El contenido del captcha se carga vía JS para mayor seguridad -->
                    <div class="captcha-image" id="captcha-box" title="Click para refrescar"></div>
                    <input type="number" id="captcha" name="captcha" class="captcha-input" required placeholder="Código" autocomplete="off">
                    <button type="button" class="refresh-captcha">🔄</button>
                </div>
                
                <button type="submit" class="btn btn-login">🔐 Iniciar Sesión</button>

                <!-- 🎨 Frontend: Agregar un "Olvidé mi Contraseña" -->
                <div style="text-align: center; margin-top: 20px;">
                    <a href="recuperar_password.php" style="color: #6c757d; text-decoration: none; font-size: 14px;">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
            </form>
        </div>
    </main>
    <?php require_once 'templates/footer.php'; ?>
    <script src="assets/js/login.js"></script>
    <script src="assets/js/theme-switcher.js"></script>
</body>
</html>