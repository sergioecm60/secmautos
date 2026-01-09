<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!verificar_autenticacion()) {
    json_response(['success' => false, 'message' => 'No autenticado'], 401);
}

try {
    $stmt = $pdo->query("
        SELECT 
            patente, marca, modelo,
            fecha_vtv, fecha_seguro, fecha_patente, 
            DATEDIFF(fecha_vtv, CURDATE()) as dias_vtv,
            DATEDIFF(fecha_seguro, CURDATE()) as dias_seguro,
            DATEDIFF(fecha_patente, CURDATE()) as dias_patente
        FROM vehiculos
        WHERE (
            (fecha_vtv IS NOT NULL AND DATEDIFF(fecha_vtv, CURDATE()) BETWEEN 0 AND 15) OR
            (fecha_seguro IS NOT NULL AND DATEDIFF(fecha_seguro, CURDATE()) BETWEEN 0 AND 15) OR
            (fecha_patente IS NOT NULL AND DATEDIFF(fecha_patente, CURDATE()) BETWEEN 0 AND 15)
        ) AND estado != 'baja'
        ORDER BY 
            LEAST(
                COALESCE(DATEDIFF(fecha_vtv, CURDATE()), 999),
                COALESCE(DATEDIFF(fecha_seguro, CURDATE()), 999),
                COALESCE(DATEDIFF(fecha_patente, CURDATE()), 999)
            ) ASC
        LIMIT 20
    ");
    $vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $vencimientos = [];
    foreach ($vehiculos as $v) {
        if ($v['dias_vtv'] >= 0 && $v['dias_vtv'] <= 15) {
            $vencimientos[] = [
                'patente' => $v['patente'],
                'marca' => $v['marca'],
                'modelo' => $v['modelo'],
                'tipo_vencimiento' => 'VTV',
                'fecha_vencimiento' => $v['fecha_vtv'],
                'dias_restantes' => $v['dias_vtv']
            ];
        }
        if ($v['dias_seguro'] >= 0 && $v['dias_seguro'] <= 15) {
            $vencimientos[] = [
                'patente' => $v['patente'],
                'marca' => $v['marca'],
                'modelo' => $v['modelo'],
                'tipo_vencimiento' => 'Seguro',
                'fecha_vencimiento' => $v['fecha_seguro'],
                'dias_restantes' => $v['dias_seguro']
            ];
        }
        if ($v['dias_patente'] >= 0 && $v['dias_patente'] <= 15) {
            $vencimientos[] = [
                'patente' => $v['patente'],
                'marca' => $v['marca'],
                'modelo' => $v['modelo'],
                'tipo_vencimiento' => 'Patente',
                'fecha_vencimiento' => $v['fecha_patente'],
                'dias_restantes' => $v['dias_patente']
            ];
        }
    }
    
    json_response(['success' => true, 'data' => $vencimientos]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Error al obtener vencimientos: ' . $e->getMessage()], 500);
}
