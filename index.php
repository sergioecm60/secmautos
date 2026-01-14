<?php
require_once 'bootstrap.php';
requiereAutenticacion();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">
    <title>Dashboard - SECM Autos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/themes.css">
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="user-info">
                <span>👤 <?= htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']) ?></span>
                <span>|</span>
                <span>🏷️ <?= htmlspecialchars($_SESSION['rol']) ?></span>
                <span>|</span>
                <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
            </div>
            <h1>🚗 SECM Flota de Autos</h1>
            <p>Sistema de Gestión de Vehículos</p>
        </div>
    </div>

    <div class="main-nav">
        <div class="container">
            <nav class="nav-buttons">
                <a href="#" class="nav-btn active" data-module="dashboard">📊 Dashboard</a>
                <a href="#" class="nav-btn" data-module="vehiculos">🚗 Vehículos</a>
                <a href="#" class="nav-btn" data-module="empleados">👥 Conductores</a>
                <a href="#" class="nav-btn" data-module="autorizaciones">🔐 Autorizaciones Manejo</a>
                <a href="#" class="nav-btn" data-module="asignaciones">🔄 Asignaciones</a>
                <a href="#" class="nav-btn" data-module="multas">⚠️ Multas</a>
                <a href="#" class="nav-btn" data-module="mantenimientos">🔧 Mantenimiento</a>
                <a href="#" class="nav-btn" data-module="compras_ventas">💸 Compras/Ventas</a>
                <a href="#" class="nav-btn" data-module="transferencias">📂 Transferencias</a>
                <a href="#" class="nav-btn" data-module="pagos">💰 Pagos</a>
                <a href="#" class="nav-btn" data-module="combustible">⛽ Combustible</a>
                <a href="#" class="nav-btn" data-module="talleres">🔧 Talleres</a>
                <a href="#" class="nav-btn" data-module="telepases">🎫 Telepases</a>
                <a href="#" class="nav-btn" data-module="reportes">📈 Reportes</a>
                <a href="#" class="nav-btn" data-module="usuarios">👤 Usuarios</a>
                <a href="#" class="nav-btn" data-module="logs">📋 Logs</a>
                <a href="#" class="nav-btn" data-module="configuracion">⚙️ Configuración</a>
            </nav>
        </div>
    </div>

    <div class="container">
        <div id="alertas-container"></div>

        <div id="module-dashboard" class="module active">
            <div class="card">
                <h3>📊 Resumen General</h3>
                <div class="stats-grid" id="stats-grid"></div>
            </div>

            <div class="card">
                <h3>⚠️ Alertas Activas</h3>
                <div id="alertas-lista"></div>
            </div>

            <div class="card">
                <h3>🚗 Vehículos Próximos a Vencer</h3>
                <div id="vencimientos-lista"></div>
            </div>
        </div>

        <div id="module-vehiculos" class="module"></div>
        <div id="module-empleados" class="module"></div>
        <div id="module-asignaciones" class="module"></div>
        <div id="module-multas" class="module"></div>
        <div id="module-mantenimientos" class="module"></div>
        <div id="module-compras_ventas" class="module"></div>
        <div id="module-transferencias" class="module"></div>
        <div id="module-pagos" class="module"></div>
        <div id="module-combustible" class="module"></div>
        <div id="module-talleres" class="module"></div>
        <div id="module-telepases" class="module"></div>
        <div id="module-ficha_vehiculo" class="module"></div>
        <div id="module-reportes" class="module"></div>
        <div id="module-autorizaciones" class="module"></div>
        <div id="module-usuarios" class="module"></div>
        <div id="module-logs" class="module"></div>
        <div id="module-configuracion" class="module"></div>
    </div>

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

    <script>
        window.phpUsuarioId = '<?= $_SESSION['usuario_id'] ?? '' ?>';
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/theme-switcher.js"></script>

    <!-- Theme Selector -->
    <div class="theme-selector">
        <button class="theme-btn light" data-theme="light" title="Tema Claro">☀️</button>
        <button class="theme-btn dark" data-theme="dark" title="Tema Oscuro">🌙</button>
        <button class="theme-btn auto" data-theme="auto" title="Automático">🔄</button>
    </div>
</body>
</html>
