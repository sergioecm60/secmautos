<?php
/**
 * Script para generar alertas automáticas de vencimientos
 * Se puede ejecutar vía cron diariamente
 * Uso: php scripts/generar_alertas.php
 */

require_once __DIR__ . '/../bootstrap.php';

echo "=== Generador de Alertas Automáticas ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $pdo->beginTransaction();
    
    $alertas_creadas = 0;
    $fecha_hoy = date('Y-m-d');
    
    // 1. Alertas de PAGOS próximos a vencer (próximos 30 días)
    echo "Verificando pagos próximos a vencer...\n";
    $stmt = $pdo->prepare("
        SELECT p.*, v.patente, v.marca, v.modelo
        FROM pagos p
        INNER JOIN vehiculos v ON p.vehiculo_id = v.id
        WHERE p.pagado = 0
          AND p.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
          AND NOT EXISTS (
              SELECT 1 FROM alertas a
              WHERE a.vehiculo_id = p.vehiculo_id
                AND a.tipo_alerta = p.tipo
                AND a.fecha_alerta = p.fecha_vencimiento
                AND a.resuelta = 0
          )
    ");
    $stmt->execute();
    $pagos_vencer = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($pagos_vencer as $pago) {
        $dias_restantes = (strtotime($pago['fecha_vencimiento']) - strtotime($fecha_hoy)) / 86400;
        $dias_restantes = (int)$dias_restantes;
        
        $tipo_label = [
            'patente' => 'Patente (Impuesto Automotor)',
            'seguro' => 'Seguro',
            'vtv' => 'VTV',
            'multa' => 'Multa',
            'servicios' => 'Servicio',
            'otro' => 'Otro pago'
        ][$pago['tipo']] ?? 'Pago';
        
        $urgencia = $dias_restantes <= 7 ? '🔴 URGENTE' : ($dias_restantes <= 15 ? '🟠 Próximo' : '🟡 Recordatorio');
        
        $mensaje = "{$urgencia}: {$tipo_label} vence en {$dias_restantes} días (Vehículo: {$pago['patente']} - {$pago['marca']} {$pago['modelo']})";
        
        if (!empty($pago['monto'])) {
            $mensaje .= " - Monto: $" . number_format($pago['monto'], 2);
        }
        
        $stmt_insert = $pdo->prepare("
            INSERT INTO alertas (vehiculo_id, tipo_alerta, mensaje, fecha_alerta, vista, resuelta)
            VALUES (?, ?, ?, ?, 0, 0)
        ");
        $stmt_insert->execute([
            $pago['vehiculo_id'],
            $pago['tipo'],
            $mensaje,
            $pago['fecha_vencimiento']
        ]);
        
        $alertas_creadas++;
        echo "  ✓ Alerta creada: {$tipo_label} - {$pago['patente']} (vence en {$dias_restantes} días)\n";
    }
    
    // 2. Alertas de TELEPASES próximos a vencer
    echo "\nVerificando telepases próximos a vencer...\n";
    $stmt = $pdo->prepare("
        SELECT pt.*, t.numero_dispositivo, v.patente, v.marca, v.modelo
        FROM pagos_telepase pt
        INNER JOIN telepases t ON pt.telepase_id = t.id
        INNER JOIN vehiculos v ON pt.vehiculo_id = v.id
        WHERE pt.estado = 'pendiente'
          AND pt.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
          AND NOT EXISTS (
              SELECT 1 FROM alertas a
              WHERE a.vehiculo_id = pt.vehiculo_id
                AND a.tipo_alerta = 'telepase'
                AND a.fecha_alerta = pt.fecha_vencimiento
                AND a.resuelta = 0
          )
    ");
    $stmt->execute();
    $telepases_vencer = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($telepases_vencer as $tp) {
        $dias_restantes = (strtotime($tp['fecha_vencimiento']) - strtotime($fecha_hoy)) / 86400;
        $dias_restantes = (int)$dias_restantes;
        
        $urgencia = $dias_restantes <= 7 ? '🔴 URGENTE' : ($dias_restantes <= 15 ? '🟠 Próximo' : '🟡 Recordatorio');
        
        $mensaje = "{$urgencia}: Telepase {$tp['concesionario']} vence en {$dias_restantes} días (Vehículo: {$tp['patente']} - Dispositivo: {$tp['numero_dispositivo']}) - Monto: $" . number_format($tp['monto'], 2);
        
        $stmt_insert = $pdo->prepare("
            INSERT INTO alertas (vehiculo_id, tipo_alerta, mensaje, fecha_alerta, vista, resuelta)
            VALUES (?, 'telepase', ?, ?, 0, 0)
        ");
        $stmt_insert->execute([
            $tp['vehiculo_id'],
            $mensaje,
            $tp['fecha_vencimiento']
        ]);
        
        $alertas_creadas++;
        echo "  ✓ Alerta creada: Telepase - {$tp['patente']} (vence en {$dias_restantes} días)\n";
    }
    
    // 3. Alertas de MANTENIMIENTOS programados próximos por kilometraje
    echo "\nVerificando mantenimientos por kilometraje...\n";
    $stmt = $pdo->query("
        SELECT v.id, v.patente, v.marca, v.modelo, v.kilometraje_actual, v.km_proximo_service
        FROM vehiculos v
        WHERE v.estado != 'baja'
          AND v.km_proximo_service IS NOT NULL
          AND v.kilometraje_actual >= (v.km_proximo_service - 1000)
          AND NOT EXISTS (
              SELECT 1 FROM alertas a
              WHERE a.vehiculo_id = v.id
                AND a.tipo_alerta = 'km'
                AND a.resuelta = 0
          )
    ");
    $vehiculos_km = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($vehiculos_km as $v) {
        $km_restantes = $v['km_proximo_service'] - $v['kilometraje_actual'];
        $urgencia = $km_restantes <= 200 ? '🔴 URGENTE' : '🟠 Próximo';
        
        $mensaje = "{$urgencia}: Mantenimiento próximo - {$v['patente']} ({$v['marca']} {$v['modelo']}) - Actual: {$v['kilometraje_actual']} km, Próximo service: {$v['km_proximo_service']} km (Faltan {$km_restantes} km)";
        
        $stmt_insert = $pdo->prepare("
            INSERT INTO alertas (vehiculo_id, tipo_alerta, mensaje, fecha_alerta, vista, resuelta)
            VALUES (?, 'km', ?, CURDATE(), 0, 0)
        ");
        $stmt_insert->execute([$v['id'], $mensaje]);
        
        $alertas_creadas++;
        echo "  ✓ Alerta creada: Mantenimiento KM - {$v['patente']} (faltan {$km_restantes} km)\n";
    }
    
    // 4. Limpiar alertas antiguas resueltas (más de 90 días)
    echo "\nLimpiando alertas antiguas...\n";
    $stmt = $pdo->prepare("
        DELETE FROM alertas
        WHERE resuelta = 1
          AND created_at < DATE_SUB(CURDATE(), INTERVAL 90 DAY)
    ");
    $stmt->execute();
    $eliminadas = $stmt->rowCount();
    echo "  ✓ Alertas antiguas eliminadas: {$eliminadas}\n";
    
    $pdo->commit();
    
    echo "\n=== Resumen ===\n";
    echo "Total de alertas creadas: {$alertas_creadas}\n";
    echo "Alertas antiguas eliminadas: {$eliminadas}\n";
    echo "Proceso completado exitosamente.\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    error_log("Error en generar_alertas.php: " . $e->getMessage());
    exit(1);
}
