<?php
/**
 * Script de Generación Automática de Alertas
 * Ejecutar diariamente vía cron job:
 * 0 6 * * * cd /var/www/secmautos && php scripts/generar_alertas.php >> logs/alertas.log 2>&1
 */

require_once __DIR__ . '/../bootstrap.php';

echo "[" . date('Y-m-d H:i:s') . "] Iniciando generación de alertas...\n";

try {
    // 1. Limpiar alertas resueltas antiguas (más de 30 días)
    $stmt = $pdo->exec("DELETE FROM alertas WHERE resuelta = 1 AND fecha_resolucion < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    echo "   - Alertas antiguas eliminadas: $stmt\n";

    // 2. VTV próximas a vencer (15 días antes)
    $stmt = $pdo->prepare("
        SELECT id, patente, fecha_vtv
        FROM vehiculos
        WHERE estado != 'baja'
        AND fecha_vtv IS NOT NULL
        AND fecha_vtv BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)
        AND id NOT IN (
            SELECT vehiculo_id
            FROM alertas
            WHERE tipo_alerta = 'vtv' AND resuelta = 0
        )
    ");
    $stmt->execute();
    $vehiculos_vtv = $stmt->fetchAll();

    foreach ($vehiculos_vtv as $v) {
        $dias = (strtotime($v['fecha_vtv']) - strtotime(date('Y-m-d'))) / 86400;
        $dias = floor($dias);
        $mensaje = "VTV vence en $dias días (el {$v['fecha_vtv']}) - Patente {$v['patente']}";

        $pdo->prepare("INSERT INTO alertas (vehiculo_id, tipo_alerta, mensaje, fecha_alerta) VALUES (?, 'vtv', ?, CURDATE())")
            ->execute([$v['id'], $mensaje]);

        echo "   - Alerta VTV creada para patente {$v['patente']}\n";
    }

    // 3. Seguro próximo a vencer (15 días antes)
    $stmt = $pdo->prepare("
        SELECT id, patente, fecha_seguro
        FROM vehiculos
        WHERE estado != 'baja'
        AND fecha_seguro IS NOT NULL
        AND fecha_seguro BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)
        AND id NOT IN (
            SELECT vehiculo_id
            FROM alertas
            WHERE tipo_alerta = 'seguro' AND resuelta = 0
        )
    ");
    $stmt->execute();
    $vehiculos_seguro = $stmt->fetchAll();

    foreach ($vehiculos_seguro as $v) {
        $dias = (strtotime($v['fecha_seguro']) - strtotime(date('Y-m-d'))) / 86400;
        $dias = floor($dias);
        $mensaje = "Seguro vence en $dias días (el {$v['fecha_seguro']}) - Patente {$v['patente']}";

        $pdo->prepare("INSERT INTO alertas (vehiculo_id, tipo_alerta, mensaje, fecha_alerta) VALUES (?, 'seguro', ?, CURDATE())")
            ->execute([$v['id'], $mensaje]);

        echo "   - Alerta SEGURO creada para patente {$v['patente']}\n";
    }

    // 4. Patente próxima a vencer (15 días antes)
    $stmt = $pdo->prepare("
        SELECT id, patente, fecha_patente
        FROM vehiculos
        WHERE estado != 'baja'
        AND fecha_patente IS NOT NULL
        AND fecha_patente BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)
        AND id NOT IN (
            SELECT vehiculo_id
            FROM alertas
            WHERE tipo_alerta = 'patente' AND resuelta = 0
        )
    ");
    $stmt->execute();
    $vehiculos_patente = $stmt->fetchAll();

    foreach ($vehiculos_patente as $v) {
        $dias = (strtotime($v['fecha_patente']) - strtotime(date('Y-m-d'))) / 86400;
        $dias = floor($dias);
        $mensaje = "Patente vence en $dias días (el {$v['fecha_patente']}) - Patente {$v['patente']}";

        $pdo->prepare("INSERT INTO alertas (vehiculo_id, tipo_alerta, mensaje, fecha_alerta) VALUES (?, 'patente', ?, CURDATE())")
            ->execute([$v['id'], $mensaje]);

        echo "   - Alerta PATENTE creada para patente {$v['patente']}\n";
    }

    // 5. CETA próxima a vencer (15 días antes)
    $stmt = $pdo->prepare("
        SELECT c.id, c.vehiculo_id, c.fecha_vencimiento, v.patente
        FROM ceta c
        JOIN vehiculos v ON c.vehiculo_id = v.id
        WHERE v.estado != 'baja'
        AND c.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)
        AND c.vehiculo_id NOT IN (
            SELECT vehiculo_id
            FROM alertas
            WHERE tipo_alerta = 'ceta' AND resuelta = 0
        )
    ");
    $stmt->execute();
    $cetas = $stmt->fetchAll();

    foreach ($cetas as $c) {
        $dias = (strtotime($c['fecha_vencimiento']) - strtotime(date('Y-m-d'))) / 86400;
        $dias = floor($dias);
        $mensaje = "CETA vence en $dias días (el {$c['fecha_vencimiento']}) - Patente {$c['patente']}";

        $pdo->prepare("INSERT INTO alertas (vehiculo_id, tipo_alerta, mensaje, fecha_alerta) VALUES (?, 'ceta', ?, CURDATE())")
            ->execute([$c['vehiculo_id'], $mensaje]);

        echo "   - Alerta CETA creada para patente {$c['patente']}\n";
    }

    // 6. Kilometraje próximo a service (1000 km antes)
    $stmt = $pdo->prepare("
        SELECT id, patente, kilometraje_actual, km_proximo_service
        FROM vehiculos
        WHERE estado != 'baja'
        AND km_proximo_service > 0
        AND kilometraje_actual >= (km_proximo_service - 1000)
        AND id NOT IN (
            SELECT vehiculo_id
            FROM alertas
            WHERE tipo_alerta = 'km' AND resuelta = 0
        )
    ");
    $stmt->execute();
    $vehiculos_km = $stmt->fetchAll();

    foreach ($vehiculos_km as $v) {
        $km_faltantes = $v['km_proximo_service'] - $v['kilometraje_actual'];
        $mensaje = "Vehículo próximo a service. Faltan $km_faltantes km (Actual: {$v['kilometraje_actual']}, Service: {$v['km_proximo_service']}) - Patente {$v['patente']}";

        $pdo->prepare("INSERT INTO alertas (vehiculo_id, tipo_alerta, mensaje, fecha_alerta) VALUES (?, 'km', ?, CURDATE())")
            ->execute([$v['id'], $mensaje]);

        echo "   - Alerta KM creada para patente {$v['patente']}\n";
    }

    // 7. Multas sin pagar (más de 30 días)
    $stmt = $pdo->prepare("
        SELECT m.id, m.vehiculo_id, m.fecha_multa, m.monto, v.patente,
               CONCAT(e.nombre, ' ', e.apellido) as empleado
        FROM multas m
        JOIN vehiculos v ON m.vehiculo_id = v.id
        JOIN empleados e ON m.empleado_id = e.id
        WHERE m.pagada = 0
        AND m.fecha_multa < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        AND m.id NOT IN (
            SELECT CAST(SUBSTRING_INDEX(mensaje, 'ID: ', -1) AS UNSIGNED)
            FROM alertas
            WHERE tipo_alerta = 'multa' AND resuelta = 0 AND mensaje LIKE '%Multa sin pagar%'
        )
    ");
    $stmt->execute();
    $multas = $stmt->fetchAll();

    foreach ($multas as $m) {
        $dias = floor((strtotime(date('Y-m-d')) - strtotime($m['fecha_multa'])) / 86400);
        $mensaje = "Multa sin pagar hace $dias días. Monto: \${$m['monto']} - Patente {$m['patente']} - Responsable: {$m['empleado']} (ID: {$m['id']})";

        $pdo->prepare("INSERT INTO alertas (vehiculo_id, tipo_alerta, mensaje, fecha_alerta) VALUES (?, 'multa', ?, CURDATE())")
            ->execute([$m['vehiculo_id'], $mensaje]);

        echo "   - Alerta MULTA creada para patente {$m['patente']}\n";
    }

    echo "[" . date('Y-m-d H:i:s') . "] Generación de alertas completada exitosamente.\n\n";

} catch (Exception $e) {
    echo "[ERROR] " . date('Y-m-d H:i:s') . " - " . $e->getMessage() . "\n\n";
    exit(1);
}
