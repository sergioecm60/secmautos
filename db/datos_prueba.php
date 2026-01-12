<?php
/**
 * Script para cargar datos de prueba
 * Ejecutar desde la terminal: php db/datos_prueba.php
 */

require_once __DIR__ . '/../bootstrap.php';

echo "=== CARGANDO DATOS DE PRUEBA ===\n";

try {
    $pdo->beginTransaction();

    // === INSERTAR VEHÍCULOS ===
    echo "Insertando vehículos...\n";

    // Vehículo 1 - Fiat Fiórino
    $stmt = $pdo->prepare("INSERT INTO vehiculos (patente, marca, modelo, anio, motor, chasis, titularidad, kilometraje_actual, estado, fecha_vtv, fecha_seguro, fecha_patente) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        'AA123BB',
        'Fiat',
        'Fiorino',
        '2020',
        '1.4L FIRE8V',
        '8AF123456789',
        'Secretaría de Educación',
        45000,
        'disponible',
        '2025-06-15',
        '2025-03-20',
        '2025-01-10'
    ]);
    echo "✓ Vehículo 1: AA123BB (Fiat Fiórino 2020)\n";

    // Vehículo 2 - Peugeot Partner
    $stmt = $pdo->prepare("INSERT INTO vehiculos (patente, marca, modelo, anio, motor, chasis, titularidad, kilometraje_actual, estado, fecha_vtv, fecha_seguro, fecha_patente) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        'CC456CD',
        'Peugeot',
        'Partner',
        '2019',
        '1.6L HDI 90 CV',
        'VR7BJ65432109876',
        'Secretaría de Educación',
        '62000',
        'disponible',
        '2025-07-22',
        '2025-02-15',
        '2025-01-25'
    ]);
    echo "✓ Vehículo 2: CC456CD (Peugeot Partner 2019)\n";

    // Vehículo 3 - Renault Kangoo
    $stmt = $pdo->prepare("INSERT INTO vehiculos (patente, marca, modelo, anio, motor, chasis, titularidad, kilometraje_actual, estado, fecha_vtv, fecha_seguro, fecha_patente) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        'EE789EF',
        'Renault',
        'Kangoo',
        '2021',
        '1.2L TCE 100',
        'VF1AK345678901234',
        'Secretaría de Educación',
        '38000',
        'disponible',
        '2025-08-10',
        '2025-04-30',
        '2025-03-05'
    ]);
    echo "✓ Vehículo 3: EE789EF (Renault Kangoo 2021)\n";

    // === INSERTAR EMPLEADOS ===
    echo "\nInsertando empleados...\n";

    // Empleado 1 - Juan Pérez
    $stmt = $pdo->prepare("INSERT INTO empleados (nombre, apellido, dni, email, telefono, direccion, activo) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        'Juan',
        'Pérez',
        '12345678',
        'juan.perez@secmautos.com',
        '+54 11 1234-5678',
        'Av. Libertador 1234, CABA',
        1
    ]);
    echo "✓ Empleado 1: Juan Pérez (12345678)\n";

    // Empleado 2 - María García
    $stmt = $pdo->prepare("INSERT INTO empleados (nombre, apellido, dni, email, telefono, direccion, activo) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        'María',
        'García',
        '87654321',
        'maria.garcia@secmautos.com',
        '+54 11 9876-5432',
        'Calle Belgrano 5678, CABA',
        1
    ]);
    echo "✓ Empleado 2: María García (87654321)\n";

    // Empleado 3 - Carlos Rodríguez
    $stmt = $pdo->prepare("INSERT INTO empleados (nombre, apellido, dni, email, telefono, direccion, activo) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        'Carlos',
        'Rodríguez',
        '34567890',
        'carlos.rodriguez@secmautos.com',
        '+54 11 3456-7890',
        'Av. Corrientes 2345, CABA',
        1
    ]);
    echo "✓ Empleado 3: Carlos Rodríguez (34567890)\n";

    // === OBTENER IDS ===
    echo "\nObteniendo IDs de vehículos y empleados...\n";

    $vehiculo_ids = [];
    $stmt = $pdo->query("SELECT id, patente FROM vehiculos ORDER BY id DESC LIMIT 3");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $vehiculo_ids[] = $row['id'];
        echo "Vehículo ID {$row['id']}: {$row['patente']}\n";
    }

    $empleado_ids = [];
    $stmt = $pdo->query("SELECT id, nombre, apellido FROM empleados ORDER BY id DESC LIMIT 3");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $empleado_ids[] = $row['id'];
        echo "Empleado ID {$row['id']}: {$row['nombre']} {$row['apellido']}\n";
    }

    // === CREAR ASIGNACIONES ===
    echo "\nCreando asignaciones activas...\n";

    // Asignación 1 - Vehículo 1 → Empleado 1
    $stmt = $pdo->prepare("INSERT INTO asignaciones (vehiculo_id, empleado_id, km_salida, observaciones, fecha_asignacion) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $vehiculo_ids[0],
        $empleado_ids[0],
        45000,
        'Asignación inicial para pruebas',
        date('Y-m-d H:i:s')
    ]);
    echo "✓ Asignación 1: Vehículo {$vehiculo_ids[0]} → Empleado {$empleado_ids[0]} (45000 km)\n";

    // Asignación 2 - Vehículo 2 → Empleado 2
    $stmt = $pdo->prepare("INSERT INTO asignaciones (vehiculo_id, empleado_id, km_salida, observaciones, fecha_asignacion) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $vehiculo_ids[1],
        $empleado_ids[1],
        62000,
        'Asignación inicial para pruebas',
        date('Y-m-d H:i:s')
    ]);
    echo "✓ Asignación 2: Vehículo {$vehiculo_ids[1]} → Empleado {$empleado_ids[1]} (62000 km)\n";

    // Asignación 3 - Vehículo 3 → Empleado 3
    $stmt = $pdo->prepare("INSERT INTO asignaciones (vehiculo_id, empleado_id, km_salida, observaciones, fecha_asignacion) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $vehiculo_ids[2],
        $empleado_ids[2],
        38000,
        'Asignación inicial para pruebas',
        date('Y-m-d H:i:s')
    ]);
    echo "✓ Asignación 3: Vehículo {$vehiculo_ids[2]} → Empleado {$empleado_ids[2]} (38000 km)\n";

    // === CREAR PAGOS DE PRUEBA ===
    echo "\nCreando pagos de prueba...\n";

    // Pago 1 - Patente Vehículo 1 (30 días desde hoy)
    $stmt = $pdo->prepare("INSERT INTO pagos (vehiculo_id, tipo, fecha_vencimiento, monto, pagado) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $vehiculo_ids[0],
        'patente',
        date('Y-m-d', strtotime('+30 days')),
        15000.00,
        0
    ]);
    echo "✓ Pago 1: Patente de vehículo {$vehiculo_ids[0]} (vence en 30 días) - $150.00 ARS - Pendiente)\n";

    // Pago 2 - Seguro Vehículo 1 (15 días desde hoy)
    $stmt = $pdo->prepare("INSERT INTO pagos (vehiculo_id, tipo, fecha_vencimiento, monto, pagado) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $vehiculo_ids[0],
        'seguro',
        date('Y-m-d', strtotime('+15 days')),
        25000.00,
        0
    ]);
    echo "✓ Pago 2: Seguro de vehículo {$vehiculo_ids[0]} (vence en 15 días) - $250.00 ARS - Pendiente)\n";

    // Pago 3 - Patente Vehículo 2 (60 días desde hoy)
    $stmt = $pdo->prepare("INSERT INTO pagos (vehiculo_id, tipo, fecha_vencimiento, monto, pagado) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $vehiculo_ids[1],
        'patente',
        date('Y-m-d', strtotime('+60 days')),
        15000.00,
        0
    ]);
    echo "✓ Pago 3: Patente de vehículo {$vehiculo_ids[1]} (vence en 60 días) - $150.00 ARS - Pendiente)\n";

    // === CREAR MANTENIMIENTO ===
    echo "\nCreando mantenimiento de prueba...\n";

    // Mantenimiento preventivo para Vehículo 1
    $stmt = $pdo->prepare("INSERT INTO mantenimientos (vehiculo_id, tipo, fecha, descripcion, costo, kilometraje) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $vehiculo_ids[0],
        'preventivo',
        date('Y-m-d'),
        'Cambio de aceite y filtros',
        5000.00,
        45000
    ]);
    echo "✓ Mantenimiento: Preventivo Vehículo {$vehiculo_ids[0]} - $5000 ARS\n";

    // === CREAR COMPRA ===
    echo "\nCreando compra de prueba...\n";

    // Compra de Vehículo 3 (hace 2 años)
    $stmt = $pdo->prepare("INSERT INTO compras (vehiculo_id, fecha, proveedor, cuit, factura_numero, importe_neto, iva, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $vehiculo_ids[2],
        date('Y-m-d', strtotime('-2 years')),
        'Concesionario Fiat',
        '20-123456789',
        'A-001234',
        450000.00,
        94500.00,
        544500.00
    ]);
    echo "✓ Compra: Vehículo 3 - Concesionario Fiat (hace 2 años)\n";

    // === CREAR USUARIO DE PRUEBA ===
    echo "\nCreando usuario de prueba para testing...\n";

    $stmt = $pdo->prepare("INSERT INTO usuarios (username, password_hash, rol, activo, nombre, apellido, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        'prueba',
        password_hash('prueba123', PASSWORD_DEFAULT),
        'admin',
        1,
        'Usuario',
        'Prueba',
        'usuario.prueba@secmautos.com'
    ]);
    echo "✓ Usuario: prueba (Contraseña: prueba123)\n";

    $pdo->commit();

    echo "\n=== ✅ DATOS DE PRUEBA CARGADOS EXITOSAMENTE ===\n";
    echo "\nResumen:\n";
    echo "- 3 vehículos insertados\n";
    echo "- 3 empleados insertados\n";
    echo "- 3 asignaciones activas creadas\n";
    echo "- 3 pagos pendientes creados\n";
    echo "- 1 mantenimiento registrado\n";
    echo "- 1 compra registrada\n";
    echo "- 1 usuario de prueba (prueba/prueba123)\n";
    echo "\n💡 Puedes hacer login con:\n";
    echo "   Usuario: prueba\n";
    echo "   Contraseña: prueba123\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
