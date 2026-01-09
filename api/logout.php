<?php
require_once __DIR__ . '/../api/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Método no permitido'], 405);
}

if (!verificar_autenticacion()) {
    json_response(['success' => false, 'message' => 'No autenticado'], 401);
}

logout($pdo);

json_response(['success' => true, 'message' => 'Sesión cerrada correctamente']);
