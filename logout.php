<?php
require_once 'api/auth.php';

// Ejecutar la lógica de logout (destruir sesión y registrar log)
logout($pdo);

// Redirigir al login con un mensaje opcional
session_start(); // Reiniciamos sesión solo para el mensaje flash si es necesario
$_SESSION['mensaje_logout'] = "Sesión cerrada correctamente.";
header('Location: login.php');
exit;