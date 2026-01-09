<?php
session_start();

if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'bootstrap.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SECM Autos</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
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
                <div class="login-logo">🚗</div>
                <h1>SECM Autos</h1>
                <p>Sistema de Gestión de Vehículos</p>
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
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                
                <div class="form-group">
                    <label for="email">📧 Email</label>
                    <input type="email" id="email" name="email" required placeholder="Ingrese su email" autocomplete="username" autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">🔒 Contraseña</label>
                    <input type="password" id="password" name="password" required placeholder="Ingrese su contraseña" autocomplete="current-password">
                </div>
                
                <div class="captcha-container">
                    <div class="captcha-image" id="captcha-box" title="Click para refrescar"></div>
                    <input type="number" id="captcha" name="captcha" class="captcha-input" required placeholder="Código" autocomplete="off">
                    <button type="button" class="refresh-captcha" title="Refrescar captcha">🔄</button>
                </div>
                
                <button type="submit" class="btn btn-login" id="btnSubmit">🔐 Iniciar Sesión</button>

                <div id="loading" class="loading-spinner" style="display: none;">
                    <div class="spinner"></div>
                    <p>Iniciando sesión...</p>
                </div>
            </form>
        </div>
    </main>
    
    <script src="assets/js/login.js"></script>
    <script src="assets/js/theme-switcher.js"></script>
</body>
</html>
