<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!verificar_autenticacion()) {
    json_response(['success' => false, 'message' => 'No autenticado'], 401);
}

try {
    $stats = [];

    // Estadísticas de vehículos
    $stats['total_vehiculos'] = $pdo->query("SELECT COUNT(*) FROM vehiculos")->fetchColumn();
    $stats['disponibles'] = $pdo->query("SELECT COUNT(*) FROM vehiculos WHERE estado = 'disponible'")->fetchColumn();
    $stats['asignados'] = $pdo->query("SELECT COUNT(*) FROM vehiculos WHERE estado = 'asignado'")->fetchColumn();
    $stats['mantenimiento'] = $pdo->query("SELECT COUNT(*) FROM vehiculos WHERE estado = 'mantenimiento'")->fetchColumn();

    // Estadísticas generales
    $stats['total_empleados'] = $pdo->query("SELECT COUNT(*) FROM empleados WHERE activo = 1")->fetchColumn();
    $stats['alertas_activas'] = $pdo->query("SELECT COUNT(*) FROM alertas WHERE vista = 0")->fetchColumn();
    $stats['multas_pendientes'] = $pdo->query("SELECT COUNT(*) FROM multas WHERE pagada = 0")->fetchColumn();
    $stats['mantenimientos_programados'] = $pdo->query("SELECT COUNT(*) FROM vehiculos WHERE kilometraje_actual >= (km_proximo_service - 1000)")->fetchColumn();

    // Estadísticas de pagos por tipo (NUEVO)
    $pagos_stats = $pdo->query("
        SELECT
            tipo,
            COUNT(*) as cantidad,
            COALESCE(SUM(monto), 0) as monto_total
        FROM pagos
        WHERE pagado = 0
        GROUP BY tipo
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Inicializar contadores de pagos
    $stats['pagos_patente_pendientes'] = 0;
    $stats['pagos_patente_monto'] = 0;
    $stats['pagos_seguro_pendientes'] = 0;
    $stats['pagos_seguro_monto'] = 0;
    $stats['pagos_vtv_pendientes'] = 0;
    $stats['pagos_vtv_monto'] = 0;
    $stats['pagos_multa_pendientes'] = 0;
    $stats['pagos_multa_monto'] = 0;
    $stats['pagos_servicios_pendientes'] = 0;
    $stats['pagos_servicios_monto'] = 0;
    $stats['pagos_otro_pendientes'] = 0;
    $stats['pagos_otro_monto'] = 0;

    // Llenar con datos reales
    foreach ($pagos_stats as $pago) {
        switch ($pago['tipo']) {
            case 'patente':
                $stats['pagos_patente_pendientes'] = (int)$pago['cantidad'];
                $stats['pagos_patente_monto'] = (float)$pago['monto_total'];
                break;
            case 'seguro':
                $stats['pagos_seguro_pendientes'] = (int)$pago['cantidad'];
                $stats['pagos_seguro_monto'] = (float)$pago['monto_total'];
                break;
            case 'vtv':
                $stats['pagos_vtv_pendientes'] = (int)$pago['cantidad'];
                $stats['pagos_vtv_monto'] = (float)$pago['monto_total'];
                break;
            case 'multa':
                $stats['pagos_multa_pendientes'] = (int)$pago['cantidad'];
                $stats['pagos_multa_monto'] = (float)$pago['monto_total'];
                break;
            case 'servicios':
                $stats['pagos_servicios_pendientes'] = (int)$pago['cantidad'];
                $stats['pagos_servicios_monto'] = (float)$pago['monto_total'];
                break;
            case 'otro':
                $stats['pagos_otro_pendientes'] = (int)$pago['cantidad'];
                $stats['pagos_otro_monto'] = (float)$pago['monto_total'];
                break;
        }
    }

    // Estadísticas de telepases
    $stats['telepases_pendientes'] = $pdo->query("SELECT COUNT(*) FROM pagos_telepase WHERE estado = 'pendiente'")->fetchColumn();
    $stats['telepases_monto'] = $pdo->query("SELECT COALESCE(SUM(monto), 0) FROM pagos_telepase WHERE estado = 'pendiente'")->fetchColumn();

    // Total de pagos pendientes (todos los tipos)
    $stats['total_pagos_pendientes'] = array_sum([
        $stats['pagos_patente_pendientes'],
        $stats['pagos_seguro_pendientes'],
        $stats['pagos_vtv_pendientes'],
        $stats['pagos_multa_pendientes'],
        $stats['pagos_servicios_pendientes'],
        $stats['pagos_otro_pendientes'],
        $stats['telepases_pendientes']
    ]);

    $stats['total_monto_pendiente'] = array_sum([
        $stats['pagos_patente_monto'],
        $stats['pagos_seguro_monto'],
        $stats['pagos_vtv_monto'],
        $stats['pagos_multa_monto'],
        $stats['pagos_servicios_monto'],
        $stats['pagos_otro_monto'],
        $stats['telepases_monto']
    ]);

    json_response(['success' => true, 'data' => $stats]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Error al obtener estadísticas: ' . $e->getMessage()], 500);
}
