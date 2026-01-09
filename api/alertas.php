<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!verificar_autenticacion()) {
    json_response(['success' => false, 'message' => 'No autenticado'], 401);
}

try {
    $stmt = $pdo->query("
        SELECT a.*, v.patente 
        FROM alertas a
        LEFT JOIN vehiculos v ON a.vehiculo_id = v.id
        WHERE a.vista = 0 AND a.resuelta = 0
        ORDER BY a.fecha_alerta ASC, a.created_at DESC
        LIMIT 20
    ");
    $alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    json_response(['success' => true, 'data' => $alertas]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Error al obtener alertas: ' . $e->getMessage()], 500);
}
