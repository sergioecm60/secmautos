<?php
require_once 'bootstrap.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Login - SECM Autos</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css?v=1.1">
    <link rel="stylesheet" href="assets/css/style.css?v=1.1">
    <link rel="stylesheet" href="assets/css/themes.css?v=1.1">
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg?v=1.1">
</head>
<body class="modern-login-body" data-theme="light">
    <div class="theme-selector no-print">
        <button class="theme-btn light" title="Tema Claro">☀️</button>
        <button class="theme-btn dark" title="Tema Oscuro">🌙</button>
        <button class="theme-btn auto" title="Automático (según sistema)">🔄</button>
    </div>

    <main class="login-main-content">
        <div class="login-container">
            <div class="login-header">
                <img src="assets/img/logo.png" alt="SECM Flota de Autos" class="login-logo">
                <h1>SECM Flota de Autos</h1>
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
    
    <footer class="page-footer-fixed">
        <p>
            <strong>SECM Flota de Autos</strong> |
            By Sergio Cabrera |
            Copyleft ©2025 |
            <a href="licence.php">Licencia GNU GPL v3</a>
        </p>
        <p class="footer-contact">
            ¿Necesitas ayuda?
            <a href="mailto:sergiomiers@gmail.com">📧 sergiomiers@gmail.com</a> |
            <a href="https://wa.me/541167598452" target="_blank">💬 WhatsApp +54 11 6759-8452</a>
        </p>
    </footer>
    
    <script src="assets/js/login.js?v=1.1"></script>
    <script src="assets/js/theme-switcher.js?v=1.1"></script>
</body>
</html>
