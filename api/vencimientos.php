<?php
require_once __DIR__ . '/../bootstrap.php';

// Desactivar output buffering para debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // Obtener vencimientos de vehículos (VTV, Seguro, Patente)
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

    // Obtener pagos pendientes de patente
    $stmtPagos = $pdo->query("
        SELECT p.id, v.patente, p.tipo, p.fecha_vencimiento,
               DATEDIFF(p.fecha_vencimiento, CURDATE()) as dias_restantes
        FROM pagos p
        INNER JOIN vehiculos v ON p.vehiculo_id = v.id
        WHERE p.tipo = 'patente'
          AND p.pagado = 0
          AND p.fecha_vencimiento > CURDATE()
          AND v.estado != 'baja'
        ORDER BY p.fecha_vencimiento ASC
        LIMIT 20
    ");
    $pagosPendientes = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

    $vencimientos = [];

    // Agregar vencimientos de vehículos
    foreach ($vehiculos as $v) {
        if ($v['dias_vtv'] >= 0 && $v['dias_vtv'] <= 15) {
            $vencimientos[] = [
                'patente' => $v['patente'],
                'marca' => $v['marca'],
                'modelo' => $v['modelo'],
                'tipo_vencimiento' => 'VTV',
                'fecha_vencimiento' => $v['fecha_vtv'],
                'dias_restantes' => $v['dias_vtv'],
                'pago_id' => null
            ];
        }
        if ($v['dias_seguro'] >= 0 && $v['dias_seguro'] <= 15) {
            $vencimientos[] = [
                'patente' => $v['patente'],
                'marca' => $v['marca'],
                'modelo' => $v['modelo'],
                'tipo_vencimiento' => 'Seguro',
                'fecha_vencimiento' => $v['fecha_seguro'],
                'dias_restantes' => $v['dias_seguro'],
                'pago_id' => null
            ];
        }
        if ($v['dias_patente'] >= 0 && $v['dias_patente'] <= 15) {
            $vencimientos[] = [
                'patente' => $v['patente'],
                'marca' => $v['marca'],
                'modelo' => $v['modelo'],
                'tipo_vencimiento' => 'Patente',
                'fecha_vencimiento' => $v['fecha_patente'],
                'dias_restantes' => $v['dias_patente'],
                'pago_id' => null
            ];
        }
    }

    // Agregar pagos pendientes de patente
    foreach ($pagosPendientes as $p) {
        $vencimientos[] = [
            'patente' => $p['patente'],
            'marca' => '',
            'modelo' => '',
            'tipo_vencimiento' => 'Patente',
            'fecha_vencimiento' => $p['fecha_vencimiento'],
            'dias_restantes' => $p['dias_restantes'],
            'pago_id' => $p['id']
        ];
    }

    // Ordenar por días restantes
    usort($vencimientos, function($a, $b) {
        return $a['dias_restantes'] - $b['dias_restantes'];
    });

    json_response(['success' => true, 'data' => $vencimientos]);
} catch (Exception $e) {
    error_log("Error en vencimientos.php: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'Error al obtener vencimientos: ' . $e->getMessage()], 500);
}
