<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!verificar_autenticacion()) {
    json_response(['success' => false, 'message' => 'No autenticado'], 401);
}

try {
    $stats = [];
    
    $stats['total_vehiculos'] = $pdo->query("SELECT COUNT(*) FROM vehiculos")->fetchColumn();
    $stats['disponibles'] = $pdo->query("SELECT COUNT(*) FROM vehiculos WHERE estado = 'disponible'")->fetchColumn();
    $stats['asignados'] = $pdo->query("SELECT COUNT(*) FROM vehiculos WHERE estado = 'asignado'")->fetchColumn();
    $stats['mantenimiento'] = $pdo->query("SELECT COUNT(*) FROM vehiculos WHERE estado = 'mantenimiento'")->fetchColumn();
    
    $stats['total_empleados'] = $pdo->query("SELECT COUNT(*) FROM empleados WHERE activo = 1")->fetchColumn();
    $stats['alertas_activas'] = $pdo->query("SELECT COUNT(*) FROM alertas WHERE vista = 0")->fetchColumn();
    $stats['multas_pendientes'] = $pdo->query("SELECT COUNT(*) FROM multas WHERE pagada = 0")->fetchColumn();
    $stats['mantenimientos_programados'] = $pdo->query("SELECT COUNT(*) FROM vehiculos WHERE kilometraje_actual >= (km_proximo_service - 1000)")->fetchColumn();
    
    json_response(['success' => true, 'data' => $stats]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Error al obtener estadísticas: ' . $e->getMessage()], 500);
}
