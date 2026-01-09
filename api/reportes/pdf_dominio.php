<?php
require_once __DIR__ . '/../bootstrap.php';

if (!verificar_autenticacion()) {
    header('HTTP/1.0 401 Unauthorized');
    exit;
}

header('Content-Type: text/html; charset=utf-8');

if (!isset($_GET['vehiculo_id'])) {
    echo '<p style="font-family: Arial, sans-serif;">Vehículo no especificado</p>';
    exit;
}

$vehiculo_id = (int)$_GET['vehiculo_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM vehiculos WHERE id = ?");
    $stmt->execute([$vehiculo_id]);
    $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vehiculo) {
        echo '<p style="font-family: Arial, sans-serif;">Vehículo no encontrado</p>';
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT a.*, CONCAT(e.nombre, ' ', e.apellido) as empleado_nombre
        FROM asignaciones a
        LEFT JOIN empleados e ON a.empleado_id = e.id
        WHERE a.vehiculo_id = ?
        ORDER BY a.fecha_asignacion DESC
    ");
    $stmt->execute([$vehiculo_id]);
    $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT m.*, CONCAT(e.nombre, ' ', e.apellido) as empleado_nombre
        FROM multas m
        LEFT JOIN empleados e ON m.empleado_id = e.id
        WHERE m.vehiculo_id = ?
        ORDER BY m.fecha_multa DESC
    ");
    $stmt->execute([$vehiculo_id]);
    $multas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM mantenimientos WHERE vehiculo_id = ? ORDER BY fecha DESC");
    $stmt->execute([$vehiculo_id]);
    $mantenimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT * FROM pagos
        WHERE vehiculo_id = ?
        ORDER BY fecha_vencimiento DESC
    ");
    $stmt->execute([$vehiculo_id]);
    $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM compras WHERE vehiculo_id = ? ORDER BY fecha DESC LIMIT 1");
    $stmt->execute([$vehiculo_id]);
    $compra = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM ventas WHERE vehiculo_id = ? ORDER BY fecha DESC LIMIT 1");
    $stmt->execute([$vehiculo_id]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);

    $totalMultas = array_sum(array_column($multas, 'monto'));
    $totalMantenimientos = array_sum(array_column($mantenimientos, 'costo'));

} catch (Exception $e) {
    echo '<p style="font-family: Arial, sans-serif;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}

function formatDate($date) {
    return $date ? date('d/m/Y', strtotime($date)) : '-';
}

function formatMoney($amount) {
    return $amount ? '$' . number_format($amount, 2) : '-';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de Dominio - <?= $vehiculo['patente'] ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .info-item {
            padding: 5px;
            background: #f5f5f5;
        }
        .info-item strong {
            display: block;
            color: #666;
        }
        section {
            margin-bottom: 20px;
        }
        h2 {
            background: #333;
            color: white;
            padding: 5px 10px;
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: left;
        }
        th {
            background: #f5f5f5;
            font-weight: bold;
        }
        .total {
            text-align: right;
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
        }
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #333;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }
        @media print {
            .print-btn { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>

    <div class="header">
        <h1>📋 INFORME DE DOMINIO COMPLETO</h1>
        <p>Fecha de emisión: <?= date('d/m/Y H:i') ?></p>
    </div>

    <div class="info-grid">
        <div class="info-item"><strong>Patente:</strong> <?= $vehiculo['patente'] ?></div>
        <div class="info-item"><strong>Marca/Modelo:</strong> <?= $vehiculo['marca'] . ' ' . $vehiculo['modelo'] ?></div>
        <div class="info-item"><strong>Año:</strong> <?= $vehiculo['anio'] ?></div>
        <div class="info-item"><strong>Motor:</strong> <?= $vehiculo['motor'] ?></div>
        <div class="info-item"><strong>Chasis:</strong> <?= $vehiculo['chasis'] ?></div>
        <div class="info-item"><strong>Titularidad:</strong> <?= $vehiculo['titularidad'] ?></div>
        <div class="info-item"><strong>Kilometraje:</strong> <?= number_format($vehiculo['kilometraje_actual']) . ' km' ?></div>
        <div class="info-item"><strong>Estado:</strong> <?= ucfirst($vehiculo['estado']) ?></div>
    </div>

    <section>
        <h2>📅 Vencimientos</h2>
        <table>
            <tr><th>VTV</th><td><?= formatDate($vehiculo['fecha_vtv']) ?></td></tr>
            <tr><th>Seguro</th><td><?= formatDate($vehiculo['fecha_seguro']) ?></td></tr>
            <tr><th>Patente</th><td><?= formatDate($vehiculo['fecha_patente']) ?></td></tr>
        </table>
    </section>

    <?php if ($compra): ?>
    <section>
        <h2>🛒 Compra</h2>
        <table>
            <tr><th>Fecha:</th><td><?= formatDate($compra['fecha']) ?></td></tr>
            <tr><th>Proveedor:</th><td><?= $compra['proveedor'] ?></td></tr>
            <tr><th>CUIT:</th><td><?= $compra['proveedor_cuit'] ?></td></tr>
            <tr><th>Factura Nº:</th><td><?= $compra['factura_numero'] ?></td></tr>
            <tr><th>Neto:</th><td><?= formatMoney($compra['neto']) ?></td></tr>
            <tr><th>IVA:</th><td><?= formatMoney($compra['iva']) ?></td></tr>
            <tr><th><strong>Total:</strong></th><td><strong><?= formatMoney($compra['total']) ?></strong></td></tr>
        </table>
    </section>
    <?php endif; ?>

    <?php if ($venta): ?>
    <section>
        <h2>💵 Venta</h2>
        <table>
            <tr><th>Fecha:</th><td><?= formatDate($venta['fecha']) ?></td></tr>
            <tr><th>Comprador:</th><td><?= $venta['comprador'] ?></td></tr>
            <tr><th>CUIT:</th><td><?= $venta['comprador_cuit'] ?></td></tr>
            <tr><th>Factura Nº:</th><td><?= $venta['factura_numero'] ?></td></tr>
            <tr><th><strong>Importe:</strong></th><td><strong><?= formatMoney($venta['importe']) ?></strong></td></tr>
        </table>
    </section>
    <?php endif; ?>

    <section>
        <h2>🔄 Historial de Asignaciones</h2>
        <?php if (count($asignaciones) > 0): ?>
        <table>
            <tr><th>Empleado</th><th>Fecha Salida</th><th>Km Salida</th><th>Fecha Regreso</th><th>Km Regreso</th></tr>
            <?php foreach ($asignaciones as $a): ?>
            <tr>
                <td><?= $a['empleado_nombre'] ?></td>
                <td><?= formatDate($a['fecha_asignacion']) ?></td>
                <td><?= number_format($a['km_salida']) ?></td>
                <td><?= formatDate($a['fecha_devolucion']) ?></td>
                <td><?= number_format($a['km_regreso']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
        <p>Sin asignaciones registradas</p>
        <?php endif; ?>
    </section>

    <section>
        <h2>⚠️ Multas</h2>
        <?php if (count($multas) > 0): ?>
        <table>
            <tr><th>Fecha</th><th>Motivo</th><th>Acta Nº</th><th>Responsable</th><th>Monto</th><th>Estado</th></tr>
            <?php foreach ($multas as $m): ?>
            <tr>
                <td><?= formatDate($m['fecha_multa']) ?></td>
                <td><?= $m['motivo'] ?></td>
                <td><?= $m['acta_numero'] ?></td>
                <td><?= $m['empleado_nombre'] ?></td>
                <td><?= formatMoney($m['monto']) ?></td>
                <td><?= $m['pagada'] ? 'Pagada' : 'Pendiente' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <div class="total">Total Multas: <?= formatMoney($totalMultas) ?></div>
        <?php else: ?>
        <p>Sin multas registradas</p>
        <?php endif; ?>
    </section>

    <section>
        <h2>🔧 Mantenimientos</h2>
        <?php if (count($mantenimientos) > 0): ?>
        <table>
            <tr><th>Fecha</th><th>Tipo</th><th>Descripción</th><th>Km</th><th>Costo</th></tr>
            <?php foreach ($mantenimientos as $m): ?>
            <tr>
                <td><?= formatDate($m['fecha']) ?></td>
                <td><?= ucfirst($m['tipo']) ?></td>
                <td><?= $m['descripcion'] ?></td>
                <td><?= $m['kilometraje'] ? number_format($m['kilometraje']) . ' km' : '-' ?></td>
                <td><?= formatMoney($m['costo']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <div class="total">Total Mantenimientos: <?= formatMoney($totalMantenimientos) ?></div>
        <?php else: ?>
        <p>Sin mantenimientos registrados</p>
        <?php endif; ?>
    </section>

    <section>
        <h2>💳 Pagos</h2>
        <?php if (count($pagos) > 0): ?>
        <table>
            <tr><th>Tipo</th><th>Vencimiento</th><th>Monto</th><th>Fecha Pago</th><th>Estado</th></tr>
            <?php foreach ($pagos as $p): ?>
            <tr>
                <td><?= ucfirst($p['tipo']) ?></td>
                <td><?= formatDate($p['fecha_vencimiento']) ?></td>
                <td><?= formatMoney($p['monto']) ?></td>
                <td><?= formatDate($p['fecha_pago']) ?></td>
                <td><?= $p['pagado'] ? 'Pagado' : 'Pendiente' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
        <p>Sin pagos registrados</p>
        <?php endif; ?>
    </section>

    <div class="total" style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #333;">
        RESUMEN ECONÓMICO<br>
        Total Invertido (Mantenimientos + Multas): <?= formatMoney($totalMantenimientos + $totalMultas) ?>
    </div>
</body>
</html>
